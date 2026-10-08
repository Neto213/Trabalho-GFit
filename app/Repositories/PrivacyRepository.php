<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Services\DataProtectionService;
use App\Support\Config;
use App\Support\Database;
use PDO;

final class PrivacyRepository
{
    public function __construct(private ?PDO $pdo = null)
    {
        $this->pdo ??= Database::getConnection();
    }

    public function hasConsent(int $userId, string $purpose): bool
    {
        $stmt = $this->pdo->prepare('SELECT status FROM lgpd_consentimentos WHERE usuario_id = ? AND finalidade = ? ORDER BY id DESC LIMIT 1');
        $stmt->execute([$userId, $purpose]);
        return $stmt->fetchColumn() === 'concedido';
    }

    public function recordConsent(array $user, string $purpose, bool $granted, string $ip): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO lgpd_consentimentos (rede_id, usuario_id, finalidade, status, versao_aviso, ip_hash) VALUES (?,?,?,?,?,?)');
        $stmt->execute([
            $user['rede_id'], $user['id'], $purpose, $granted ? 'concedido' : 'revogado',
            Config::get('privacy.notice_version'), DataProtectionService::fingerprint($ip),
        ]);
    }

    public function consentHistory(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT finalidade,status,versao_aviso,created_at FROM lgpd_consentimentos WHERE usuario_id=? ORDER BY id DESC LIMIT 50');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function createRequest(array $user, string $type, string $details): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO lgpd_solicitacoes (rede_id, usuario_id, tipo, detalhes) VALUES (?,?,?,?)');
        $stmt->execute([$user['rede_id'], $user['id'], $type, $details]);
        return (int)$this->pdo->lastInsertId();
    }

    public function requestsForUser(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT id,tipo,detalhes,status,resposta,created_at,responded_at FROM lgpd_solicitacoes WHERE usuario_id=? ORDER BY id DESC');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function requestsForNetwork(int $networkId): array
    {
        $stmt = $this->pdo->prepare('SELECT s.*,u.nome,u.email FROM lgpd_solicitacoes s JOIN usuarios u ON u.id=s.usuario_id WHERE s.rede_id=? ORDER BY FIELD(s.status,"aberta","em_analise","concluida","negada"),s.id DESC LIMIT 200');
        $stmt->execute([$networkId]);
        return $stmt->fetchAll();
    }

    public function updateRequest(array $admin, int $requestId, string $status, string $answer): void
    {
        $stmt = $this->pdo->prepare('UPDATE lgpd_solicitacoes SET status=?,resposta=?,respondido_por=?,responded_at=IF(? IN ("concluida","negada"),NOW(),NULL) WHERE id=? AND rede_id=?');
        $stmt->execute([$status, $answer, $admin['id'], $status, $requestId, $admin['rede_id']]);
    }

    public function exportForUser(array $user): array
    {
        $userId = (int)$user['id'];
        $networkId = (int)$user['rede_id'];
        $profile = $this->pdo->prepare('SELECT id,nome,email,perfil,status,created_at,updated_at FROM usuarios WHERE id=? AND rede_id=?');
        $profile->execute([$userId, $networkId]);
        $units = $this->pdo->prepare('SELECT un.id,un.nome FROM unidades un JOIN usuario_unidades uu ON uu.unidade_id=un.id WHERE uu.usuario_id=? AND un.rede_id=?');
        $units->execute([$userId, $networkId]);

        $training = new TrainingRepository($this->pdo);
        $trainingData = $user['perfil'] === 'aluno' ? $training->data($user, $userId) : [];

        return [
            'generated_at' => gmdate('c'),
            'notice_version' => Config::get('privacy.notice_version'),
            'profile' => $profile->fetch() ?: null,
            'units' => $units->fetchAll(),
            'consents' => $this->consentHistory($userId),
            'requests' => $this->requestsForUser($userId),
            'training' => $trainingData,
        ];
    }
}
