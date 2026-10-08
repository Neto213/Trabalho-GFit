<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\Database;
use PDO;

class ContentRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getConnection();
    }

    /**
     * Buscar todos os comunicados da rede, com nomes da unidade e autor.
     *
     * @param int $networkId
     * @param int|null $unitId
     * @return array
     */
    public function findAllByNetwork(int $networkId, ?int $unitId = null): array
    {
        $sql = "
            SELECT c.*, u.nome AS unidade_nome, a.nome AS autor_nome
            FROM conteudos_comunicados c
            LEFT JOIN unidades u ON u.id = c.unidade_id
            LEFT JOIN usuarios a ON a.id = c.autor_id
            WHERE c.rede_id = :rede_id
        ";
        $params = ['rede_id' => $networkId];

        if ($unitId !== null) {
            $sql .= " AND (c.unidade_id IS NULL OR c.unidade_id = :unidade_id)";
            $params['unidade_id'] = $unitId;
        }

        $sql .= " ORDER BY c.fixado DESC, c.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }

    /**
     * Buscar comunicados ativos filtrados por público-alvo e unidade.
     *
     * @param int $networkId
     * @param string $audience 'todos' | 'alunos' | 'profissionais'
     * @param int|null $unitId
     * @return array
     */
    public function findActiveByNetworkAndAudience(int $networkId, string $audience = 'todos', ?int $unitId = null): array
    {
        $sql = "
            SELECT c.*, u.nome AS unidade_nome, a.nome AS autor_nome
            FROM conteudos_comunicados c
            LEFT JOIN unidades u ON u.id = c.unidade_id
            LEFT JOIN usuarios a ON a.id = c.autor_id
            WHERE c.rede_id = :rede_id
              AND c.status = 'ativo'
              AND (c.publico_alvo = 'todos' OR c.publico_alvo = :publico_alvo)
        ";
        $params = [
            'rede_id' => $networkId,
            'publico_alvo' => $audience,
        ];

        if ($unitId !== null) {
            $sql .= " AND (c.unidade_id IS NULL OR c.unidade_id = :unidade_id)";
            $params['unidade_id'] = $unitId;
        }

        $sql .= " ORDER BY c.fixado DESC, c.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }

    /**
     * Buscar um comunicado por ID e Rede (garantindo isolamento).
     */
    public function findById(int $id, int $networkId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT c.*, u.nome AS unidade_nome, a.nome AS autor_nome
            FROM conteudos_comunicados c
            LEFT JOIN unidades u ON u.id = c.unidade_id
            LEFT JOIN usuarios a ON a.id = c.autor_id
            WHERE c.id = :id AND c.rede_id = :rede_id
            LIMIT 1
        ");
        $stmt->execute([
            'id' => $id,
            'rede_id' => $networkId,
        ]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Criar novo comunicado.
     */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO conteudos_comunicados (
                rede_id, unidade_id, titulo, categoria, publico_alvo, conteudo, fixado, status, autor_id
            ) VALUES (
                :rede_id, :unidade_id, :titulo, :categoria, :publico_alvo, :conteudo, :fixado, :status, :autor_id
            )
        ");

        $stmt->execute([
            'rede_id' => $data['rede_id'],
            'unidade_id' => !empty($data['unidade_id']) ? (int)$data['unidade_id'] : null,
            'titulo' => trim($data['titulo']),
            'categoria' => !empty($data['categoria']) ? trim($data['categoria']) : 'Geral',
            'publico_alvo' => in_array($data['publico_alvo'] ?? '', ['todos', 'alunos', 'profissionais'], true) ? $data['publico_alvo'] : 'todos',
            'conteudo' => trim($data['conteudo']),
            'fixado' => !empty($data['fixado']) ? 1 : 0,
            'status' => ($data['status'] ?? 'ativo') === 'inativo' ? 'inativo' : 'ativo',
            'autor_id' => (int)$data['autor_id'],
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Atualizar comunicado existente.
     */
    public function update(int $id, int $networkId, array $data): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE conteudos_comunicados
            SET unidade_id = :unidade_id,
                titulo = :titulo,
                categoria = :categoria,
                publico_alvo = :publico_alvo,
                conteudo = :conteudo,
                fixado = :fixado,
                status = :status
            WHERE id = :id AND rede_id = :rede_id
        ");

        return $stmt->execute([
            'id' => $id,
            'rede_id' => $networkId,
            'unidade_id' => !empty($data['unidade_id']) ? (int)$data['unidade_id'] : null,
            'titulo' => trim($data['titulo']),
            'categoria' => !empty($data['categoria']) ? trim($data['categoria']) : 'Geral',
            'publico_alvo' => in_array($data['publico_alvo'] ?? '', ['todos', 'alunos', 'profissionais'], true) ? $data['publico_alvo'] : 'todos',
            'conteudo' => trim($data['conteudo']),
            'fixado' => !empty($data['fixado']) ? 1 : 0,
            'status' => ($data['status'] ?? 'ativo') === 'inativo' ? 'inativo' : 'ativo',
        ]);
    }

    /**
     * Alternar status ativo/inativo.
     */
    public function toggleStatus(int $id, int $networkId): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE conteudos_comunicados
            SET status = CASE WHEN status = 'ativo' THEN 'inativo' ELSE 'ativo' END
            WHERE id = :id AND rede_id = :rede_id
        ");

        return $stmt->execute([
            'id' => $id,
            'rede_id' => $networkId,
        ]);
    }

    /**
     * Excluir comunicado respeitando a rede.
     */
    public function delete(int $id, int $networkId): bool
    {
        $stmt = $this->pdo->prepare("
            DELETE FROM conteudos_comunicados
            WHERE id = :id AND rede_id = :rede_id
        ");

        return $stmt->execute([
            'id' => $id,
            'rede_id' => $networkId,
        ]);
    }

    /**
     * Contagem de comunicados ativos da rede.
     */
    public function countActive(int $networkId): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM conteudos_comunicados
            WHERE rede_id = :rede_id AND status = 'ativo'
        ");
        $stmt->execute(['rede_id' => $networkId]);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Obter dados de conteúdo institucional da rede.
     */
    public function getInstitutionalContent(int $networkId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM rede_conteudos
            WHERE rede_id = :rede_id
            LIMIT 1
        ");
        $stmt->execute(['rede_id' => $networkId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * Salvar ou atualizar dados de conteúdo institucional.
     */
    public function saveInstitutionalContent(int $networkId, array $data): bool
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO rede_conteudos (
                rede_id, slogan, descricao, horario_funcionamento, telefone_contato, email_contato, banner_aviso, banner_ativo, regras_gerais
            ) VALUES (
                :rede_id, :slogan, :descricao, :horario_funcionamento, :telefone_contato, :email_contato, :banner_aviso, :banner_ativo, :regras_gerais
            )
            ON DUPLICATE KEY UPDATE
                slogan = VALUES(slogan),
                descricao = VALUES(descricao),
                horario_funcionamento = VALUES(horario_funcionamento),
                telefone_contato = VALUES(telefone_contato),
                email_contato = VALUES(email_contato),
                banner_aviso = VALUES(banner_aviso),
                banner_ativo = VALUES(banner_ativo),
                regras_gerais = VALUES(regras_gerais)
        ");

        return $stmt->execute([
            'rede_id' => $networkId,
            'slogan' => trim($data['slogan'] ?? ''),
            'descricao' => trim($data['descricao'] ?? ''),
            'horario_funcionamento' => trim($data['horario_funcionamento'] ?? ''),
            'telefone_contato' => trim($data['telefone_contato'] ?? ''),
            'email_contato' => trim($data['email_contato'] ?? ''),
            'banner_aviso' => trim($data['banner_aviso'] ?? ''),
            'banner_ativo' => !empty($data['banner_ativo']) ? 1 : 0,
            'regras_gerais' => trim($data['regras_gerais'] ?? ''),
        ]);
    }
}
