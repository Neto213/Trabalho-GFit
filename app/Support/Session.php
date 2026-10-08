<?php

declare(strict_types=1);

namespace App\Support;

class Session
{
    private static bool $started = false;

    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        $lifetime = Config::get('auth.session_lifetime', 7200);
        $absoluteLifetime = Config::get('auth.session_absolute_lifetime', 28800);

        if (!headers_sent()) {
            $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

            session_set_cookie_params([
                'lifetime' => $lifetime,
                'path' => '/',
                'domain' => '',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);

            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');
        }

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        self::$started = true;

        $now = time();

        // Inicializar timestamp absoluto de criação
        if (!isset($_SESSION['_created_at'])) {
            $_SESSION['_created_at'] = $now;
        }

        // 1. Verificar expiração absoluta da sessão
        if (($now - $_SESSION['_created_at']) > $absoluteLifetime) {
            self::destroy();
            session_start();
            $_SESSION['_created_at'] = time();
            self::setFlash('warning', 'Sua sessão atingiu o tempo máximo de duração permitida. Faça login novamente.');
        }
        // 2. Verificar expiração por inatividade
        elseif (isset($_SESSION['_last_activity']) && ($now - $_SESSION['_last_activity'] > $lifetime)) {
            self::destroy();
            session_start();
            $_SESSION['_created_at'] = time();
            self::setFlash('warning', 'Sua sessão expirou por inatividade. Faça login novamente.');
        }

        $_SESSION['_last_activity'] = $now;
    }

    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function setUser(array $user): void
    {
        self::clearMfaPending();
        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'nome' => $user['nome'],
            'email' => $user['email'],
            'perfil' => $user['perfil'],
            'rede_id' => (int)$user['rede_id'],
            'mfa_enabled' => (bool)($user['mfa_enabled'] ?? false),
            'mfa_verified' => true,
            'session_version' => (int)($user['session_version'] ?? 1),
        ];
    }

    public static function getUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function isLoggedIn(): bool
    {
        // Se houver desafio MFA pendente, NÃO é considerada sessão autenticada completa
        if (self::isMfaPending()) {
            return false;
        }
        return isset($_SESSION['user']) && !empty($_SESSION['user']['id']) && !empty($_SESSION['user']['mfa_verified']);
    }

    // --- Gerenciamento do Estado Temporário de Desafio MFA ---

    public static function setMfaPending(array $user, int $timeoutMinutes = 5): void
    {
        self::regenerate();
        unset($_SESSION['user']);
        $_SESSION['mfa_pending'] = [
            'user' => [
                'id' => (int)$user['id'],
                'nome' => $user['nome'],
                'email' => $user['email'],
                'perfil' => $user['perfil'],
                'rede_id' => (int)$user['rede_id'],
                'mfa_enabled' => (int)($user['mfa_enabled'] ?? 0),
                'session_version' => (int)($user['session_version'] ?? 1),
            ],
            'expires_at' => time() + ($timeoutMinutes * 60),
            'attempts' => 0,
        ];
    }

    public static function isMfaPending(): bool
    {
        if (empty($_SESSION['mfa_pending'])) {
            return false;
        }

        // Se expirou o tempo de resposta do desafio, anular
        if (time() > (int)($_SESSION['mfa_pending']['expires_at'] ?? 0)) {
            self::clearMfaPending();
            return false;
        }

        return true;
    }

    public static function getMfaPendingUser(): ?array
    {
        if (!self::isMfaPending()) {
            return null;
        }
        return $_SESSION['mfa_pending']['user'] ?? null;
    }

    public static function incrementMfaAttempts(): int
    {
        if (!isset($_SESSION['mfa_pending'])) {
            return 0;
        }
        $_SESSION['mfa_pending']['attempts'] = (int)($_SESSION['mfa_pending']['attempts'] ?? 0) + 1;
        return $_SESSION['mfa_pending']['attempts'];
    }

    public static function getMfaAttempts(): int
    {
        return (int)($_SESSION['mfa_pending']['attempts'] ?? 0);
    }

    public static function clearMfaPending(): void
    {
        unset($_SESSION['mfa_pending']);
    }

    public static function completeMfa(array $user): void
    {
        self::clearMfaPending();
        self::regenerate();
        self::setUser($user);
    }

    public static function logout(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            unset($_SESSION['user'], $_SESSION['mfa_pending']);
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }
            session_destroy();
            self::$started = false;
        }
    }

    public static function destroy(): void
    {
        self::logout();
    }


    public static function setFlash(string $type, string $message): void
    {
        $_SESSION['_flash'][$type] = $message;
    }

    public static function getFlash(string $type): ?string
    {
        if (isset($_SESSION['_flash'][$type])) {
            $msg = $_SESSION['_flash'][$type];
            unset($_SESSION['_flash'][$type]);
            return $msg;
        }
        return null;
    }

    public static function getAllFlashes(): array
    {
        $flashes = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $flashes;
    }

    public static function setOld(array $data): void
    {
        // Não salvar senhas na sessão
        unset($data['senha'], $data['password'], $data['confirmar_senha'], $data['csrf_token']);
        $_SESSION['_old'] = $data;
    }

    public static function getOld(string $key, string $default = ''): string
    {
        $val = $_SESSION['_old'][$key] ?? $default;
        return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
    }

    public static function clearOld(): void
    {
        unset($_SESSION['_old']);
    }
}
