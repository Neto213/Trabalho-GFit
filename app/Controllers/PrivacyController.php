<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\PrivacyRepository;
use App\Services\AuthService;
use App\Services\SecurityAuditService;
use App\Support\Config;
use App\Support\Router;
use App\Support\SecurityHeaders;
use App\Support\Session;
use App\Support\View;

final class PrivacyController
{
    private function actor(): array
    {
        if (!(new AuthService())->validateAuthenticatedSession()) {
            Router::redirect('/login');
        }
        SecurityHeaders::applyPrivateCacheHeaders();
        return Session::getUser();
    }

    public function notice(): void
    {
        View::render('privacy.notice', [
            'title' => 'Privacidade e proteção de dados',
            'contact' => Config::get('privacy.contact_email'),
            'controller' => Config::get('privacy.controller_name'),
            'version' => Config::get('privacy.notice_version'),
        ], 'main');
    }

    public function center(): void
    {
        $actor = $this->actor();
        $repo = new PrivacyRepository();
        View::render('privacy.center', [
            'title' => 'Minha privacidade', 'actor' => $actor,
            'healthConsent' => $repo->hasConsent((int)$actor['id'], 'avaliacao_fisica'),
            'consents' => $repo->consentHistory((int)$actor['id']),
            'requests' => $repo->requestsForUser((int)$actor['id']),
            'networkRequests' => $actor['perfil'] === 'admin' ? $repo->requestsForNetwork((int)$actor['rede_id']) : [],
            'contact' => Config::get('privacy.contact_email'),
        ], 'main');
    }

    public function consent(): void
    {
        $actor = $this->actor();
        $grant = ($_POST['decision'] ?? '') === 'grant';
        (new PrivacyRepository())->recordConsent($actor, 'avaliacao_fisica', $grant, AuthService::getClientIp());
        SecurityAuditService::log('privacy_consent_changed', 'SUCCESS', ['user_id' => $actor['id'], 'granted' => $grant]);
        Session::setFlash('success', $grant ? 'Consentimento registrado.' : 'Consentimento revogado. Novas avaliações físicas ficam bloqueadas.');
        Router::redirect('/minha-privacidade');
    }

    public function request(): void
    {
        $actor = $this->actor();
        $type = (string)($_POST['tipo'] ?? '');
        $details = trim((string)($_POST['detalhes'] ?? ''));
        $allowed = ['acesso','correcao','exclusao','portabilidade','informacao','oposicao','revogacao'];
        if (!in_array($type, $allowed, true) || mb_strlen($details) < 10 || mb_strlen($details) > 3000) {
            Session::setFlash('error', 'Selecione um direito e descreva a solicitação entre 10 e 3.000 caracteres.');
            Router::redirect('/minha-privacidade');
        }
        $id = (new PrivacyRepository())->createRequest($actor, $type, $details);
        SecurityAuditService::log('privacy_request_created', 'SUCCESS', ['user_id' => $actor['id'], 'request_id' => $id, 'type' => $type]);
        Session::setFlash('success', "Solicitação #{$id} registrada para análise.");
        Router::redirect('/minha-privacidade');
    }

    public function updateRequest(): void
    {
        $actor = $this->actor();
        if ($actor['perfil'] !== 'admin') {
            http_response_code(403); View::render('errors.403', ['title' => 'Acesso negado'], 'main'); return;
        }
        $status = (string)($_POST['status'] ?? '');
        $answer = trim((string)($_POST['resposta'] ?? ''));
        if (!in_array($status, ['aberta','em_analise','concluida','negada'], true) || mb_strlen($answer) > 3000) {
            Session::setFlash('error', 'Status ou resposta inválidos.');
        } else {
            (new PrivacyRepository())->updateRequest($actor, (int)($_POST['id'] ?? 0), $status, $answer);
            SecurityAuditService::log('privacy_request_updated', 'SUCCESS', ['admin_id' => $actor['id'], 'request_id' => (int)($_POST['id'] ?? 0), 'status' => $status]);
            Session::setFlash('success', 'Solicitação atualizada.');
        }
        Router::redirect('/minha-privacidade');
    }

    public function export(): void
    {
        $actor = $this->actor();
        $payload = (new PrivacyRepository())->exportForUser($actor);
        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="gfit-meus-dados-' . date('Y-m-d') . '.json"');
        echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
