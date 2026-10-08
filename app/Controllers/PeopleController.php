<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Services\AuthService;
use App\Support\{Session,Router,View,Database,SecurityHeaders};
use DomainException;
use PDOException;

class PeopleController
{
    private function actor(): array {
        if(!(new AuthService())->validateAuthenticatedSession()) Router::redirect('/login');
        $u=Session::getUser();
        if($u['perfil']!=='admin') {http_response_code(403);View::render('errors.403',['title'=>'Acesso negado'],'main');exit;}
        SecurityHeaders::applyPrivateCacheHeaders();return $u;
    }
    public function index(): void {
        $actor=$this->actor();$db=Database::getConnection();
        $q=trim(is_string($_GET['q']??null)?$_GET['q']:'');
        $s=$db->prepare("SELECT u.id,u.nome,u.email,u.perfil,u.status,GROUP_CONCAT(DISTINCT un.nome ORDER BY un.nome SEPARATOR ', ') AS unidades FROM usuarios u LEFT JOIN usuario_unidades uu ON uu.usuario_id=u.id LEFT JOIN unidades un ON un.id=uu.unidade_id AND un.rede_id=u.rede_id WHERE u.rede_id=? AND (u.nome LIKE ? OR u.email LIKE ?) GROUP BY u.id ORDER BY u.nome LIMIT 200");
        $s->execute([$actor['rede_id'],'%'.$q.'%','%'.$q.'%']);$people=$s->fetchAll();
        $s=$db->prepare('SELECT * FROM unidades WHERE rede_id=? ORDER BY nome');$s->execute([$actor['rede_id']]);$units=$s->fetchAll();
        $editing=null;$linked=[];
        if(!empty($_GET['editar'])) {
            $s=$db->prepare("SELECT id,nome,email,perfil,status FROM usuarios WHERE id=? AND rede_id=? AND perfil IN ('aluno','profissional')");$s->execute([(int)$_GET['editar'],$actor['rede_id']]);$editing=$s->fetch()?:null;
            if($editing){$s=$db->prepare('SELECT unidade_id FROM usuario_unidades WHERE usuario_id=?');$s->execute([$editing['id']]);$linked=$s->fetchAll(\PDO::FETCH_COLUMN);}
        }
        View::render('admin.people',compact('actor','people','units','editing','linked','q')+['title'=>'Pessoas e unidades'],'main');
    }
    public function save(): void {
        $actor=$this->actor();$db=Database::getConnection();
        try {
            $name=$this->value('nome',150);
            if(($_POST['action']??'')==='unit') {
                $id=(int)($_POST['id']??0);$status=($_POST['status']??'ativo')==='inativo'?'inativo':'ativo';
                if($id){$db->prepare('UPDATE unidades SET nome=?,status=? WHERE id=? AND rede_id=?')->execute([$name,$status,$id,$actor['rede_id']]);}
                else {$db->prepare('INSERT INTO unidades (rede_id,nome) VALUES (?,?)')->execute([$actor['rede_id'],$name]);}
                Session::setFlash('success','Unidade salva com sucesso.');
            } else {
                $email=$this->value('email',191);if(!filter_var($email,FILTER_VALIDATE_EMAIL)) throw new DomainException('Informe um e-mail válido.');
                $role=$_POST['perfil']??'';if(!in_array($role,['aluno','profissional'],true)) throw new DomainException('Selecione um perfil válido.');
                $id=(int)($_POST['id']??0);$status=($_POST['status']??'ativo')==='inativo'?'inativo':'ativo';
                $units=$_POST['unidades']??[];if(!is_array($units)) throw new DomainException('Selecione as unidades.');
                $units=array_values(array_unique(array_map('intval',$units)));
                $s=$db->prepare('SELECT id FROM unidades WHERE rede_id=?');$s->execute([$actor['rede_id']]);$allowed=array_map('intval',$s->fetchAll(\PDO::FETCH_COLUMN));
                if(array_diff($units,$allowed)) throw new DomainException('Unidade inválida para esta rede.');
                $db->beginTransaction();
                if($id) {
                    $s=$db->prepare("SELECT id FROM usuarios WHERE id=? AND rede_id=? AND perfil IN ('aluno','profissional') FOR UPDATE");$s->execute([$id,$actor['rede_id']]);
                    if(!$s->fetch()) throw new DomainException('Cadastro não encontrado ou protegido.');
                    $db->prepare('UPDATE usuarios SET nome=?,email=?,perfil=?,status=?,session_version=session_version+1 WHERE id=? AND rede_id=?')->execute([$name,$email,$role,$status,$id,$actor['rede_id']]);
                } else {
                    $password=$this->value('senha',128);if(mb_strlen($password)<12) throw new DomainException('A senha inicial deve ter pelo menos 12 caracteres.');
                    $db->prepare('INSERT INTO usuarios (rede_id,nome,email,senha_hash,perfil,status) VALUES (?,?,?,?,?,?)')->execute([$actor['rede_id'],$name,$email,password_hash($password,PASSWORD_DEFAULT),$role,$status]);$id=(int)$db->lastInsertId();
                }
                $db->prepare('DELETE FROM usuario_unidades WHERE usuario_id=?')->execute([$id]);
                foreach($units as $unit) $db->prepare('INSERT INTO usuario_unidades (usuario_id,unidade_id) VALUES (?,?)')->execute([$id,$unit]);
                $db->commit();Session::setFlash('success','Cadastro salvo com sucesso.');
            }
        } catch(DomainException $e) {if($db->inTransaction())$db->rollBack();Session::setFlash('error',$e->getMessage());}
        catch(PDOException $e) {if($db->inTransaction())$db->rollBack();if($e->getCode()==='23000')Session::setFlash('error','Este e-mail já está cadastrado. Use outro endereço.');else throw $e;}
        Router::redirect('/admin/pessoas');
    }
    private function value(string $key,int $max): string {$v=$_POST[$key]??'';if(!is_string($v)||trim($v)===''||mb_strlen($v)>$max)throw new DomainException('Preencha todos os campos obrigatórios corretamente.');return trim($v);}
}
