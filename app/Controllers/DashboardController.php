<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\ContentRepository;
use App\Repositories\NetworkRepository;
use App\Repositories\StatsRepository;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Support\Router;
use App\Support\Session;
use App\Support\View;

class DashboardController
{
    private AuthService $authService;
    private UserRepository $userRepo;
    private NetworkRepository $networkRepo;
    private UnitRepository $unitRepo;
    private ContentRepository $contentRepo;
    private StatsRepository $statsRepo;

    public function __construct(
        ?AuthService $authService = null,
        ?UserRepository $userRepo = null,
        ?NetworkRepository $networkRepo = null,
        ?UnitRepository $unitRepo = null,
        ?ContentRepository $contentRepo = null,
        ?StatsRepository $statsRepo = null
    ) {
        $this->authService = $authService ?? new AuthService();
        $this->userRepo = $userRepo ?? new UserRepository();
        $this->networkRepo = $networkRepo ?? new NetworkRepository();
        $this->unitRepo = $unitRepo ?? new UnitRepository();
        $this->contentRepo = $contentRepo ?? new ContentRepository();
        $this->statsRepo = $statsRepo ?? new StatsRepository();
    }

    private function requireAuthAndRole(string $requiredRole): array
    {
        Session::start();

        // Se houver desafio MFA pendente, barrar imediatamente
        if (Session::isMfaPending()) {
            Session::setFlash('warning', 'Conclua a verificação em duas etapas para acessar o painel.');
            Router::redirect('/login/mfa');
            return [];
        }

        if (!$this->authService->validateAuthenticatedSession()) {
            Session::setFlash('warning', 'Por favor, faça login para acessar esta área.');
            Router::redirect('/login');
            return [];
        }

        // Aplicar cabeçalhos anti-cache em todas as telas privadas autenticadas (ASVS V14.4.4)
        \App\Support\SecurityHeaders::applyPrivateCacheHeaders();

        $user = Session::getUser();

        if ($user['perfil'] !== $requiredRole) {
            http_response_code(403);
            View::render('errors.403', [
                'title' => 'Acesso Negado',
                'user' => $user,
                'requiredRole' => $requiredRole,
            ], 'main');
            exit;
        }

        return $user;
    }


    public function admin(): void
    {
        $sessionUser = $this->requireAuthAndRole('admin');
        $user = $this->userRepo->findById($sessionUser['id']);
        $networkId = (int)$user['rede_id'];

        $network = $this->networkRepo->findById($networkId);
        $networkUnits = $this->unitRepo->findByNetwork($networkId);
        $linkedUnits = $this->userRepo->getLinkedUnits((int)$user['id']);
        $summary = $this->statsRepo->getSummary($networkId);
        $announcements = $this->contentRepo->findAllByNetwork($networkId);
        $institutional = $this->contentRepo->getInstitutionalContent($networkId);

        View::render('dashboard.admin', [
            'title' => 'Painel Administrativo',
            'user' => $user,
            'network' => $network,
            'networkUnits' => $networkUnits,
            'linkedUnits' => $linkedUnits,
            'summary' => $summary,
            'announcements' => array_slice($announcements, 0, 5),
            'institutional' => $institutional,
        ], 'main');
    }

    public function professional(): void
    {
        $sessionUser = $this->requireAuthAndRole('profissional');
        $user = $this->userRepo->findById($sessionUser['id']);
        $networkId = (int)$user['rede_id'];

        $network = $this->networkRepo->findById($networkId);
        $linkedUnits = $this->userRepo->getLinkedUnits((int)$user['id']);
        $announcements = $this->visibleAnnouncements($networkId, 'profissionais', $linkedUnits);
        $institutional = $this->contentRepo->getInstitutionalContent($networkId);

        View::render('dashboard.professional', [
            'title' => 'Painel do Profissional',
            'user' => $user,
            'network' => $network,
            'linkedUnits' => $linkedUnits,
            'announcements' => $announcements,
            'institutional' => $institutional,
        ], 'main');
    }

    public function student(): void
    {
        $sessionUser = $this->requireAuthAndRole('aluno');
        $user = $this->userRepo->findById($sessionUser['id']);
        $networkId = (int)$user['rede_id'];

        $network = $this->networkRepo->findById($networkId);
        $linkedUnits = $this->userRepo->getLinkedUnits((int)$user['id']);
        $announcements = $this->visibleAnnouncements($networkId, 'alunos', $linkedUnits);
        $institutional = $this->contentRepo->getInstitutionalContent($networkId);

        View::render('dashboard.student', [
            'title' => 'Painel do Aluno',
            'user' => $user,
            'network' => $network,
            'linkedUnits' => $linkedUnits,
            'announcements' => $announcements,
            'institutional' => $institutional,
        ], 'main');
    }

    private function visibleAnnouncements(int $networkId, string $audience, array $units): array
    {
        $ids = array_map('intval', array_column($units, 'id'));
        return array_values(array_filter(
            $this->contentRepo->findActiveByNetworkAndAudience($networkId, $audience),
            fn(array $item): bool => empty($item['unidade_id']) || in_array((int)$item['unidade_id'], $ids, true)
        ));
    }
}
