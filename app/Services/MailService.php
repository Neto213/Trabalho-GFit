<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\Config;

class MailService
{
    public function sendPasswordResetEmail(string $toEmail, string $userName, string $resetUrl): bool
    {
        $subject = "Recuperação de Senha - Gestão de Academia";
        $fromAddress = Config::get('auth.mail.from_address', 'noreply@gestaoacademia.local');
        $fromName = Config::get('auth.mail.from_name', 'Gestão de Academia');
        $driver = Config::get('auth.mail.driver', 'outbox');
        $safeName = htmlspecialchars($userName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $htmlBody = <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>{$subject}</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #1e293b; padding: 24px;">
    <div style="max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0; padding: 32px;">
        <h2 style="margin-top: 0; color: #0f172a;">Recuperação de Acesso</h2>
        <p>Olá, <strong>{$safeName}</strong>,</p>
        <p>Recebemos uma solicitação para redefinir a senha da sua conta no sistema <strong>Gestão de Academia</strong>.</p>
        <p>Para prosseguir e cadastrar uma nova senha, utilize o botão abaixo:</p>
        <div style="margin: 30px 0; text-align: center;">
            <a href="{$safeUrl}" style="background-color: #2563eb; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">Redefinir Minha Senha</a>
        </div>
        <p style="font-size: 13px; color: #64748b;">Este link possui validade de 30 minutos e só poderá ser utilizado uma única vez.</p>
        <p style="font-size: 13px; color: #64748b;">Se você não realizou esta solicitação, por favor desconsidere este e-mail. Sua senha atual continuará inalterada.</p>
        <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 24px 0;">
        <p style="font-size: 11px; color: #94a3b8; word-break: break-all;">Link direto: {$safeUrl}</p>
    </div>
</body>
</html>
HTML;

        $textBody = "Olá, {$userName},\n\nRecebemos uma solicitação de redefinição de senha para sua conta.\n"
            . "Acesse o link a seguir para criar sua nova senha:\n{$resetUrl}\n\n"
            . "Este link expira em 30 minutos e só pode ser usado uma vez.\nSe você não solicitou, ignore esta mensagem.";

        if ($driver === 'smtp') {
            return $this->sendViaSmtp($toEmail, $subject, $htmlBody, $fromAddress, $fromName);
        }

        // Modo Outbox local (Desenvolvimento)
        return $this->writeToOutbox($toEmail, $subject, $htmlBody, $textBody, $fromAddress, $fromName);
    }

    public function sendMfaCodeEmail(string $toEmail, string $userName, string $code): bool
    {
        $subject = "Seu código de acesso MFA - Gestão de Academia";
        $fromAddress = Config::get('auth.mail.from_address', 'noreply@gestaoacademia.local');
        $fromName = Config::get('auth.mail.from_name', 'Gestão de Academia');
        $driver = Config::get('auth.mail.driver', 'outbox');
        $safeName = htmlspecialchars($userName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeCode = htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $htmlBody = <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>{$subject}</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #1e293b; padding: 24px;">
    <div style="max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0; padding: 32px;">
        <h2 style="margin-top: 0; color: #0f172a;">Código de Verificação</h2>
        <p>Olá, <strong>{$safeName}</strong>,</p>
        <p>Utilize o código de 6 dígitos abaixo para confirmar sua ação no sistema <strong>Gestão de Academia</strong>.</p>
        <div style="margin: 30px 0; text-align: center;">
            <span style="background-color: #f1f5f9; color: #0f172a; padding: 16px 32px; border-radius: 6px; font-weight: bold; font-size: 24px; letter-spacing: 4px;">{$safeCode}</span>
        </div>
        <p style="font-size: 13px; color: #64748b;">Este código é válido por tempo limitado.</p>
    </div>
</body>
</html>
HTML;

        $textBody = "Olá, {$userName},\n\nSeu código de verificação MFA é: {$code}\n";

        if ($driver === 'smtp') {
            return $this->sendViaSmtp($toEmail, $subject, $htmlBody, $fromAddress, $fromName);
        }

        return $this->writeToOutbox($toEmail, $subject, $htmlBody, $textBody, $fromAddress, $fromName);
    }

    private function writeToOutbox(string $to, string $subject, string $htmlBody, string $textBody, string $from, string $fromName): bool
    {
        $outboxDir = __DIR__ . '/../../storage/mail';
        if (!is_dir($outboxDir)) {
            mkdir($outboxDir, 0755, true);
        }

        $timestamp = date('Y-m-d_H-i-s');
        $random = bin2hex(random_bytes(4));
        $filename = "{$outboxDir}/mail_{$timestamp}_{$random}.html";

        $envelope = "<!-- Para: {$to} | Assunto: {$subject} | De: {$fromName} <{$from}> | Data: " . date('c') . " -->\n";
        $content = $envelope . $htmlBody;

        return file_put_contents($filename, $content) !== false;
    }

    private function sendViaSmtp(string $to, string $subject, string $htmlBody, string $from, string $fromName): bool
    {
        foreach ([$to, $subject, $from, $fromName] as $headerValue) {
            if (preg_match('/[\r\n]/', $headerValue)) {
                return false;
            }
        }
        if (!filter_var($to, FILTER_VALIDATE_EMAIL) || !filter_var($from, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        // Envio SMTP configurado com cabeçalhos padrão
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=utf-8',
            "From: {$fromName} <{$from}>",
            "Reply-To: {$from}",
            'X-Mailer: PHP/' . phpversion(),
        ];

        return @mail($to, $subject, $htmlBody, implode("\r\n", $headers));
    }
}
