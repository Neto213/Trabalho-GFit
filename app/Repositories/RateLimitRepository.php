<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\Database;
use PDO;

class RateLimitRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    public function isLocked(string $key, string $type): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT bloqueado_ate
            FROM tentativas_acesso
            WHERE chave_identificadora = :chave AND tipo = :tipo
            LIMIT 1
        ");
        $stmt->execute(['chave' => $key, 'tipo' => $type]);
        $row = $stmt->fetch();

        if (!$row || empty($row['bloqueado_ate'])) {
            return false;
        }

        return strtotime($row['bloqueado_ate']) > time();
    }

    public function getLockMinutesRemaining(string $key, string $type): int
    {
        $stmt = $this->pdo->prepare("
            SELECT TIMESTAMPDIFF(MINUTE, NOW(), bloqueado_ate) AS rem
            FROM tentativas_acesso
            WHERE chave_identificadora = :chave AND tipo = :tipo
            LIMIT 1
        ");
        $stmt->execute(['chave' => $key, 'tipo' => $type]);
        $row = $stmt->fetch();

        if (!$row || $row['rem'] === null) {
            return 0;
        }

        $rem = (int)$row['rem'];
        return $rem > 0 ? $rem : 1; // Ao menos 1 minuto se estiver bloqueado
    }

    public function increment(string $key, string $type, int $maxAttempts, int $lockoutMinutes): int
    {
        // Se já passou do tempo de bloqueio, reiniciar contador
        $stmt = $this->pdo->prepare("
            SELECT tentativas, bloqueado_ate
            FROM tentativas_acesso
            WHERE chave_identificadora = :chave AND tipo = :tipo
            LIMIT 1
        ");
        $stmt->execute(['chave' => $key, 'tipo' => $type]);
        $record = $stmt->fetch();

        $now = time();
        if ($record && !empty($record['bloqueado_ate']) && strtotime($record['bloqueado_ate']) <= $now) {
            // Bloqueio expirou, resetar
            $this->reset($key, $type);
            $record = null;
        }

        $currentAttempts = $record ? (int)$record['tentativas'] + 1 : 1;
        $bloqueadoAte = null;

        if ($currentAttempts >= $maxAttempts) {
            $bloqueadoAte = date('Y-m-d H:i:s', $now + ($lockoutMinutes * 60));
        }

        $stmtUpsert = $this->pdo->prepare("
            INSERT INTO tentativas_acesso (chave_identificadora, tipo, tentativas, bloqueado_ate, updated_at)
            VALUES (:chave, :tipo, :tentativas, :bloqueado_ate, NOW())
            ON DUPLICATE KEY UPDATE
                tentativas = :tentativas_up,
                bloqueado_ate = :bloqueado_ate_up,
                updated_at = NOW()
        ");

        $stmtUpsert->execute([
            'chave' => $key,
            'tipo' => $type,
            'tentativas' => $currentAttempts,
            'bloqueado_ate' => $bloqueadoAte,
            'tentativas_up' => $currentAttempts,
            'bloqueado_ate_up' => $bloqueadoAte,
        ]);

        return $currentAttempts;
    }

    public function reset(string $key, string $type): void
    {
        $stmt = $this->pdo->prepare("
            DELETE FROM tentativas_acesso
            WHERE chave_identificadora = :chave AND tipo = :tipo
        ");
        $stmt->execute(['chave' => $key, 'tipo' => $type]);
    }
}
