<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\NetworkRepository;
use App\Repositories\StatsRepository;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Support\Router;
use App\Support\Session;
use App\Support\View;

class AdminStatsController
{
    private AuthService $authService;
    private UserRepository $userRepo;
    private NetworkRepository $networkRepo;
    private UnitRepository $unitRepo;
    private StatsRepository $statsRepo;

    public function __construct(
        ?AuthService $authService = null,
        ?UserRepository $userRepo = null,
        ?NetworkRepository $networkRepo = null,
        ?UnitRepository $unitRepo = null,
        ?StatsRepository $statsRepo = null
    ) {
        $this->authService = $authService ?? new AuthService();
        $this->userRepo = $userRepo ?? new UserRepository();
        $this->networkRepo = $networkRepo ?? new NetworkRepository();
        $this->unitRepo = $unitRepo ?? new UnitRepository();
        $this->statsRepo = $statsRepo ?? new StatsRepository();
    }

    private function requireAdmin(): array
    {
        Session::start();

        if (Session::isMfaPending()) {
            Session::setFlash('warning', 'Conclua a verificação em duas etapas para acessar as estatísticas.');
            Router::redirect('/login/mfa');
            exit;
        }

        if (!$this->authService->validateAuthenticatedSession()) {
            Session::setFlash('warning', 'Por favor, faça login para acessar esta área.');
            Router::redirect('/admin/login');
            exit;
        }

        \App\Support\SecurityHeaders::applyPrivateCacheHeaders();

        $sessionUser = Session::getUser();

        if (($sessionUser['perfil'] ?? '') !== 'admin') {
            http_response_code(403);
            View::render('errors.403', [
                'title' => 'Acesso Negado',
                'user' => $sessionUser,
                'requiredRole' => 'admin',
            ], 'main');
            exit;
        }

        return $this->userRepo->findById((int)$sessionUser['id']);
    }


    public function index(): void
    {
        $user = $this->requireAdmin();
        $networkId = (int)$user['rede_id'];

        $unitFilter = !empty($_GET['unidade_id']) ? (int)$_GET['unidade_id'] : null;
        if ($unitFilter !== null) {
            $unit = $this->unitRepo->findById($unitFilter);
            if (!$unit || (int)$unit['rede_id'] !== $networkId) {
                $unitFilter = null; // Prevenção de IDOR cross-tenant
            }
        }

        $network = $this->networkRepo->findById($networkId);
        $networkUnits = $this->unitRepo->findByNetwork($networkId);
        $summary = $this->statsRepo->getSummary($networkId, $unitFilter);
        $unitsDistribution = $this->statsRepo->getUnitsDistribution($networkId);
        $securityStats = $this->statsRepo->getAccessSecurityStats($networkId);
        $registrationTrends = $this->statsRepo->getRegistrationTrends($networkId);

        View::render('admin.stats', [
            'title' => 'Estatísticas e Métricas da Rede',
            'user' => $user,
            'network' => $network,
            'networkUnits' => $networkUnits,
            'summary' => $summary,
            'unitsDistribution' => $unitsDistribution,
            'securityStats' => $securityStats,
            'registrationTrends' => $registrationTrends,
            'selectedUnit' => $unitFilter,
        ], 'main');
    }

    public function apiData(): void
    {
        $user = $this->requireAdmin();
        $networkId = (int)$user['rede_id'];

        $unitFilter = !empty($_GET['unidade_id']) ? (int)$_GET['unidade_id'] : null;
        if ($unitFilter !== null) {
            $unit = $this->unitRepo->findById($unitFilter);
            if (!$unit || (int)$unit['rede_id'] !== $networkId) {
                $unitFilter = null;
            }
        }

        $summary = $this->statsRepo->getSummary($networkId, $unitFilter);

        $unitsDistribution = $this->statsRepo->getUnitsDistribution($networkId);
        $registrationTrends = $this->statsRepo->getRegistrationTrends($networkId);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'summary' => $summary,
            'unitsDistribution' => $unitsDistribution,
            'registrationTrends' => $registrationTrends,
        ], JSON_THROW_ON_ERROR);
        exit;
    }
}
