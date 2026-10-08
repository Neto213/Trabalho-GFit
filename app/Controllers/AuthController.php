<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Services\PasswordResetService;
use App\Support\Router;
use App\Support\Session;
use App\Support\View;

class AuthController
{
    private AuthService $authService;
    private PasswordResetService $resetService;
    private UserRepository $userRepo;
    private UnitRepository $unitRepo;

    public function __construct(
        ?AuthService $authService = null,
        ?PasswordResetService $resetService = null,
        ?UserRepository $userRepo = null,
        ?UnitRepository $unitRepo = null
    ) {
        $this->authService = $authService ?? new AuthService();
        $this->resetService = $resetService ?? new PasswordResetService();
        $this->userRepo = $userRepo ?? new UserRepository();
        $this->unitRepo = $unitRepo ?? new UnitRepository();
    }

    public function showSignup(): void
    {
        Session::start();
        if ($this->authService->validateAuthenticatedSession()) {
            $user = Session::getUser();
            $this->redirectToRoleDashboard($user['perfil']);
            return;
        }

        $units = $this->unitRepo->getAllActiveWithNetwork();

        View::render('auth.signup', [
            'title' => 'Criar Nova Conta - Gestão de Academia',
            'units' => $units,
        ], 'auth');
    }

    public function signup(): void
    {
        $nome = trim($_POST['nome'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $senha = $_POST['senha'] ?? '';
        $confirmarSenha = $_POST['confirmar_senha'] ?? '';
        $unidadeId = (int)($_POST['unidade_id'] ?? 0);
        $perfil = trim($_POST['perfil'] ?? 'aluno');
        $privacyAccepted = ($_POST['aceite_privacidade'] ?? '') === '1';

        Session::setOld([
            'nome' => $nome,
            'email' => $email,
            'unidade_id' => $unidadeId,
            'perfil' => $perfil,
        ]);

        if (mb_strlen($nome) < 3 || mb_strlen($nome) > 150) {
            Session::setFlash('error', 'Por favor, informe seu nome completo (mínimo de 3 caracteres).');
            Router::redirect('/cadastro');
            return;
        }

        if ($email === '' || mb_strlen($email) > 191 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::setFlash('error', 'Por favor, informe um endereço de e-mail válido.');
            Router::redirect('/cadastro');
            return;
        }

        $existing = $this->userRepo->findByEmail($email);
        if ($existing) {
            Session::setFlash('error', 'Este e-mail já está cadastrado no sistema. Tente fazer login ou recuperar sua senha.');
            Router::redirect('/cadastro');
            return;
        }

        $unit = $this->unitRepo->findById($unidadeId);
        if (!$unit || $unit['status'] !== 'ativo') {
            Session::setFlash('error', 'Por favor, selecione uma unidade válida em operação.');
            Router::redirect('/cadastro');
            return;
        }

        if (strlen($senha) < 12 || strlen($senha) > 128) {
            Session::setFlash('error', 'A senha deve conter entre 12 e 128 caracteres.');
            Router::redirect('/cadastro');
            return;
        }

        if (!$privacyAccepted) {
            Session::setFlash('error', 'Leia e confirme o Aviso de Privacidade para concluir o cadastro.');
            Router::redirect('/cadastro');
            return;
        }

        if ($senha !== $confirmarSenha) {
            Session::setFlash('error', 'A confirmação de senha não confere com a senha digitada.');
            Router::redirect('/cadastro');
            return;
        }

        $allowedProfiles = ['aluno'];
        $chosenProfile = 'aluno';

        $hash = password_hash($senha, PASSWORD_DEFAULT);

        $this->userRepo->createWithUnit([
            'rede_id' => (int)$unit['rede_id'],
            'nome' => $nome,
            'email' => $email,
            'senha_hash' => $hash,
            'perfil' => $chosenProfile,
            'status' => 'ativo',
        ], $unidadeId);

        Session::clearOld();
        Session::setOld(['email' => $email]);
        Session::setFlash('success', 'Cadastro realizado com sucesso! Faça login com suas credenciais para acessar o sistema.');
        Router::redirect('/login');
    }

    public function showLogin(): void
    {
        Session::start();
        if ($this->authService->validateAuthenticatedSession()) {
            $user = Session::getUser();
            $this->redirectToRoleDashboard($user['perfil']);
            return;
        }

        View::render('auth.login', ['title' => 'Entrar no Sistema'], 'auth');
    }

    public function login(): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['senha'] ?? '';
        $ip = AuthService::getClientIp();

        if ($email === '' || $password === '') {
            Session::setOld(['email' => $email]);
            Session::setFlash('error', 'Por favor, preencha o e-mail e a senha.');
            Router::redirect('/login');
            return;
        }

        $result = $this->authService->attemptLogin($email, $password, $ip);

        if (!$result['success']) {
            Session::setOld(['email' => $email]);
            Session::setFlash('error', $result['error']);
            Router::redirect('/login');
            return;
        }

        // Se o usuário possui MFA habilitado, direcionar ao desafio em duas etapas
        if (!empty($result['mfa_required'])) {
            Router::redirect('/login/mfa');
            return;
        }

        Session::clearOld();
        $user = $result['user'];

        // Onboarding controlado: se o perfil exige MFA mas a conta ainda não configurou
        if (!empty($result['mfa_setup_required'])) {
            Session::setFlash('info', 'Atenção: A autenticação em duas etapas (MFA) é obrigatória para seu perfil de acesso. Confirme o código enviado por e-mail.');
            Router::redirect('/mfa/configurar');
            return;
        }

        $this->redirectToRoleDashboard($user['perfil']);
    }

    public function showAdminLogin(): void
    {
        Session::start();
        if ($this->authService->validateAuthenticatedSession()) {
            $user = Session::getUser();
            $this->redirectToRoleDashboard($user['perfil']);
            return;
        }

        View::render('auth.admin-login', ['title' => 'Acesso Administrativo - Gestão de Academia'], 'auth');
    }

    public function adminLogin(): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['senha'] ?? '';
        $ip = AuthService::getClientIp();

        if ($email === '' || $password === '') {
            Session::setOld(['email' => $email]);
            Session::setFlash('error', 'Por favor, informe o e-mail administrativo e a senha.');
            Router::redirect('/admin/login');
            return;
        }

        $result = $this->authService->attemptLogin($email, $password, $ip);

        if (!$result['success']) {
            Session::setOld(['email' => $email]);
            Session::setFlash('error', $result['error']);
            Router::redirect('/admin/login');
            return;
        }

        // Se exige MFA, redirecionar ao desafio
        if (!empty($result['mfa_required'])) {
            Router::redirect('/login/mfa');
            return;
        }

        $user = $result['user'];

        // Garantir que somente perfis 'admin' acessem através do portal administrativo
        if ($user['perfil'] !== 'admin') {
            $this->authService->logout();
            Session::start();
            Session::setOld(['email' => $email]);
            Session::setFlash('error', 'Acesso negado: Este portal é restrito exclusivamente a administradores da rede.');
            Router::redirect('/admin/login');
            return;
        }

        Session::clearOld();

        if (!empty($result['mfa_setup_required'])) {
            Session::setFlash('info', 'Atenção: A autenticação em duas etapas (MFA) é mandatória para administradores. Confirme o código enviado por e-mail para prosseguir.');
            Router::redirect('/mfa/configurar');
            return;
        }

        Session::setFlash('success', 'Login de administrador autenticado com sucesso.');
        Router::redirect('/admin');
    }


    public function logout(): void
    {
        $this->authService->logout();
        Session::start();
        Session::setFlash('success', 'Você saiu do sistema com segurança.');
        Router::redirect('/login');
    }

    public function showForgotPassword(): void
    {
        Session::start();
        if ($this->authService->validateAuthenticatedSession()) {
            $user = Session::getUser();
            $this->redirectToRoleDashboard($user['perfil']);
            return;
        }

        View::render('auth.forgot-password', ['title' => 'Recuperar Senha'], 'auth');
    }

    public function sendResetLink(): void
    {
        $email = trim($_POST['email'] ?? '');
        $ip = AuthService::getClientIp();

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::setOld(['email' => $email]);
            Session::setFlash('error', 'Por favor, informe um endereço de e-mail válido.');
            Router::redirect('/recuperar-senha');
            return;
        }

        $result = $this->resetService->requestReset($email, $ip);

        if ($result['success']) {
            Session::clearOld();
            Session::setFlash('success', $result['message']);
        } else {
            Session::setOld(['email' => $email]);
            Session::setFlash('error', $result['message']);
        }

        Router::redirect('/recuperar-senha');
    }

    public function showResetPassword(): void
    {
        $token = trim($_GET['token'] ?? '');

        if ($token === '') {
            Session::setFlash('error', 'Token de recuperação não fornecido.');
            Router::redirect('/login');
            return;
        }

        $record = $this->resetService->validateToken($token);

        if (!$record) {
            Session::setFlash('error', 'O link de recuperação é inválido, expirou ou já foi utilizado.');
            Router::redirect('/recuperar-senha');
            return;
        }

        View::render('auth.reset-password', [
            'title' => 'Definir Nova Senha',
            'token' => $token,
            'email' => $record['email'],
        ], 'auth');
    }

    public function resetPassword(): void
    {
        $token = trim($_POST['token'] ?? '');
        $password = $_POST['senha'] ?? '';
        $confirmPassword = $_POST['confirmar_senha'] ?? '';

        if ($token === '') {
            Session::setFlash('error', 'Token de recuperação inválido.');
            Router::redirect('/login');
            return;
        }

        $result = $this->resetService->resetPassword($token, $password, $confirmPassword);

        if (!$result['success']) {
            Session::setFlash('error', $result['error']);
            Router::redirect('/redefinir-senha?token=' . urlencode($token));
            return;
        }

        Session::setFlash('success', $result['message']);
        Router::redirect('/login');
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
