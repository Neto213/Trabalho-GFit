<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\Database;
use PDO;

class PasswordResetRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    public function create(int $userId, string $tokenHash, string $expiresAt): bool
    {
        // Invalida tokens anteriores não utilizados do mesmo usuário
        $this->invalidateUserTokens($userId);

        $stmt = $this->pdo->prepare("
            INSERT INTO recuperacao_senha (usuario_id, token_hash, expires_at, created_at)
            VALUES (:uid, :thash, :expires, NOW())
        ");
        return $stmt->execute([
            'uid' => $userId,
            'thash' => $tokenHash,
            'expires' => $expiresAt,
        ]);
    }

    public function findValidByTokenHash(string $tokenHash): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT r.*, u.email, u.nome, u.status AS usuario_status
            FROM recuperacao_senha r
            INNER JOIN usuarios u ON u.id = r.usuario_id
            WHERE r.token_hash = :thash
              AND r.used_at IS NULL
              AND r.expires_at > NOW()
            LIMIT 1
        ");
        $stmt->execute(['thash' => $tokenHash]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function markAsUsed(int $id): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE recuperacao_senha
            SET used_at = NOW()
            WHERE id = :id AND used_at IS NULL
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function invalidateUserTokens(int $userId): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE recuperacao_senha
            SET used_at = NOW()
            WHERE usuario_id = :uid AND used_at IS NULL
        ");
        return $stmt->execute(['uid' => $userId]);
    }
}
