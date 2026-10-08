<?php

declare(strict_types=1);

namespace App\Support;

class SecurityHeaders
{
    private static ?string $nonce = null;

    public static function nonce(): string
    {
        return self::$nonce ??= base64_encode(random_bytes(18));
    }

    /**
     * Aplica cabeçalhos globais de segurança HTTP em todas as respostas.
     */
    public static function apply(): void
    {
        if (headers_sent()) {
            return;
        }

        // 1. Proteção contra clickjacking
        header('X-Frame-Options: DENY');

        // 2. Prevenção de MIME-sniffing
        header('X-Content-Type-Options: nosniff');

        // 3. Política de Referrer estrita
        header('Referrer-Policy: strict-origin-when-cross-origin');

        // 4. Desativação de permissões desnecessárias do navegador
        header('Permissions-Policy: geolocation=(), camera=(), microphone=(), payment=(), usb=()');

        // 5. CSP restrita: bibliotecas locais e nonce único por resposta.
        $nonce = self::nonce();
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}'",
            "style-src 'self' 'unsafe-inline'",
            "font-src 'self' data:",
            "img-src 'self' data: blob:",
            "connect-src 'self'",
            "object-src 'none'",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ];
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            $csp[] = 'upgrade-insecure-requests';
        }
        header('Content-Security-Policy: ' . implode('; ', $csp));

        // 6. HSTS (somente quando trafegando via HTTPS)
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
    }

    /**
     * Aplica cabeçalhos para impedir cache de páginas privadas ou sensíveis.
     */
    public static function applyPrivateCacheHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private');
        header('Pragma: no-cache');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    }
}
