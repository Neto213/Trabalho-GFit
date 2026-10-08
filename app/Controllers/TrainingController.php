<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Repositories\TrainingRepository;
use App\Repositories\PrivacyRepository;
use App\Services\AuthService;
use App\Support\{Session,Router,View,SecurityHeaders};
use DomainException;

class TrainingController
{
    private function actor(): array {
        if(!(new AuthService())->validateAuthenticatedSession()) Router::redirect('/login');
        SecurityHeaders::applyPrivateCacheHeaders();
        return Session::getUser();
    }
    public function index(): void {
        $actor=$this->actor(); $repo=new TrainingRepository();
        $students=$actor['perfil']==='aluno'?[]:$repo->students($actor);
        $studentId=$actor['perfil']==='aluno'?(int)$actor['id']:(int)($_GET['aluno']??($students[0]['id']??0));
        $data=['workouts'=>[],'assessments'=>[],'attendance'=>[]];
        try { if($studentId) $data=$repo->data($actor,$studentId); }
        catch(DomainException $e) { http_response_code(403); View::render('errors.403',['title'=>'Acesso negado'],'main'); return; }
        $healthConsent=$studentId?(new PrivacyRepository())->hasConsent($studentId,'avaliacao_fisica'):false;
        View::render('training.index',array_merge($data,compact('actor','students','studentId','healthConsent'),['title'=>'Treinos e evolução']),'main');
    }
    public function save(): void {
        $actor=$this->actor(); $repo=new TrainingRepository();
        $student=(int)($_POST['aluno_id']??0); $action=$_POST['action']??'';
        try {
            if($action==='complete') {
                $student=(int)$actor['id'];
                $saved=$repo->complete($actor,(int)($_POST['treino_id']??0));
                Session::setFlash('success',$saved?'Treino concluído! Sua frequência foi registrada.':'Este treino já foi registrado hoje.');
            } elseif($action==='workout') {
                $title=$this->text('titulo',150); $goal=$this->text('objetivo',255); $exercises=$this->text('exercicios',10000);
                $repo->saveWorkout($actor,$student,$title,$goal,$exercises);
                Session::setFlash('success','Treino publicado. O aluno já pode consultar a ficha.');
            } elseif($action==='assessment') {
                if(!(new PrivacyRepository())->hasConsent($student,'avaliacao_fisica')) throw new DomainException('O aluno precisa autorizar o tratamento de dados da avaliação física em Minha privacidade.');
                $weight=filter_var($_POST['peso']??'',FILTER_VALIDATE_FLOAT); $height=filter_var($_POST['altura']??'',FILTER_VALIDATE_FLOAT);
                if($weight===false || $height===false || $weight<20 || $weight>400 || $height<80 || $height>250) throw new DomainException('Informe peso entre 20 e 400 kg e altura entre 80 e 250 cm.');
                $repo->saveAssessment($actor,$student,$weight,$height,$this->text('observacoes',5000));
                Session::setFlash('success','Avaliação registrada no histórico do aluno.');
            } elseif($action==='archive') {
                $repo->archive($actor,$student,(int)($_POST['treino_id']??0));
                Session::setFlash('success','Treino arquivado. O histórico foi preservado.');
            } else throw new DomainException('Ação inválida.');
        } catch(DomainException $e) { Session::setFlash('error',$e->getMessage()); }
        Router::redirect('/treinos'.($actor['perfil']==='aluno'?'':'?aluno='.$student));
    }
    private function text(string $key,int $max): string {
        $v=$_POST[$key]??'';
        if(!is_string($v) || trim($v)==='' || mb_strlen($v)>$max) throw new DomainException('Preencha os campos obrigatórios e respeite os limites de tamanho.');
        return trim($v);
    }
}
