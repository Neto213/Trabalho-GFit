<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\Database;
use PDO;

class StatsRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    /**
     * Obter estatísticas consolidadas da rede ou filtradas por unidade.
     */
    public function getSummary(int $networkId, ?int $unitId = null): array
    {
        // 1. Contagem de usuários por perfil e status
        if ($unitId !== null) {
            $stmt = $this->pdo->prepare("
                SELECT 
                    u.perfil,
                    u.status,
                    COUNT(DISTINCT u.id) AS total
                FROM usuarios u
                INNER JOIN usuario_unidades uu ON uu.usuario_id = u.id
                WHERE u.rede_id = :rede_id AND uu.unidade_id = :unidade_id
                GROUP BY u.perfil, u.status
            ");
            $stmt->execute(['rede_id' => $networkId, 'unidade_id' => $unitId]);
        } else {
            $stmt = $this->pdo->prepare("
                SELECT 
                    perfil,
                    status,
                    COUNT(*) AS total
                FROM usuarios
                WHERE rede_id = :rede_id
                GROUP BY perfil, status
            ");
            $stmt->execute(['rede_id' => $networkId]);
        }

        $rows = $stmt->fetchAll();

        $stats = [
            'total_usuarios' => 0,
            'total_alunos' => 0,
            'alunos_ativos' => 0,
            'alunos_inativos' => 0,
            'total_profissionais' => 0,
            'profissionais_ativos' => 0,
            'total_admins' => 0,
            'taxa_atividade_alunos' => 0.0,
        ];

        foreach ($rows as $row) {
            $count = (int)$row['total'];
            $stats['total_usuarios'] += $count;

            if ($row['perfil'] === 'aluno') {
                $stats['total_alunos'] += $count;
                if ($row['status'] === 'ativo') {
                    $stats['alunos_ativos'] += $count;
                } else {
                    $stats['alunos_inativos'] += $count;
                }
            } elseif ($row['perfil'] === 'profissional') {
                $stats['total_profissionais'] += $count;
                if ($row['status'] === 'ativo') {
                    $stats['profissionais_ativos'] += $count;
                }
            } elseif ($row['perfil'] === 'admin') {
                $stats['total_admins'] += $count;
            }
        }

        if ($stats['total_alunos'] > 0) {
            $stats['taxa_atividade_alunos'] = round(($stats['alunos_ativos'] / $stats['total_alunos']) * 100, 1);
        }

        // 2. Total de unidades da rede
        $stmtUnits = $this->pdo->prepare("
            SELECT COUNT(*) FROM unidades WHERE rede_id = :rede_id AND status = 'ativo'
        ");
        $stmtUnits->execute(['rede_id' => $networkId]);
        $stats['total_unidades'] = (int)$stmtUnits->fetchColumn();

        // 3. Total de comunicados ativos da rede
        $stmtComms = $this->pdo->prepare("
            SELECT COUNT(*) FROM conteudos_comunicados WHERE rede_id = :rede_id AND status = 'ativo'
        ");
        $stmtComms->execute(['rede_id' => $networkId]);
        $stats['total_comunicados'] = (int)$stmtComms->fetchColumn();

        return $stats;
    }

    /**
     * Distribuição de alunos e profissionais por unidade na rede.
     */
    public function getUnitsDistribution(int $networkId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                un.id AS unidade_id,
                un.nome AS unidade_nome,
                un.status AS unidade_status,
                COUNT(DISTINCT CASE WHEN u.perfil = 'aluno' AND u.status = 'ativo' THEN u.id END) AS alunos_ativos,
                COUNT(DISTINCT CASE WHEN u.perfil = 'aluno' AND u.status = 'inativo' THEN u.id END) AS alunos_inativos,
                COUNT(DISTINCT CASE WHEN u.perfil = 'aluno' THEN u.id END) AS total_alunos,
                COUNT(DISTINCT CASE WHEN u.perfil = 'profissional' THEN u.id END) AS total_profissionais,
                COUNT(DISTINCT u.id) AS total_usuarios
            FROM unidades un
            LEFT JOIN usuario_unidades uu ON uu.unidade_id = un.id
            LEFT JOIN usuarios u ON u.id = uu.usuario_id AND u.rede_id = un.rede_id
            WHERE un.rede_id = :rede_id
            GROUP BY un.id, un.nome, un.status
            ORDER BY total_alunos DESC, un.nome ASC
        ");
        $stmt->execute(['rede_id' => $networkId]);

        return $stmt->fetchAll() ?: [];
    }

    /**
     * Métricas de tentativas de acesso (segurança / rate limiting).
     */
    public function getAccessSecurityStats(?int $networkId = null): array
    {
        if ($networkId !== null) {
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) AS total_registros,
                    COALESCE(SUM(ta.tentativas), 0) AS total_tentativas,
                    COUNT(CASE WHEN ta.bloqueado_ate IS NOT NULL AND ta.bloqueado_ate > NOW() THEN 1 END) AS bloqueios_ativos
                FROM tentativas_acesso ta
                WHERE ta.chave_identificadora IN (
                    SELECT email FROM usuarios WHERE rede_id = :rede_id1
                )
                OR ta.chave_identificadora IN (
                    SELECT CONCAT('account:', email) FROM usuarios WHERE rede_id = :rede_id2
                )
                OR ta.chave_identificadora IN (
                    SELECT CONCAT('login_acc:', email) FROM usuarios WHERE rede_id = :rede_id3
                )
                OR EXISTS (
                    SELECT 1 FROM usuarios u 
                    WHERE u.rede_id = :rede_id4 
                    AND (ta.chave_identificadora LIKE CONCAT('%:', u.email) OR ta.chave_identificadora LIKE CONCAT('%:', u.id))
                )
            ");
            $stmt->execute([
                'rede_id1' => $networkId,
                'rede_id2' => $networkId,
                'rede_id3' => $networkId,
                'rede_id4' => $networkId,
            ]);
        } else {
            $stmt = $this->pdo->query("
                SELECT 
                    COUNT(*) AS total_registros,
                    COALESCE(SUM(tentativas), 0) AS total_tentativas,
                    COUNT(CASE WHEN bloqueado_ate IS NOT NULL AND bloqueado_ate > NOW() THEN 1 END) AS bloqueios_ativos
                FROM tentativas_acesso
            ");
        }
        $row = $stmt->fetch();

        return [
            'total_registros' => (int)($row['total_registros'] ?? 0),
            'total_tentativas' => (int)($row['total_tentativas'] ?? 0),
            'bloqueios_ativos' => (int)($row['bloqueios_ativos'] ?? 0),
        ];
    }


    /**
     * Novos usuários cadastrados por mês (últimos 6 meses).
     */
    public function getRegistrationTrends(int $networkId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') AS mes_ano,
                DATE_FORMAT(created_at, '%b/%y') AS rotulo,
                COUNT(*) AS total,
                SUM(CASE WHEN perfil = 'aluno' THEN 1 ELSE 0 END) AS alunos,
                SUM(CASE WHEN perfil = 'profissional' THEN 1 ELSE 0 END) AS profissionais
            FROM usuarios
            WHERE rede_id = :rede_id
            GROUP BY mes_ano, rotulo
            ORDER BY mes_ano ASC
            LIMIT 6
        ");
        $stmt->execute(['rede_id' => $networkId]);

        return $stmt->fetchAll() ?: [];
    }
}
