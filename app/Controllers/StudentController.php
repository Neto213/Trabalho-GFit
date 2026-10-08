<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Support\Router;
use App\Support\Session;

class StudentController
{
    private AuthService $authService;
    private UserRepository $userRepo;

    public function __construct(
        ?AuthService $authService = null,
        ?UserRepository $userRepo = null
    ) {
        $this->authService = $authService ?? new AuthService();
        $this->userRepo = $userRepo ?? new UserRepository();
    }

    /**
     * Processa a alteração de senha do aluno autenticado.
     * POST /aluno/senha
     */
    public function updatePassword(): void
    {
        Session::start();

        if (!$this->authService->validateAuthenticatedSession()) {
            Session::setFlash('warning', 'Por favor, faça login para acessar esta área.');
            Router::redirect('/login');
            return;
        }

        $sessionUser = Session::getUser();

        if ($sessionUser['perfil'] !== 'aluno') {
            http_response_code(403);
            Session::setFlash('error', 'Acesso negado.');
            Router::redirect('/login');
            return;
        }

        $senhaAtual = $_POST['senha_atual'] ?? '';
        $novaSenha = $_POST['nova_senha'] ?? '';
        $confirmarSenha = $_POST['confirmar_senha'] ?? '';

        // Validações
        if (empty($senhaAtual) || empty($novaSenha) || empty($confirmarSenha)) {
            Session::setFlash('error', 'Todos os campos de senha são obrigatórios.');
            Router::redirect('/aluno#tab-settings');
            return;
        }

        if (mb_strlen($novaSenha) < 12) {
            Session::setFlash('error', 'A nova senha deve possuir no mínimo 12 caracteres.');
            Router::redirect('/aluno#tab-settings');
            return;
        }

        if (mb_strlen($novaSenha) > 128) {
            Session::setFlash('error', 'A nova senha não pode exceder 128 caracteres.');
            Router::redirect('/aluno#tab-settings');
            return;
        }

        if ($novaSenha !== $confirmarSenha) {
            Session::setFlash('error', 'A nova senha e a confirmação não coincidem.');
            Router::redirect('/aluno#tab-settings');
            return;
        }

        // Verificar senha atual
        $user = $this->userRepo->findById($sessionUser['id']);

        if (!$user || !password_verify($senhaAtual, $user['senha_hash'])) {
            \App\Services\SecurityAuditService::log('password_change_failed', 'FAILURE', ['user_id' => $sessionUser['id']]);
            Session::setFlash('error', 'A senha atual está incorreta.');
            Router::redirect('/aluno#tab-settings');
            return;
        }

        // Atualizar senha e revogar sessões anteriores (preserva MFA intacto)
        $newHash = password_hash($novaSenha, PASSWORD_DEFAULT);
        $this->userRepo->updatePasswordAndRevokeSessions((int)$user['id'], $newHash);
        \App\Services\SecurityAuditService::log('password_changed', 'SUCCESS', ['user_id' => $user['id']]);

        // Deslogar e redirecionar para login
        Session::destroy();
        Session::start();

        Session::setFlash('success', 'Senha alterada com sucesso! Faça login novamente com a nova senha.');
        Router::redirect('/login');
    }
}
