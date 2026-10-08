<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\Database;
use PDO;

class MfaRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    /**
     * Salva novos códigos de recuperação com hash, revogando os anteriores não utilizados.
     * @param int $userId
     * @param array<string> $codeHashes
     */
    public function saveRecoveryCodes(int $userId, array $codeHashes): void
    {
        $this->pdo->beginTransaction();
        try {
            // Remover códigos anteriores não utilizados
            $stmtDel = $this->pdo->prepare("DELETE FROM mfa_recovery_codes WHERE usuario_id = :uid");
            $stmtDel->execute(['uid' => $userId]);

            $stmtIns = $this->pdo->prepare("
                INSERT INTO mfa_recovery_codes (usuario_id, code_hash, created_at)
                VALUES (:uid, :hash, NOW())
            ");

            foreach ($codeHashes as $hash) {
                $stmtIns->execute(['uid' => $userId, 'hash' => $hash]);
            }

            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Valida e consome atomicamente um código de recuperação de uso único.
     */
    public function verifyAndConsumeRecoveryCode(int $userId, string $rawCode): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT id, code_hash
            FROM mfa_recovery_codes
            WHERE usuario_id = :uid AND used_at IS NULL
        ");
        $stmt->execute(['uid' => $userId]);
        $rows = $stmt->fetchAll();

        $cleanCode = strtoupper(trim(str_replace([' ', '-'], '', $rawCode)));

        foreach ($rows as $row) {
            if (password_verify($cleanCode, $row['code_hash'])) {
                // Consumo atômico
                $stmtUpdate = $this->pdo->prepare("
                    UPDATE mfa_recovery_codes
                    SET used_at = NOW()
                    WHERE id = :id AND used_at IS NULL
                ");
                $stmtUpdate->execute(['id' => $row['id']]);

                if ($stmtUpdate->rowCount() > 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Retorna a quantidade de códigos de recuperação ainda disponíveis (não utilizados).
     */
    public function countRemainingRecoveryCodes(int $userId): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM mfa_recovery_codes
            WHERE usuario_id = :uid AND used_at IS NULL
        ");
        $stmt->execute(['uid' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Registra o consumo atômico de um código TOTP para evitar reutilização (anti-replay).
     * Retorna true se registrado com sucesso, false se já foi consumido nessa janela temporal.
     */
    public function recordTotpCodeUsed(int $userId, string $code, int $periodTimestamp): bool
    {
        $codeHash = hash('sha256', $code . ':' . $periodTimestamp);

        // Limpeza periódica de códigos antigos (mais de 15 minutos atrás)
        try {
            $stmtClean = $this->pdo->prepare("DELETE FROM mfa_used_codes WHERE consumed_at < (NOW() - INTERVAL 15 MINUTE)");
            $stmtClean->execute();
        } catch (\Throwable) {
            // Falha silenciosa de limpeza não bloqueia a autenticação
        }

        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO mfa_used_codes (usuario_id, code_hash, period_timestamp, consumed_at)
                VALUES (:uid, :chash, :period, NOW())
            ");
            return $stmt->execute([
                'uid' => $userId,
                'chash' => $codeHash,
                'period' => $periodTimestamp,
            ]);
        } catch (\PDOException $e) {
            // Violação de chave única = código já consumido neste período
            if ($e->getCode() === '23000') {
                return false;
            }
            throw $e;
        }
    }

    /**
     * Limpa os dados de MFA e códigos de recuperação quando o MFA é desativado.
     */
    public function clearUserMfaData(int $userId): void
    {
        $stmtCodes = $this->pdo->prepare("DELETE FROM mfa_recovery_codes WHERE usuario_id = :uid");
        $stmtCodes->execute(['uid' => $userId]);

        $stmtUsed = $this->pdo->prepare("DELETE FROM mfa_used_codes WHERE usuario_id = :uid");
        $stmtUsed->execute(['uid' => $userId]);
    }
}
