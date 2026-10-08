<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\RateLimitRepository;
use App\Repositories\UserRepository;
use App\Support\Config;
use App\Support\Session;

class AuthService
{
    private UserRepository $userRepo;
    private RateLimitRepository $rateLimitRepo;

    public function __construct(?UserRepository $userRepo = null, ?RateLimitRepository $rateLimitRepo = null)
    {
        $this->userRepo = $userRepo ?? new UserRepository();
        $this->rateLimitRepo = $rateLimitRepo ?? new RateLimitRepository();
    }

    public function attemptLogin(string $email, string $password, string $ip): array
    {
        $email = strtolower(trim($email));

        $maxPairAttempts = Config::get('auth.max_login_attempts', 5);
        $maxIpAttempts = Config::get('auth.ip_max_attempts', 20);
        $maxAccountAttempts = Config::get('auth.account_max_attempts', 5);
        $lockoutMinutes = Config::get('auth.lockout_minutes', 15);

        $ipId = DataProtectionService::fingerprint($ip);
        $accountId = DataProtectionService::fingerprint($email);
        $pairKey = "login:{$ipId}:{$accountId}";
        $ipKey = "login_ip:{$ipId}";
        $accountKey = "login_acc:{$accountId}";

        // 1. Verificar bloqueios em qualquer uma das três camadas
        if ($this->rateLimitRepo->isLocked($pairKey, 'login') ||
            $this->rateLimitRepo->isLocked($ipKey, 'login_ip') ||
            $this->rateLimitRepo->isLocked($accountKey, 'login_acc')) {

            $remPair = $this->rateLimitRepo->getLockMinutesRemaining($pairKey, 'login');
            $remIp = $this->rateLimitRepo->getLockMinutesRemaining($ipKey, 'login_ip');
            $remAcc = $this->rateLimitRepo->getLockMinutesRemaining($accountKey, 'login_acc');
            $remaining = max($remPair, $remIp, $remAcc, 1);

            SecurityAuditService::log('login_lockout', 'BLOCKED', [
                'email' => $email,
                'ip' => $ip,
                'remaining_minutes' => $remaining,
            ]);

            return [
                'success' => false,
                'error' => "Limite de tentativas excedido. Por favor, aguarde {$remaining} minuto(s) antes de tentar novamente.",
                'user' => null,
                'mfa_required' => false,
            ];
        }

        $user = $this->userRepo->findByEmail($email);

        // 2. Validação de credenciais com resposta genérica anti-enumeração
        if (!$user || !password_verify($password, $user['senha_hash'])) {
            $this->rateLimitRepo->increment($pairKey, 'login', $maxPairAttempts, $lockoutMinutes);
            $this->rateLimitRepo->increment($ipKey, 'login_ip', $maxIpAttempts, $lockoutMinutes);
            $this->rateLimitRepo->increment($accountKey, 'login_acc', $maxAccountAttempts, $lockoutMinutes);

            SecurityAuditService::log('login_failed', 'FAILURE', [
                'email' => $email,
                'ip' => $ip,
            ]);

            return [
                'success' => false,
                'error' => 'E-mail ou senha incorretos.',
                'user' => null,
                'mfa_required' => false,
            ];
        }

        // 3. Validação de conta ativa
        if ($user['status'] !== 'ativo') {
            SecurityAuditService::log('login_inactive_account', 'FAILURE', ['user_id' => $user['id']]);
            return [
                'success' => false,
                'error' => 'Esta conta de usuário está inativa. Entre em contato com a administração da academia.',
                'user' => null,
                'mfa_required' => false,
            ];
        }

        // 4. Validação de rede ativa
        if ($user['rede_status'] !== 'ativo') {
            SecurityAuditService::log('login_inactive_network', 'FAILURE', ['user_id' => $user['id']]);
            return [
                'success' => false,
                'error' => 'A rede vinculada a esta conta está inativa. Acesso suspenso.',
                'user' => null,
                'mfa_required' => false,
            ];
        }

        // 5. Sucesso de credenciais primárias: resetar tentativas falhas
        $this->rateLimitRepo->reset($pairKey, 'login');
        $this->rateLimitRepo->reset($ipKey, 'login_ip');
        $this->rateLimitRepo->reset($accountKey, 'login_acc');

        // Atualização transparente de hash de senha se necessário (ASVS V2.1.2)
        if (password_needs_rehash($user['senha_hash'], PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $this->userRepo->updatePasswordHash((int)$user['id'], $newHash);
            $user['senha_hash'] = $newHash;
        }

        Session::start();

        // 6. Verificar se o usuário possui MFA ativado
        if (!empty($user['mfa_enabled'])) {
            $timeoutMinutes = (int)Config::get('auth.mfa.challenge_timeout_minutes', 5);
            Session::setMfaPending($user, $timeoutMinutes);

            SecurityAuditService::log('mfa_challenge_prompted', 'SUCCESS', [
                'user_id' => $user['id'],
                'ip' => $ip,
            ]);

            return [
                'success' => true,
                'mfa_required' => true,
                'user' => $user,
            ];
        }

        // 7. Se não tem MFA ativado: verificar se o perfil exige adesão mandatória
        $enforceRoles = Config::get('auth.mfa.enforce_roles', ['admin', 'profissional']);
        $mfaSetupRequired = in_array($user['perfil'], $enforceRoles, true);

        // Login completo com renovação estrita de identificador de sessão
        Session::regenerate();
        Session::setUser($user);

        SecurityAuditService::log('login_success', 'SUCCESS', [
            'user_id' => $user['id'],
            'perfil' => $user['perfil'],
            'ip' => $ip,
        ]);

        return [
            'success' => true,
            'mfa_required' => false,
            'mfa_setup_required' => $mfaSetupRequired,
            'user' => $user,
        ];
    }


    public function validateAuthenticatedSession(): bool
    {
        Session::start();
        if (!Session::isLoggedIn()) {
            return false;
        }

        $sessionUser = Session::getUser();
        if (!$sessionUser || empty($sessionUser['id'])) {
            return false;
        }

        $user = $this->userRepo->findById((int)$sessionUser['id']);

        if (!$user || $user['status'] !== 'ativo' || $user['rede_status'] !== 'ativo') {
            Session::logout();
            return false;
        }

        // Validar versão da sessão para revogação instantânea de sessões antigas
        if ((int)$user['session_version'] !== (int)$sessionUser['session_version']) {
            Session::logout();
            return false;
        }

        $enforceRoles = Config::get('auth.mfa.enforce_roles', ['admin', 'profissional']);
        $path = \App\Support\Router::resolveRequestPath();
        if (PHP_SAPI !== 'cli' && in_array($user['perfil'], $enforceRoles, true) && empty($user['mfa_enabled'])
            && !in_array($path, ['/mfa/configurar', '/mfa/confirmar', '/logout'], true)) {
            \App\Support\Router::redirect('/mfa/configurar');
        }
        return true;
    }

    public function logout(): void
    {
        Session::logout();
    }

    public static function getClientIp(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '127.0.0.1';
    }
}
