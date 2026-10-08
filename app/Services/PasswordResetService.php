<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\PasswordResetRepository;
use App\Repositories\RateLimitRepository;
use App\Repositories\UserRepository;
use App\Support\Config;

class PasswordResetService
{
    private UserRepository $userRepo;
    private PasswordResetRepository $resetRepo;
    private RateLimitRepository $rateLimitRepo;
    private MailService $mailService;

    public function __construct(
        ?UserRepository $userRepo = null,
        ?PasswordResetRepository $resetRepo = null,
        ?RateLimitRepository $rateLimitRepo = null,
        ?MailService $mailService = null
    ) {
        $this->userRepo = $userRepo ?? new UserRepository();
        $this->resetRepo = $resetRepo ?? new PasswordResetRepository();
        $this->rateLimitRepo = $rateLimitRepo ?? new RateLimitRepository();
        $this->mailService = $mailService ?? new MailService();
    }

    public function requestReset(string $email, string $ip): array
    {
        $email = strtolower(trim($email));
        $ipId = DataProtectionService::fingerprint($ip);
        $accountId = DataProtectionService::fingerprint($email);
        $pairKey = "recovery:{$ipId}:{$accountId}";
        $ipKey = "recovery_ip:{$ipId}";
        $accountKey = "recovery_acc:{$accountId}";

        $maxAttempts = Config::get('auth.password_reset.max_attempts', 3);
        $maxIpAttempts = Config::get('auth.ip_max_attempts', 20);
        $lockoutMinutes = Config::get('auth.lockout_minutes', 15);

        // Mensagem genérica anti-enumeração de usuários
        $genericMessage = 'Se o e-mail informado estiver cadastrado e ativo em nosso sistema, as instruções para redefinição de senha foram enviadas.';

        // Verificar limite de requisições de recuperação em múltiplas camadas
        if ($this->rateLimitRepo->isLocked($pairKey, 'recuperacao') ||
            $this->rateLimitRepo->isLocked($ipKey, 'recuperacao_ip') ||
            $this->rateLimitRepo->isLocked($accountKey, 'recuperacao_acc')) {

            $remPair = $this->rateLimitRepo->getLockMinutesRemaining($pairKey, 'recuperacao');
            $remIp = $this->rateLimitRepo->getLockMinutesRemaining($ipKey, 'recuperacao_ip');
            $remAcc = $this->rateLimitRepo->getLockMinutesRemaining($accountKey, 'recuperacao_acc');
            $remaining = max($remPair, $remIp, $remAcc, 1);

            SecurityAuditService::log('password_reset_lockout', 'BLOCKED', [
                'email' => $email,
                'ip' => $ip,
                'remaining_minutes' => $remaining,
            ]);

            return [
                'success' => false,
                'message' => "Muitas solicitações recentes. Por favor, aguarde {$remaining} minuto(s) antes de tentar novamente.",
            ];
        }

        $this->rateLimitRepo->increment($pairKey, 'recuperacao', $maxAttempts, $lockoutMinutes);
        $this->rateLimitRepo->increment($ipKey, 'recuperacao_ip', $maxIpAttempts, $lockoutMinutes);
        $this->rateLimitRepo->increment($accountKey, 'recuperacao_acc', $maxAttempts, $lockoutMinutes);

        SecurityAuditService::log('password_reset_requested', 'SUCCESS', ['email' => $email, 'ip' => $ip]);


        $user = $this->userRepo->findByEmail($email);

        // Se usuário não existe ou está inativo, retorne a resposta genérica sem processar
        if (!$user || $user['status'] !== 'ativo' || $user['rede_status'] !== 'ativo') {
            return [
                'success' => true,
                'message' => $genericMessage,
            ];
        }

        // Gerar token criptograficamente seguro
        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);

        $timeoutMinutes = Config::get('auth.password_reset.timeout_minutes', 30);
        $expiresAt = date('Y-m-d H:i:s', time() + ($timeoutMinutes * 60));

        // Salvar token no banco
        $this->resetRepo->create((int)$user['id'], $tokenHash, $expiresAt);

        // Montar URL confiável utilizando a base configurada
        $baseUrl = rtrim(Config::get('app.url', 'http://localhost/GFit/public'), '/');
        $resetUrl = "{$baseUrl}/redefinir-senha?token={$rawToken}";

        // Enviar e-mail
        $this->mailService->sendPasswordResetEmail($user['email'], $user['nome'], $resetUrl);

        return [
            'success' => true,
            'message' => $genericMessage,
        ];
    }

    public function validateToken(string $rawToken): ?array
    {
        if (trim($rawToken) === '') {
            return null;
        }

        $tokenHash = hash('sha256', $rawToken);
        return $this->resetRepo->findValidByTokenHash($tokenHash);
    }

    public function resetPassword(string $rawToken, string $newPassword, string $confirmPassword): array
    {
        if (strlen($newPassword) < 12) {
            return [
                'success' => false,
                'error' => 'A nova senha deve possuir pelo menos 12 caracteres.',
            ];
        }

        if ($newPassword !== $confirmPassword) {
            return [
                'success' => false,
                'error' => 'A confirmação da senha não confere com a nova senha digitada.',
            ];
        }

        $record = $this->validateToken($rawToken);

        if (!$record) {
            return [
                'success' => false,
                'error' => 'O link de recuperação é inválido, expirou ou já foi utilizado.',
            ];
        }

        // Consumo atômico do token
        $used = $this->resetRepo->markAsUsed((int)$record['id']);
        if (!$used) {
            return [
                'success' => false,
                'error' => 'Este link de recuperação já foi processado anteriormente.',
            ];
        }

        // Atualizar senha e revogar todas as sessões anteriores incrementando session_version
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->userRepo->updatePasswordAndRevokeSessions((int)$record['usuario_id'], $newHash);

        return [
            'success' => true,
            'error' => null,
            'message' => 'Sua senha foi redefinida com sucesso! Você já pode efetuar login.',
        ];
    }
}
