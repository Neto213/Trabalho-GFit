<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\MfaRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\MfaService;
use App\Support\Router;
use App\Support\Session;
use App\Support\View;

class MfaController
{
    private AuthService $authService;
    private MfaService $mfaService;
    private UserRepository $userRepo;
    private MfaRepository $mfaRepo;

    public function __construct(
        ?AuthService $authService = null,
        ?MfaService $mfaService = null,
        ?UserRepository $userRepo = null,
        ?MfaRepository $mfaRepo = null
    ) {
        $this->authService = $authService ?? new AuthService();
        $this->mfaService = $mfaService ?? new MfaService();
        $this->userRepo = $userRepo ?? new UserRepository();
        $this->mfaRepo = $mfaRepo ?? new MfaRepository();
    }

    /**
     * Exibe o formulário de desafio TOTP (6 dígitos) no login.
     * GET /login/mfa
     */
    public function showChallenge(): void
    {
        Session::start();

        if (!Session::isMfaPending()) {
            Session::setFlash('warning', 'Nenhum desafio de autenticação pendente.');
            Router::redirect('/login');
            return;
        }

        $pendingUser = Session::getMfaPendingUser();

        if (!isset($_SESSION['mfa_pending']['code_sent'])) {
            $code = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            $_SESSION['mfa_pending']['code_hash'] = password_hash($code, PASSWORD_DEFAULT);
            $_SESSION['mfa_pending']['code_sent'] = true;
            
            $mailService = new \App\Services\MailService();
            if (!$mailService->sendMfaCodeEmail($pendingUser['email'], $pendingUser['nome'], $code)) {
                unset($_SESSION['mfa_pending']['code_sent'], $_SESSION['mfa_pending']['code_hash']);
                Session::setFlash('error', 'Não foi possível enviar o código. Tente novamente ou contate o administrador.');
            }
        }

        View::render('auth.mfa-challenge', [
            'title' => 'Autenticação em Duas Etapas (MFA) - Gestão de Academia',
            'user' => $pendingUser,
        ], 'auth');
    }

    /**
     * Processa a verificação do código TOTP enviado pelo usuário.
     * POST /login/mfa
     */
    public function verifyChallenge(): void
    {
        Session::start();

        if (!Session::isMfaPending()) {
            Session::setFlash('error', 'Sessão de desafio expirada ou inválida. Faça login novamente.');
            Router::redirect('/login');
            return;
        }

        $pendingUser = Session::getMfaPendingUser();
        $code = trim($_POST['code'] ?? '');
        $ip = AuthService::getClientIp();

        if ($code === '') {
            Session::setFlash('error', 'Informe o código de 6 dígitos enviado ao seu e-mail.');
            Router::redirect('/login/mfa');
            return;
        }

        $result = $this->mfaService->verifyChallenge((int)$pendingUser['id'], $code, $ip);

        if (!$result['success']) {
            Session::incrementMfaAttempts();
            Session::setFlash('error', $result['error']);
            Router::redirect('/login/mfa');
            return;
        }

        // Conclusão bem-sucedida: eleva sessão de 'mfa_pending' para 'user' totalmente autenticado
        $user = $result['user'];
        Session::completeMfa($user);

        Session::setFlash('success', 'Autenticação em duas etapas concluída com sucesso!');
        $this->redirectToRoleDashboard($user['perfil']);
    }

    /**
     * Exibe formulário para login com código de recuperação de uso único.
     * GET /login/mfa/recuperar
     */
    public function showRecovery(): void
    {
        Session::start();

        if (!Session::isMfaPending()) {
            Session::setFlash('warning', 'Nenhum desafio de autenticação pendente.');
            Router::redirect('/login');
            return;
        }

        $pendingUser = Session::getMfaPendingUser();

        View::render('auth.mfa-recovery', [
            'title' => 'Usar Código de Recuperação - Gestão de Academia',
            'user' => $pendingUser,
        ], 'auth');
    }

    /**
     * Processa o código de recuperação de uso único.
     * POST /login/mfa/recuperar
     */
    public function verifyRecovery(): void
    {
        Session::start();

        if (!Session::isMfaPending()) {
            Session::setFlash('error', 'Sessão de desafio expirada ou inválida. Faça login novamente.');
            Router::redirect('/login');
            return;
        }

        $pendingUser = Session::getMfaPendingUser();
        $code = trim($_POST['recovery_code'] ?? '');
        $ip = AuthService::getClientIp();

        if ($code === '') {
            Session::setFlash('error', 'Por favor, informe o código de recuperação.');
            Router::redirect('/login/mfa/recuperar');
            return;
        }

        $result = $this->mfaService->verifyRecoveryCode((int)$pendingUser['id'], $code, $ip);

        if (!$result['success']) {
            Session::setFlash('error', $result['error']);
            Router::redirect('/login/mfa/recuperar');
            return;
        }

        $user = $result['user'];
        Session::completeMfa($user);

        $remaining = $result['remaining_codes'];
        Session::setFlash('warning', "Você utilizou um código de recuperação de uso único. Restam {$remaining} código(s). Recomendamos gerar novos códigos nas configurações de segurança.");

        $this->redirectToRoleDashboard($user['perfil']);
    }

    /**
     * Tela de configuração e ativação de MFA (gera segredo e QR code local).
     * GET /mfa/configurar
     */
    public function setup(): void
    {
        Session::start();

        if (!$this->authService->validateAuthenticatedSession()) {
            Session::setFlash('warning', 'Faça login para configurar a autenticação em duas etapas.');
            Router::redirect('/login');
            return;
        }

        $sessionUser = Session::getUser();
        $user = $this->userRepo->findById((int)$sessionUser['id']);
        $userId = (int) $user['id'];
        
        $pendingSetup = $_SESSION['_mfa_pending_setup'] ?? null;

        if (
            !is_array($pendingSetup)
            || (int) ($pendingSetup['user_id'] ?? 0) !== $userId
            || (int) ($pendingSetup['expires_at'] ?? 0) <= time()
        ) {
            $code = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            $pendingSetup = [
                'user_id' => $userId,
                'expires_at' => time() + 600,
                'code_hash' => password_hash($code, PASSWORD_DEFAULT),
            ];
            $_SESSION['_mfa_pending_setup'] = $pendingSetup;

            $mailService = new \App\Services\MailService();
            if (!$mailService->sendMfaCodeEmail($user['email'], $user['nome'], $code)) {
                unset($_SESSION['_mfa_pending_setup']);
                Session::setFlash('error', 'Não foi possível enviar o código. Verifique a configuração de e-mail com o administrador.');
            }
        }

        View::render('mfa.setup', [
            'title' => 'Ativar Autenticação em Duas Etapas (MFA)',
            'user' => $user,
            'isMandatory' => $this->mfaService->isMfaEnforcedForRole($user['perfil']),
        ], 'main');
    }

    /**
     * Confirmação da ativação do MFA com o primeiro código.
     * POST /mfa/confirmar
     */

    public function confirmSetup(): void
    {
        Session::start();

        if (!$this->authService->validateAuthenticatedSession()) {
            Router::redirect('/login');
            return;
        }

        // Obter o usuário ANTES de verificar a configuração.
        $sessionUser = Session::getUser();

        if (!$sessionUser || empty($sessionUser['id'])) {
            Router::redirect('/login');
            return;
        }

        $pendingSetup = $_SESSION['_mfa_pending_setup'] ?? null;

        if (
            !is_array($pendingSetup)
            || (int) ($pendingSetup['user_id'] ?? 0)
            !== (int) $sessionUser['id']
            || (int) ($pendingSetup['expires_at'] ?? 0) <= time()
            || empty($pendingSetup['code_hash'])
        ) {
            unset($_SESSION['_mfa_pending_setup']);

            Session::setFlash(
                'error',
                'A configuração expirou. Solicite um novo código por e-mail antes de tentar novamente.'
            );

            Router::redirect('/mfa/configurar');
            return;
        }

        $rawCode = $_POST['code'] ?? '';
        $code = is_string($rawCode) ? trim($rawCode) : '';
        $expectedCode = $pendingSetup['code_hash'] ?? '';

        $_SESSION['_mfa_pending_setup']['attempts'] = (int)($pendingSetup['attempts'] ?? 0) + 1;
        if ($_SESSION['_mfa_pending_setup']['attempts'] > 5) {
            Session::setFlash('error', 'Limite de tentativas atingido. Aguarde a expiração do código para tentar novamente.');
            Router::redirect('/mfa/configurar');
        }
        $result = $this->mfaService->confirmActivation(
            (int) $sessionUser['id'],
            $expectedCode,
            $code
        );

        if (!$result['success']) {
            // Preserva a configuração para tentar novamente.
            Session::setFlash('error', $result['error']);
            Router::redirect('/mfa/configurar');
            return;
        }

        // Limpa a configuração somente após ativar com sucesso.
        unset(
            $_SESSION['_mfa_pending_setup'],
            $_SESSION['_mfa_setup_secret']
        );

        $sessionUser['mfa_enabled'] = true;
        Session::regenerate();
        Session::setUser($sessionUser);

        $_SESSION['_mfa_new_recovery_codes'] =
            $result['recovery_codes'];

        Session::setFlash(
            'success',
            'Autenticação em Duas Etapas ativada com sucesso! Guarde seus códigos de recuperação em local seguro.'
        );

        Router::redirect('/mfa/gerenciar');
    }

    /**
     * Tela de gerenciamento do MFA (status, novos códigos de recuperação, desativação).
     * GET /mfa/gerenciar
     */
    public function manage(): void
    {
        Session::start();

        if (!$this->authService->validateAuthenticatedSession()) {
            Router::redirect('/login');
            return;
        }

        $sessionUser = Session::getUser();
        $user = $this->userRepo->findById((int)$sessionUser['id']);
        $remainingCodes = $this->mfaRepo->countRemainingRecoveryCodes((int)$user['id']);

        $newRecoveryCodes = $_SESSION['_mfa_new_recovery_codes'] ?? null;
        unset($_SESSION['_mfa_new_recovery_codes']);

        View::render('mfa.manage', [
            'title' => 'Gerenciar Autenticação em Duas Etapas (MFA)',
            'user' => $user,
            'remainingCodes' => $remainingCodes,
            'newRecoveryCodes' => $newRecoveryCodes,
            'isMandatory' => $this->mfaService->isMfaEnforcedForRole($user['perfil']),
        ], 'main');
    }

    /**
     * Desativa o MFA mediante senha + código atual.
     * POST /mfa/desativar
     */
    public function disable(): void
    {
        Session::start();

        if (!$this->authService->validateAuthenticatedSession()) {
            Router::redirect('/login');
            return;
        }

        $sessionUser = Session::getUser();
        $user = $this->userRepo->findById((int)$sessionUser['id']);
        $password = $_POST['senha'] ?? '';
        $code = $_POST['codigo_confirmacao'] ?? '';

        if (empty($password) || empty($code)) {
            Session::setFlash('error', 'Informe sua senha e o código atual para confirmar a desativação.');
            Router::redirect('/mfa/gerenciar');
            return;
        }

        $result = $this->mfaService->disableMfa((int)$sessionUser['id'], $password, $code);

        if (!$result['success']) {
            Session::setFlash('error', $result['error']);
            Router::redirect('/mfa/gerenciar');
            return;
        }

        $sessionUser['mfa_enabled'] = false;
        Session::setUser($sessionUser);

        Session::setFlash('success', 'A Autenticação em Duas Etapas foi desativada.');
        Router::redirect('/mfa/gerenciar');
    }

    /**
     * Regenera os códigos de recuperação mediante confirmação de senha.
     * POST /mfa/codigos-recuperacao
     */
    public function regenerateRecoveryCodes(): void
    {
        Session::start();

        if (!$this->authService->validateAuthenticatedSession()) {
            Router::redirect('/login');
            return;
        }

        $sessionUser = Session::getUser();
        $password = $_POST['senha'] ?? '';

        if (empty($password)) {
            Session::setFlash('error', 'Informe sua senha para gerar novos códigos de recuperação.');
            Router::redirect('/mfa/gerenciar');
            return;
        }

        $result = $this->mfaService->regenerateRecoveryCodes((int)$sessionUser['id'], $password);

        if (!$result['success']) {
            Session::setFlash('error', $result['error']);
            Router::redirect('/mfa/gerenciar');
            return;
        }

        $_SESSION['_mfa_new_recovery_codes'] = $result['recovery_codes'];
        Session::setFlash('success', 'Novos códigos de recuperação gerados com sucesso! Códigos anteriores foram invalidados.');
        Router::redirect('/mfa/gerenciar');
    }

    private function redirectToRoleDashboard(string $role): void
    {
        match ($role) {
            'admin' => Router::redirect('/admin'),
            'profissional' => Router::redirect('/profissional'),
            'aluno' => Router::redirect('/aluno'),
            default => Router::redirect('/login'),
        };
    }
}
