<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\Database;
use PDO;

class UnitRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    public function findByNetwork(int $networkId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM unidades WHERE rede_id = :rede_id ORDER BY nome ASC");
        $stmt->execute(['rede_id' => $networkId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM unidades WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function getAllActiveWithNetwork(): array
    {
        $stmt = $this->pdo->query("
            SELECT u.id, u.nome AS unidade_nome, u.rede_id, r.nome AS rede_nome
            FROM unidades u
            INNER JOIN redes r ON r.id = u.rede_id
            WHERE u.status = 'ativo' AND r.status = 'ativo'
            ORDER BY r.nome ASC, u.nome ASC
        ");
        return $stmt->fetchAll() ?: [];
    }
}
