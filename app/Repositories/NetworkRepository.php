<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\Database;
use PDO;

class NetworkRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM redes WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function all(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM redes ORDER BY nome ASC");
        return $stmt->fetchAll();
    }
}
