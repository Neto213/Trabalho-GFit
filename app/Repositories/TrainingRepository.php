<?php
declare(strict_types=1);
namespace App\Repositories;
use App\Support\Database;
use App\Services\DataProtectionService;
use PDO;
use DomainException;

class TrainingRepository
{
    private DataProtectionService $protection;
    public function __construct(private ?PDO $pdo = null, ?DataProtectionService $protection = null) {
        $this->pdo ??= Database::getConnection();
        $this->protection = $protection ?? new DataProtectionService();
    }
    private function query(string $sql, array $params): array {
        $s=$this->pdo->prepare($sql); $s->execute($params); return $s->fetchAll();
    }
    public function students(array $actor): array {
        $sql="SELECT u.id,u.nome,u.email FROM usuarios u WHERE u.rede_id=? AND u.perfil='aluno' AND u.status='ativo'";
        $p=[$actor['rede_id']];
        if($actor['perfil']==='profissional') {
            $sql.=' AND EXISTS (SELECT 1 FROM usuario_unidades a JOIN usuario_unidades b ON a.unidade_id=b.unidade_id JOIN unidades un ON un.id=a.unidade_id WHERE a.usuario_id=u.id AND b.usuario_id=? AND un.status=\'ativo\' AND un.rede_id=u.rede_id)';
            $p[]=$actor['id'];
        }
        return $this->query($sql.' ORDER BY u.nome',$p);
    }
    public function authorizeStudent(array $actor,int $id): void {
        if($actor['perfil']==='aluno' && (int)$actor['id']===$id) return;
        foreach($this->students($actor) as $s) if((int)$s['id']===$id) return;
        throw new DomainException('Aluno não disponível nas suas unidades.');
    }
    public function data(array $actor,int $id): array {
        $this->authorizeStudent($actor,$id);
        $p=[$actor['rede_id'],$id];
        $assessments=$this->query('SELECT a.*,u.nome AS profissional_nome FROM gfit_avaliacoes a JOIN usuarios u ON u.id=a.profissional_id WHERE a.rede_id=? AND a.aluno_id=? ORDER BY a.id DESC LIMIT 50',$p);
        foreach($assessments as &$assessment) {
            if(!empty($assessment['dados_protegidos'])) {
                $protected=$this->protection->decrypt($assessment['dados_protegidos'],'gfit:assessment:'.$assessment['id'].':'.$assessment['aluno_id']);
                $assessment['peso']=$protected['peso']??null;
                $assessment['altura']=$protected['altura']??null;
                $assessment['observacoes']=$protected['observacoes']??'';
            }
            unset($assessment['dados_protegidos']);
        }
        unset($assessment);
        return [
            'workouts'=>$this->query("SELECT t.*,u.nome AS profissional_nome FROM gfit_treinos t JOIN usuarios u ON u.id=t.profissional_id WHERE t.rede_id=? AND t.aluno_id=? ORDER BY t.status='ativo' DESC,t.id DESC",$p),
            'assessments'=>$assessments,
            'attendance'=>$this->query('SELECT f.*,t.titulo FROM gfit_frequencias f JOIN gfit_treinos t ON t.id=f.treino_id WHERE f.rede_id=? AND f.aluno_id=? ORDER BY f.id DESC LIMIT 90',$p),
        ];
    }
    public function saveWorkout(array $actor,int $student,string $title,string $goal,string $exercises): void {
        if(!in_array($actor['perfil'],['admin','profissional'],true)) throw new DomainException('Acesso negado.');
        $this->authorizeStudent($actor,$student);
        $this->pdo->prepare('INSERT INTO gfit_treinos (rede_id,aluno_id,profissional_id,titulo,objetivo,exercicios) VALUES (?,?,?,?,?,?)')->execute([$actor['rede_id'],$student,$actor['id'],$title,$goal,$exercises]);
    }
    public function saveAssessment(array $actor,int $student,float $weight,float $height,string $notes): void {
        if(!in_array($actor['perfil'],['admin','profissional'],true)) throw new DomainException('Acesso negado.');
        $this->authorizeStudent($actor,$student);
        $this->pdo->beginTransaction();
        try {
            $stmt=$this->pdo->prepare('INSERT INTO gfit_avaliacoes (rede_id,aluno_id,profissional_id,peso,altura,observacoes) VALUES (?,?,?,NULL,NULL,NULL)');
            $stmt->execute([$actor['rede_id'],$student,$actor['id']]);
            $id=(int)$this->pdo->lastInsertId();
            $payload=$this->protection->encrypt(['peso'=>$weight,'altura'=>$height,'observacoes'=>$notes],'gfit:assessment:'.$id.':'.$student);
            $this->pdo->prepare('UPDATE gfit_avaliacoes SET dados_protegidos=?,versao_criptografia=1 WHERE id=?')->execute([$payload,$id]);
            $this->pdo->commit();
        } catch(\Throwable $e) {
            if($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }
    public function archive(array $actor,int $student,int $workout): void {
        if(!in_array($actor['perfil'],['admin','profissional'],true)) throw new DomainException('Acesso negado.');
        $this->authorizeStudent($actor,$student);
        $this->pdo->prepare("UPDATE gfit_treinos SET status='arquivado' WHERE id=? AND rede_id=? AND aluno_id=?")->execute([$workout,$actor['rede_id'],$student]);
    }
    public function complete(array $actor,int $workout): bool {
        if($actor['perfil']!=='aluno') throw new DomainException('Apenas alunos podem registrar um treino.');
        $rows=$this->query("SELECT id FROM gfit_treinos WHERE id=? AND rede_id=? AND aluno_id=? AND status='ativo'",[$workout,$actor['rede_id'],$actor['id']]);
        if(!$rows) throw new DomainException('Treino indisponível.');
        $s=$this->pdo->prepare('INSERT INTO gfit_frequencias (rede_id,aluno_id,treino_id,dia) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE id=id');
        $s->execute([$actor['rede_id'],$actor['id'],$workout,date('Y-m-d')]);
        return $s->rowCount()===1;
    }
}
