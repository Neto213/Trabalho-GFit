<?php
declare(strict_types=1);
spl_autoload_register(function(string $class):void{$prefix='App\\';if(!str_starts_with($class,$prefix))return;$file=__DIR__.'/../app/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php';if(is_file($file))require_once $file;});
use App\Repositories\PrivacyRepository;
use App\Repositories\TrainingRepository;
use App\Repositories\UserRepository;
use App\Support\Config;
use App\Support\Database;
Config::load(__DIR__.'/../.env');$pdo=Database::getConnection();$users=new UserRepository($pdo);
$professional=$users->findByEmail('personal@alphafit.com.br');$student=$users->findByEmail('aluno@alphafit.com.br');
if(!$professional||!$student){fwrite(STDERR,"Contas de teste não disponíveis.\n");exit(2);}
$consentId=0;$assessmentId=0;
try{
    $privacy=new PrivacyRepository($pdo);$privacy->recordConsent($student,'avaliacao_fisica',true,'127.0.0.1');$consentId=(int)$pdo->lastInsertId();
    if(!$privacy->hasConsent((int)$student['id'],'avaliacao_fisica'))throw new RuntimeException('Consentimento não foi reconhecido.');
    $training=new TrainingRepository($pdo);$training->saveAssessment($professional,(int)$student['id'],71.25,175.0,'registro temporário de verificação LGPD');
    $assessmentId=(int)$pdo->query('SELECT MAX(id) FROM gfit_avaliacoes WHERE aluno_id='.(int)$student['id'].' AND profissional_id='.(int)$professional['id'])->fetchColumn();
    $row=$pdo->query('SELECT peso,altura,observacoes,dados_protegidos FROM gfit_avaliacoes WHERE id='.$assessmentId)->fetch();
    if(!$row||$row['peso']!==null||$row['altura']!==null||$row['observacoes']!==null||!str_starts_with($row['dados_protegidos'],'gfit:v1:'))throw new RuntimeException('Persistência em texto claro detectada.');
    $data=$training->data($student,(int)$student['id']);$found=array_values(array_filter($data['assessments'],fn($a)=>(int)$a['id']===$assessmentId));
    if(!$found||(float)$found[0]['peso']!==71.25||$found[0]['observacoes']!=='registro temporário de verificação LGPD')throw new RuntimeException('Leitura autorizada não recuperou o dado protegido.');
    echo "[PASS] Consentimento, gravação criptografada e leitura autorizada.\n";
}finally{
    if($assessmentId)$pdo->prepare('DELETE FROM gfit_avaliacoes WHERE id=?')->execute([$assessmentId]);
    if($consentId)$pdo->prepare('DELETE FROM lgpd_consentimentos WHERE id=?')->execute([$consentId]);
}
