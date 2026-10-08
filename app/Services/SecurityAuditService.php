<?php

declare(strict_types=1);

namespace App\Services;

class SecurityAuditService
{
    private static ?string $logFile = null;

    private static function getLogPath(): string
    {
        if (self::$logFile === null) {
            $dir = __DIR__ . '/../../storage/logs';
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            self::$logFile = $dir . '/security.log';
        }
        return self::$logFile;
    }

    /**
     * Registra um evento de auditoria de segurança estruturado.
     * NUNCA grava senhas, tokens de recuperação, segredos TOTP ou dados clínicos.
     */
    public static function log(string $event, string $status, array $context = []): void
    {
        $ip = DataProtectionService::fingerprint(AuthService::getClientIp());
        $timestamp = date('Y-m-d H:i:s');

        // Sanitização estrita do contexto para evitar vazamento de segredos
        $sanitized = [];
        foreach ($context as $key => $val) {
            $lowerKey = strtolower((string)$key);
            if (str_contains($lowerKey, 'senha') || 
                str_contains($lowerKey, 'password') || 
                str_contains($lowerKey, 'secret') || 
                str_contains($lowerKey, 'token') ||
                str_contains($lowerKey, 'key') ||
                str_contains($lowerKey, 'code')) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (str_contains($lowerKey, 'email') || $lowerKey === 'ip') {
                $sanitized[$key] = is_scalar($val) ? DataProtectionService::fingerprint((string)$val) : '[REDACTED]';
            } elseif (is_scalar($val) || $val === null) {
                $sanitized[$key] = $val;
            } else {
                $sanitized[$key] = json_encode($val);
            }
        }

        $line = sprintf(
            "[%s] [%s] [%s] CLIENT=%s CONTEXT=%s\n",
            $timestamp,
            strtoupper($status),
            strtoupper($event),
            $ip,
            json_encode($sanitized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        @file_put_contents(self::getLogPath(), $line, FILE_APPEND | LOCK_EX);
    }
}
