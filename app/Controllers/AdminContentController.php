<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\ContentRepository;
use App\Repositories\NetworkRepository;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Support\Router;
use App\Support\Session;
use App\Support\View;

class AdminContentController
{
    private AuthService $authService;
    private UserRepository $userRepo;
    private NetworkRepository $networkRepo;
    private UnitRepository $unitRepo;
    private ContentRepository $contentRepo;

    public function __construct(
        ?AuthService $authService = null,
        ?UserRepository $userRepo = null,
        ?NetworkRepository $networkRepo = null,
        ?UnitRepository $unitRepo = null,
        ?ContentRepository $contentRepo = null
    ) {
        $this->authService = $authService ?? new AuthService();
        $this->userRepo = $userRepo ?? new UserRepository();
        $this->networkRepo = $networkRepo ?? new NetworkRepository();
        $this->unitRepo = $unitRepo ?? new UnitRepository();
        $this->contentRepo = $contentRepo ?? new ContentRepository();
    }

    private function requireAdmin(): array
    {
        Session::start();

        if (Session::isMfaPending()) {
            Session::setFlash('warning', 'Conclua a verificação em duas etapas para acessar o painel administrativo.');
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

        $network = $this->networkRepo->findById($networkId);
        $networkUnits = $this->unitRepo->findByNetwork($networkId);
        $announcements = $this->contentRepo->findAllByNetwork($networkId);
        $institutional = $this->contentRepo->getInstitutionalContent($networkId);

        View::render('admin.content', [
            'title' => 'Gestão de Conteúdo e Comunicados',
            'user' => $user,
            'network' => $network,
            'networkUnits' => $networkUnits,
            'announcements' => $announcements,
            'institutional' => $institutional,
        ], 'main');
    }

    public function save(): void
    {
        $user = $this->requireAdmin();
        $networkId = (int)$user['rede_id'];

        $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
        $titulo = trim($_POST['titulo'] ?? '');
        $categoria = trim($_POST['categoria'] ?? 'Geral');
        $publicoAlvo = trim($_POST['publico_alvo'] ?? 'todos');
        $unidadeId = !empty($_POST['unidade_id']) ? (int)$_POST['unidade_id'] : null;
        $conteudo = trim($_POST['conteudo'] ?? '');
        $fixado = !empty($_POST['fixado']) ? 1 : 0;
        $status = ($_POST['status'] ?? 'ativo') === 'inativo' ? 'inativo' : 'ativo';

        if ($titulo === '' || $conteudo === '') {
            Session::setFlash('error', 'O título e o conteúdo do comunicado são obrigatórios.');
            Router::redirect('/admin/conteudo');
            return;
        }

        // Validação estrita de isolamento multi-tenant: garantir que unidade pertence à rede do admin
        if ($unidadeId !== null) {
            $unit = $this->unitRepo->findById($unidadeId);
            if (!$unit || (int)$unit['rede_id'] !== $networkId) {
                Session::setFlash('error', 'A unidade selecionada não pertence à sua rede autorizada.');
                Router::redirect('/admin/conteudo');
                return;
            }
        }


        $data = [
            'rede_id' => $networkId,
            'unidade_id' => $unidadeId,
            'titulo' => $titulo,
            'categoria' => $categoria,
            'publico_alvo' => $publicoAlvo,
            'conteudo' => $conteudo,
            'fixado' => $fixado,
            'status' => $status,
            'autor_id' => (int)$user['id'],
        ];

        if ($id !== null) {
            $this->contentRepo->update($id, $networkId, $data);
            Session::setFlash('success', 'Comunicado atualizado com sucesso.');
        } else {
            $this->contentRepo->create($data);
            Session::setFlash('success', 'Novo comunicado publicado com sucesso.');
        }

        Router::redirect('/admin/conteudo');
    }

    public function toggleStatus(): void
    {
        $user = $this->requireAdmin();
        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0) {
            $this->contentRepo->toggleStatus($id, (int)$user['rede_id']);
            Session::setFlash('success', 'Status do comunicado alterado com sucesso.');
        }

        Router::redirect('/admin/conteudo');
    }

    public function delete(): void
    {
        $user = $this->requireAdmin();
        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0) {
            $this->contentRepo->delete($id, (int)$user['rede_id']);
            Session::setFlash('success', 'Comunicado excluído com sucesso.');
        }

        Router::redirect('/admin/conteudo');
    }

    public function saveInstitutional(): void
    {
        $user = $this->requireAdmin();
        $networkId = (int)$user['rede_id'];

        $data = [
            'slogan' => trim($_POST['slogan'] ?? ''),
            'descricao' => trim($_POST['descricao'] ?? ''),
            'horario_funcionamento' => trim($_POST['horario_funcionamento'] ?? ''),
            'telefone_contato' => trim($_POST['telefone_contato'] ?? ''),
            'email_contato' => trim($_POST['email_contato'] ?? ''),
            'banner_aviso' => trim($_POST['banner_aviso'] ?? ''),
            'banner_ativo' => !empty($_POST['banner_ativo']) ? 1 : 0,
            'regras_gerais' => trim($_POST['regras_gerais'] ?? ''),
        ];

        $this->contentRepo->saveInstitutionalContent($networkId, $data);
        Session::setFlash('success', 'Informações institucionais e alertas da rede salvos com sucesso.');

        Router::redirect('/admin/conteudo');
    }
}
