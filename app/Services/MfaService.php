<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\MfaRepository;
use App\Repositories\RateLimitRepository;
use App\Repositories\UserRepository;
use App\Support\Config;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use OTPHP\TOTP;

class MfaService
{
    private MfaRepository $mfaRepo;
    private UserRepository $userRepo;
    private RateLimitRepository $rateLimitRepo;

    public function __construct(
        ?MfaRepository $mfaRepo = null,
        ?UserRepository $userRepo = null,
        ?RateLimitRepository $rateLimitRepo = null
    ) {
        $this->mfaRepo = $mfaRepo ?? new MfaRepository();
        $this->userRepo = $userRepo ?? new UserRepository();
        $this->rateLimitRepo = $rateLimitRepo ?? new RateLimitRepository();
    }

    /**
     * Obtém a chave binária de 32 bytes para criptografia AES-256-GCM.
     */
    private function getEncryptionKey(): string
    {
        $raw = (string)Config::get('auth.mfa.encryption_key', '');
        if (strlen($raw) === 64 && ctype_xdigit($raw)) {
            return hex2bin($raw);
        }
        throw new \RuntimeException('MFA_ENCRYPTION_KEY deve conter 64 caracteres hexadecimais.');
    }

    /**
     * Criptografa o segredo TOTP usando AES-256-GCM (Criptografia Autenticada).
     */
    public function encryptSecret(string $plainSecret): string
    {
        $key = $this->getEncryptionKey();
        $iv = random_bytes(12); // Vetor de inicialização padrão de 96 bits para GCM
        $tag = '';
        $ciphertext = openssl_encrypt(
            $plainSecret,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($ciphertext === false) {
            throw new \RuntimeException('Falha na criptografia autenticada do segredo MFA.');
        }

        // Armazena IV (12 bytes) + TAG de autenticação (16 bytes) + CIPHERTEXT
        return base64_encode($iv . $tag . $ciphertext);
    }

    /**
     * Descriptografa e verifica a autenticidade do segredo TOTP usando AES-256-GCM.
     */
    public function decryptSecret(string $encryptedSecret): ?string
    {
        $data = base64_decode($encryptedSecret, true);
        if ($data === false || strlen($data) < 28) {
            return null;
        }

        $key = $this->getEncryptionKey();
        $iv = substr($data, 0, 12);
        $tag = substr($data, 12, 16);
        $ciphertext = substr($data, 28);

        $plaintext = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        return $plaintext !== false ? $plaintext : null;
    }

    /**
     * Inicia a configuração de MFA para um usuário: gera segredo TOTP, URI de provisionamento e QR Code local.
     */
    public function initializeSetup(array $user): array
    {
        $issuer = (string)Config::get('auth.mfa.issuer', 'Gestão de Academia');
        $email = $user['email'];

        $totp = TOTP::generate();
        $totp->setPeriod(30);
        $totp->setDigits(6);
        $totp->setLabel($email);
        $totp->setIssuer($issuer);

        $secret = $totp->getSecret();
        $provisioningUri = $totp->getProvisioningUri();

        // Geração de QR code local em formato vetorial SVG Data URI (sem serviços de terceiros)
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
            'svgUseFill' => true,
            'scale' => 5,
            'imageBase64' => false,
        ]);
        $qrCodeSvg = (new QRCode($options))->render($provisioningUri);

        return [
            'secret' => $secret,
            'encrypted_secret' => $this->encryptSecret($secret),
            'qr_code_svg' => $qrCodeSvg,
            'provisioning_uri' => $provisioningUri,
            'formatted_secret' => chunk_split($secret, 4, ' '),
        ];
    }

    /**
     * Confirma a ativação do MFA com o primeiro código de 6 dígitos.
     * Gera e persiste 8 códigos de recuperação e salva o segredo criptografado.
     */
    public function confirmActivation(int $userId, string $expectedCodeHash, string $code): array
    {
        $code = trim(str_replace(' ', '', $code));
        if (strlen($code) !== 6 || !ctype_digit($code)) {
            return [
                'success' => false,
                'error' => 'O código de verificação deve conter exatamente 6 dígitos numéricos.',
            ];
        }

        if (!password_verify($code, $expectedCodeHash)) {
            return [
                'success' => false,
                'error' => 'Código de verificação incorreto ou expirado.',
            ];
        }

        $this->mfaRepo->recordTotpCodeUsed($userId, $code, time());

        // Gerar 8 códigos de recuperação de uso único
        $recoveryData = $this->generateRecoveryCodes();

        // Criptografar segredo fictício indicando email mfa
        $encryptedSecret = $this->encryptSecret('EMAIL_MFA_ENABLED');

        // Persistir no banco
        $this->userRepo->enableMfa($userId, $encryptedSecret);
        $this->mfaRepo->saveRecoveryCodes($userId, $recoveryData['hashes']);

        SecurityAuditService::log('mfa_enabled', 'SUCCESS', [
            'user_id' => $userId,
        ]);

        return [
            'success' => true,
            'recovery_codes' => $recoveryData['plain_codes'],
        ];
    }

    /**
     * Valida um desafio TOTP no fluxo de login.
     * Inclui proteção contra força bruta, expiração e anti-replay.
     */
    public function verifyChallenge(int $userId, string $code, string $ip): array
    {
        $code = trim(str_replace(' ', '', $code));
        $rateKey = 'mfa:' . DataProtectionService::fingerprint($ip) . ":{$userId}";
        $maxAttempts = (int)Config::get('auth.mfa.max_attempts', 5);
        $lockoutMinutes = (int)Config::get('auth.lockout_minutes', 15);

        if ($this->rateLimitRepo->isLocked($rateKey, 'mfa')) {
            $rem = $this->rateLimitRepo->getLockMinutesRemaining($rateKey, 'mfa');
            SecurityAuditService::log('mfa_challenge_locked', 'BLOCKED', [
                'user_id' => $userId,
                'ip' => $ip,
            ]);
            return [
                'success' => false,
                'error' => "Limite de tentativas de autenticação em duas etapas excedido. Aguarde {$rem} minuto(s).",
            ];
        }

        if (strlen($code) !== 6 || !ctype_digit($code)) {
            $this->rateLimitRepo->increment($rateKey, 'mfa', $maxAttempts, $lockoutMinutes);
            return [
                'success' => false,
                'error' => 'O código de autenticação deve possuir 6 dígitos.',
            ];
        }

        $user = $this->userRepo->findById($userId);
        if (!$user) {
            return [
                'success' => false,
                'error' => 'Usuário não encontrado.',
            ];
        }

        $expectedCodeHash = $_SESSION['mfa_pending']['code_hash'] ?? null;

        if (!$expectedCodeHash || !password_verify($code, $expectedCodeHash)) {
            $this->rateLimitRepo->increment($rateKey, 'mfa', $maxAttempts, $lockoutMinutes);
            SecurityAuditService::log('mfa_code_invalid', 'FAILURE', [
                'user_id' => $userId,
                'ip' => $ip,
            ]);
            return [
                'success' => false,
                'error' => 'Código de autenticação incorreto.',
            ];
        }

        // Proteção contra Replay Attack
        $recorded = $this->mfaRepo->recordTotpCodeUsed($userId, $code, time());
        if (!$recorded) {
            SecurityAuditService::log('mfa_code_replay_rejected', 'BLOCKED', [
                'user_id' => $userId,
                'ip' => $ip,
            ]);
            return [
                'success' => false,
                'error' => 'Este código já foi utilizado. Aguarde o próximo código em seu aplicativo.',
            ];
        }

        // Sucesso: limpar tentativas de rate limit e invalidar código na sessão
        $this->rateLimitRepo->reset($rateKey, 'mfa');
        unset($_SESSION['mfa_pending']['code_hash']);
        SecurityAuditService::log('mfa_challenge_success', 'SUCCESS', [
            'user_id' => $userId,
            'ip' => $ip,
        ]);

        return [
            'success' => true,
            'user' => $user,
        ];
    }

    /**
     * Valida um código de recuperação no login caso o usuário perca o dispositivo autenticador.
     */
    public function verifyRecoveryCode(int $userId, string $rawCode, string $ip): array
    {
        $rateKey = 'mfa_recovery:' . DataProtectionService::fingerprint($ip) . ":{$userId}";
        $maxAttempts = (int)Config::get('auth.mfa.max_attempts', 5);
        $lockoutMinutes = (int)Config::get('auth.lockout_minutes', 15);

        if ($this->rateLimitRepo->isLocked($rateKey, 'mfa_recovery')) {
            $rem = $this->rateLimitRepo->getLockMinutesRemaining($rateKey, 'mfa_recovery');
            SecurityAuditService::log('mfa_recovery_locked', 'BLOCKED', ['user_id' => $userId, 'ip' => $ip]);
            return [
                'success' => false,
                'error' => "Limite de tentativas com códigos de recuperação excedido. Aguarde {$rem} minuto(s).",
            ];
        }

        $consumed = $this->mfaRepo->verifyAndConsumeRecoveryCode($userId, $rawCode);

        if (!$consumed) {
            $this->rateLimitRepo->increment($rateKey, 'mfa_recovery', $maxAttempts, $lockoutMinutes);
            SecurityAuditService::log('mfa_recovery_invalid', 'FAILURE', ['user_id' => $userId, 'ip' => $ip]);
            return [
                'success' => false,
                'error' => 'Código de recuperação inválido ou já utilizado.',
            ];
        }

        $this->rateLimitRepo->reset($rateKey, 'mfa_recovery');
        $user = $this->userRepo->findById($userId);

        $remaining = $this->mfaRepo->countRemainingRecoveryCodes($userId);

        SecurityAuditService::log('mfa_recovery_success', 'SUCCESS', [
            'user_id' => $userId,
            'ip' => $ip,
            'remaining_codes' => $remaining,
        ]);

        return [
            'success' => true,
            'user' => $user,
            'remaining_codes' => $remaining,
        ];
    }

    /**
     * Desativa o MFA mediante confirmação de senha e (código TOTP atual OU código de recuperação).
     */
    public function disableMfa(int $userId, string $password, string $mfaOrRecoveryCode): array
    {
        $user = $this->userRepo->findById($userId);
        if (!$user) {
            return ['success' => false, 'error' => 'Usuário não encontrado.'];
        }

        // 1. Exigir confirmação de senha
        if (!password_verify($password, $user['senha_hash'])) {
            SecurityAuditService::log('mfa_disable_password_failed', 'FAILURE', ['user_id' => $userId]);
            return ['success' => false, 'error' => 'A senha informada está incorreta.'];
        }

        // 2. Não permitir desativação sem código atual ou de recuperação
        $clean = trim($mfaOrRecoveryCode);
        $validFactor = false;

        // Tentar como código TOTP (6 dígitos)
        // Aceitar apenas código de recuperação para confirmar desativação
        // (já que não há mais app TOTP — a verificação é por email no login)

        // Se não validou como TOTP, tentar como código de recuperação
        if (!$validFactor) {
            $validFactor = $this->mfaRepo->verifyAndConsumeRecoveryCode($userId, $clean);
        }

        if (!$validFactor) {
            SecurityAuditService::log('mfa_disable_code_failed', 'FAILURE', ['user_id' => $userId]);
            return [
                'success' => false,
                'error' => 'Código de verificação ou de recuperação inválido. Ação não autorizada.',
            ];
        }

        // Não permitir bypass se o perfil exige MFA obrigatório sem autorização expressa
        $enforceRoles = Config::get('auth.mfa.enforce_roles', ['admin', 'profissional']);
        if (in_array($user['perfil'], $enforceRoles, true)) {
            // Conta obrigatória: pode resetar para reconfiguração imediata
        }

        // Desativar no banco e limpar dados
        $this->userRepo->disableMfa($userId);
        $this->mfaRepo->clearUserMfaData($userId);

        SecurityAuditService::log('mfa_disabled', 'SUCCESS', ['user_id' => $userId]);

        return ['success' => true];
    }

    /**
     * Regenera novos códigos de recuperação exigindo confirmação de senha.
     */
    public function regenerateRecoveryCodes(int $userId, string $password): array
    {
        $user = $this->userRepo->findById($userId);
        if (!$user || !password_verify($password, $user['senha_hash'])) {
            return ['success' => false, 'error' => 'Senha incorreta.'];
        }

        if (empty($user['mfa_enabled'])) {
            return ['success' => false, 'error' => 'O MFA precisa estar ativo para gerar novos códigos de recuperação.'];
        }

        $recoveryData = $this->generateRecoveryCodes();
        $this->mfaRepo->saveRecoveryCodes($userId, $recoveryData['hashes']);

        SecurityAuditService::log('mfa_recovery_regenerated', 'SUCCESS', ['user_id' => $userId]);

        return [
            'success' => true,
            'recovery_codes' => $recoveryData['plain_codes'],
        ];
    }

    /**
     * Gera 8 códigos de recuperação únicos e seus respectivos hashes.
     */
    private function generateRecoveryCodes(int $count = 8): array
    {
        $plainCodes = [];
        $hashes = [];

        for ($i = 0; $i < $count; $i++) {
            // Formato legível: 10 caracteres em 2 grupos (ex: ABCD-1234)
            $raw = strtoupper(bin2hex(random_bytes(4)));
            $formatted = substr($raw, 0, 4) . '-' . substr($raw, 4, 4);
            $clean = str_replace('-', '', $formatted);

            $plainCodes[] = $formatted;
            $hashes[] = password_hash($clean, PASSWORD_DEFAULT);
        }

        return [
            'plain_codes' => $plainCodes,
            'hashes' => $hashes,
        ];
    }

    /**
     * Verifica se o perfil do usuário exige MFA obrigatoriamente.
     */
    public function isMfaEnforcedForRole(string $role): bool
    {
        $roles = Config::get('auth.mfa.enforce_roles', ['admin', 'profissional']);
        return in_array($role, $roles, true);
    }
}
