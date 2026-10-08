<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\Config;
use RuntimeException;

final class DataProtectionService
{
    private const PREFIX = 'gfit:v1:';

    private function key(): string
    {
        $raw = trim((string)Config::get('privacy.encryption_key', ''));
        if (strlen($raw) !== 64 || !ctype_xdigit($raw)) {
            throw new RuntimeException('DATA_ENCRYPTION_KEY deve conter 64 caracteres hexadecimais.');
        }

        return hex2bin($raw);
    }

    public function encrypt(array $data, string $context): string
    {
        $plain = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plain, 'aes-256-gcm', $this->key(), OPENSSL_RAW_DATA, $iv, $tag, $context);
        if ($cipher === false) {
            throw new RuntimeException('Não foi possível proteger os dados pessoais.');
        }

        return self::PREFIX . base64_encode($iv . $tag . $cipher);
    }

    public function decrypt(string $payload, string $context): array
    {
        if (!str_starts_with($payload, self::PREFIX)) {
            throw new RuntimeException('Formato de dado protegido inválido.');
        }
        $raw = base64_decode(substr($payload, strlen(self::PREFIX)), true);
        if ($raw === false || strlen($raw) < 29) {
            throw new RuntimeException('Dado protegido corrompido.');
        }
        $plain = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', $this->key(), OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16), $context);
        if ($plain === false) {
            throw new RuntimeException('Falha na autenticação do dado protegido.');
        }

        return json_decode($plain, true, 32, JSON_THROW_ON_ERROR);
    }

    public static function fingerprint(string $value): string
    {
        $key = trim((string)Config::get('privacy.fingerprint_key', ''));
        if ($key === '') {
            $key = trim((string)Config::get('privacy.encryption_key', ''));
        }
        if ($key === '') {
            throw new RuntimeException('PRIVACY_FINGERPRINT_KEY não configurada.');
        }

        return substr(hash_hmac('sha256', mb_strtolower(trim($value)), $key), 0, 32);
    }
}
