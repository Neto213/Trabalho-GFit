<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\Database;
use PDO;

class UserRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT u.*, r.nome AS rede_nome, r.status AS rede_status
            FROM usuarios u
            INNER JOIN redes r ON r.id = u.rede_id
            WHERE LOWER(u.email) = LOWER(:email)
            LIMIT 1
        ");
        $stmt->execute(['email' => trim($email)]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT u.*, r.nome AS rede_nome, r.status AS rede_status
            FROM usuarios u
            INNER JOIN redes r ON r.id = u.rede_id
            WHERE u.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function getSessionVersion(int $id): ?int
    {
        $stmt = $this->pdo->prepare("SELECT session_version, status FROM usuarios WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $res = $stmt->fetch();

        return $res ? (int)$res['session_version'] : null;
    }

    public function updatePasswordAndRevokeSessions(int $userId, string $newPasswordHash): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE usuarios
            SET senha_hash = :hash,
                session_version = session_version + 1,
                updated_at = NOW()
            WHERE id = :id
        ");
        return $stmt->execute([
            'hash' => $newPasswordHash,
            'id' => $userId,
        ]);
    }

    public function getLinkedUnits(int $userId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT un.id, un.nome, un.status, un.rede_id
            FROM unidades un
            INNER JOIN usuario_unidades uu ON uu.unidade_id = un.id
            WHERE uu.usuario_id = :usuario_id
            ORDER BY un.nome ASC
        ");
        $stmt->execute(['usuario_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO usuarios (rede_id, nome, email, senha_hash, perfil, status, session_version)
            VALUES (:rede_id, :nome, :email, :senha_hash, :perfil, :status, 1)
        ");
        $stmt->execute([
            'rede_id' => $data['rede_id'],
            'nome' => $data['nome'],
            'email' => strtolower(trim($data['email'])),
            'senha_hash' => $data['senha_hash'],
            'perfil' => $data['perfil'],
            'status' => $data['status'] ?? 'ativo',
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function linkUnit(int $userId, int $unitId): bool
    {
        $stmt = $this->pdo->prepare("INSERT IGNORE INTO usuario_unidades (usuario_id, unidade_id) VALUES (:uid, :unid)");
        return $stmt->execute(['uid' => $userId, 'unid' => $unitId]);
    }

    public function createWithUnit(array $data, int $unitId): int
    {
        $this->pdo->beginTransaction();
        try {
            $userId = $this->create($data);
            $this->linkUnit($userId, $unitId);
            $this->pdo->commit();
            return $userId;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function enableMfa(int $userId, string $encryptedSecret): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE usuarios
            SET mfa_enabled = 1,
                mfa_secret = :secret,
                mfa_enforced = 1,
                mfa_enforced_at = NOW(),
                updated_at = NOW()
            WHERE id = :id
        ");
        return $stmt->execute([
            'secret' => $encryptedSecret,
            'id' => $userId,
        ]);
    }

    public function disableMfa(int $userId): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE usuarios
            SET mfa_enabled = 0,
                mfa_secret = NULL,
                updated_at = NOW()
            WHERE id = :id
        ");
        return $stmt->execute(['id' => $userId]);
    }

    public function updatePasswordHash(int $userId, string $newHash): bool
    {
        $stmt = $this->pdo->prepare("UPDATE usuarios SET senha_hash = :hash, updated_at = NOW() WHERE id = :id");
        return $stmt->execute(['hash' => $newHash, 'id' => $userId]);
    }
}

