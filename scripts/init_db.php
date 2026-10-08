<?php

/**
 * Script de inicialização do banco de dados e seed para desenvolvimento.
 * Executar via CLI: php scripts/init_db.php
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

require_once __DIR__ . '/../app/Support/Config.php';

use App\Support\Config;

echo "=== Inicialização do Banco de Dados - Gestão de Academia ===\n";

Config::load(__DIR__ . '/../.env');

$dbHost = Config::get('database.host', '127.0.0.1');
$dbPort = Config::get('database.port', '3306');
$dbName = Config::get('database.database', 'gestao_academia');
$dbUser = Config::get('database.username', 'root');
$dbPass = Config::get('database.password', '');

try {
    // Conectar ao servidor MySQL sem selecionar o banco para garantir que o banco exista
    $rootPdo = new PDO(
        "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    echo "1. Criando banco de dados '{$dbName}' (se não existir)...\n";
    $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

    // Conectar ao banco específico
    $pdo = new PDO(
        "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    echo "2. Aplicando schema...\n";
    $schemaSql = file_get_contents(__DIR__ . '/../database/schema.sql');
    $pdo->exec($schemaSql);
    echo "   Schema aplicado com sucesso.\n";

    echo "3. Populando dados iniciais de desenvolvimento...\n";

    // Redes
    $stmtRede = $pdo->prepare("INSERT INTO redes (id, nome, status) VALUES (:id, :nome, :status) ON DUPLICATE KEY UPDATE nome = VALUES(nome), status = VALUES(status)");
    $stmtRede->execute(['id' => 1, 'nome' => 'Rede Alpha Fit', 'status' => 'ativo']);
    $stmtRede->execute(['id' => 2, 'nome' => 'Rede Beta Saúde', 'status' => 'ativo']);
    $stmtRede->execute(['id' => 3, 'nome' => 'Rede Delta Inativa', 'status' => 'inativo']);

    // Unidades
    $stmtUnidade = $pdo->prepare("INSERT INTO unidades (id, rede_id, nome, status) VALUES (:id, :rede_id, :nome, :status) ON DUPLICATE KEY UPDATE rede_id = VALUES(rede_id), nome = VALUES(nome), status = VALUES(status)");
    $stmtUnidade->execute(['id' => 1, 'rede_id' => 1, 'nome' => 'Alpha Centro', 'status' => 'ativo']);
    $stmtUnidade->execute(['id' => 2, 'rede_id' => 1, 'nome' => 'Alpha Jardins', 'status' => 'ativo']);
    $stmtUnidade->execute(['id' => 3, 'rede_id' => 2, 'nome' => 'Beta Norte', 'status' => 'ativo']);
    $stmtUnidade->execute(['id' => 4, 'rede_id' => 3, 'nome' => 'Delta Sul', 'status' => 'inativo']);

    // Usuários de desenvolvimento
    $usuarios = [
        [
            'id' => 1,
            'rede_id' => 1,
            'nome' => 'Carlos Administrador',
            'email' => 'admin@alphafit.com.br',
            'senha' => 'Admin@123456',
            'perfil' => 'admin',
            'status' => 'ativo',
            'unidades' => [1, 2],
        ],
        [
            'id' => 2,
            'rede_id' => 1,
            'nome' => 'Renata Profissional',
            'email' => 'personal@alphafit.com.br',
            'senha' => 'Profissional@123456',
            'perfil' => 'profissional',
            'status' => 'ativo',
            'unidades' => [1],
        ],
        [
            'id' => 3,
            'rede_id' => 1,
            'nome' => 'Lucas Aluno',
            'email' => 'aluno@alphafit.com.br',
            'senha' => 'Aluno@123456',
            'perfil' => 'aluno',
            'status' => 'ativo',
            'unidades' => [1],
        ],
        [
            'id' => 4,
            'rede_id' => 1,
            'nome' => 'Mariana Inativa',
            'email' => 'inativo@alphafit.com.br',
            'senha' => 'Inativo@123456',
            'perfil' => 'aluno',
            'status' => 'inativo',
            'unidades' => [1],
        ],
        [
            'id' => 5,
            'rede_id' => 3,
            'nome' => 'Eduardo Rede Inativa',
            'email' => 'bloqueado@deltainativa.com.br',
            'senha' => 'Bloqueado@123456',
            'perfil' => 'admin',
            'status' => 'ativo',
            'unidades' => [4],
        ],
        [
            'id' => 6,
            'rede_id' => 2,
            'nome' => 'Beatriz Admin Beta',
            'email' => 'admin@betasaude.com.br',
            'senha' => 'Beta@123456',
            'perfil' => 'admin',
            'status' => 'ativo',
            'unidades' => [3],
        ],
    ];

    $stmtUser = $pdo->prepare("
        INSERT INTO usuarios (id, rede_id, nome, email, senha_hash, perfil, status, session_version)
        VALUES (:id, :rede_id, :nome, :email, :senha_hash, :perfil, :status, 1)
        ON DUPLICATE KEY UPDATE
            rede_id = VALUES(rede_id),
            nome = VALUES(nome),
            senha_hash = VALUES(senha_hash),
            perfil = VALUES(perfil),
            status = VALUES(status)
    ");

    $stmtVinculo = $pdo->prepare("INSERT IGNORE INTO usuario_unidades (usuario_id, unidade_id) VALUES (:usuario_id, :unidade_id)");

    foreach ($usuarios as $u) {
        $hash = password_hash($u['senha'], PASSWORD_DEFAULT);
        $stmtUser->execute([
            'id' => $u['id'],
            'rede_id' => $u['rede_id'],
            'nome' => $u['nome'],
            'email' => $u['email'],
            'senha_hash' => $hash,
            'perfil' => $u['perfil'],
            'status' => $u['status'],
        ]);

        foreach ($u['unidades'] as $unidadeId) {
            $stmtVinculo->execute([
                'usuario_id' => $u['id'],
                'unidade_id' => $unidadeId,
            ]);
        }
    }

    echo "   Usuários e vínculos criados com sucesso.\n";

    echo "4. Populando conteúdos institucionais e comunicados de teste...\n";
    $stmtRedeConteudo = $pdo->prepare("
        INSERT INTO rede_conteudos (rede_id, slogan, descricao, horario_funcionamento, telefone_contato, email_contato, banner_aviso, banner_ativo, regras_gerais)
        VALUES (:rede_id, :slogan, :descricao, :horario_funcionamento, :telefone_contato, :email_contato, :banner_aviso, :banner_ativo, :regras_gerais)
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

    $stmtRedeConteudo->execute([
        'rede_id' => 1,
        'slogan' => 'Sua melhor versão começa aqui.',
        'descricao' => 'A Rede Alpha Fit oferece infraestrutura moderna, profissionais qualificados e acompanhamento personalizado para você atingir seus objetivos de saúde e bem-estar.',
        'horario_funcionamento' => 'Seg a Sex: 06:00 às 23:00 | Sáb: 08:00 às 18:00 | Dom: 09:00 às 14:00',
        'telefone_contato' => '(11) 98765-4321',
        'email_contato' => 'contato@alphafit.com.br',
        'banner_aviso' => 'Aviso: Horário especial de funcionamento no feriado. Consulte os detalhes no mural.',
        'banner_ativo' => 1,
        'regras_gerais' => "1. Uso obrigatório de toalha de treino.\n2. Guardar pesos e anilhas após a utilização.\n3. Respeitar o tempo de descanso nos aparelhos compartilhados.",
    ]);

    $stmtRedeConteudo->execute([
        'rede_id' => 2,
        'slogan' => 'Saúde e movimento todos os dias.',
        'descricao' => 'Rede Beta Saúde com foco em longevidade e condicionamento físico.',
        'horario_funcionamento' => 'Seg a Sex: 06:00 às 22:00 | Sáb: 08:00 às 14:00',
        'telefone_contato' => '(11) 91234-5678',
        'email_contato' => 'atendimento@betasaude.com.br',
        'banner_aviso' => '',
        'banner_ativo' => 0,
        'regras_gerais' => 'Respeite as orientações dos instrutores.',
    ]);

    // Comunicados
    $stmtComunicado = $pdo->prepare("
        INSERT INTO conteudos_comunicados (id, rede_id, unidade_id, titulo, categoria, publico_alvo, conteudo, fixado, status, autor_id)
        VALUES (:id, :rede_id, :unidade_id, :titulo, :categoria, :publico_alvo, :conteudo, :fixado, :status, :autor_id)
        ON DUPLICATE KEY UPDATE
            titulo = VALUES(titulo),
            categoria = VALUES(categoria),
            publico_alvo = VALUES(publico_alvo),
            conteudo = VALUES(conteudo),
            fixado = VALUES(fixado),
            status = VALUES(status)
    ");

    $stmtComunicado->execute([
        'id' => 1,
        'rede_id' => 1,
        'unidade_id' => null,
        'titulo' => 'Bem-vindo à nova plataforma de Gestão de Academia',
        'categoria' => 'Novidades',
        'publico_alvo' => 'todos',
        'conteudo' => 'Estamos felizes em apresentar o novo portal de gestão da academia. Aqui você acompanha avisos, treinos e informações da sua unidade com máxima agilidade e segurança.',
        'fixado' => 1,
        'status' => 'ativo',
        'autor_id' => 1,
    ]);

    $stmtComunicado->execute([
        'id' => 2,
        'rede_id' => 1,
        'unidade_id' => 1,
        'titulo' => 'Manutenção preventiva dos equipamentos da Unidade Centro',
        'categoria' => 'Manutenção',
        'publico_alvo' => 'alunos',
        'conteudo' => 'Nesta sexta-feira realizaremos a revisão das esteiras e aparelhos de cabo da Unidade Alpha Centro entre 13:00 e 15:00. O espaço permanecerá liberado para uso parcial.',
        'fixado' => 0,
        'status' => 'ativo',
        'autor_id' => 1,
    ]);

    $stmtComunicado->execute([
        'id' => 3,
        'rede_id' => 1,
        'unidade_id' => null,
        'titulo' => 'Alinhamento com a equipe de profissionais e instrutores',
        'categoria' => 'Operacional',
        'publico_alvo' => 'profissionais',
        'conteudo' => 'Lembramos a todos os profissionais da Rede Alpha que os registros de novos treinos e avaliações devem seguir os protocolos padronizados de biossegurança e postura.',
        'fixado' => 0,
        'status' => 'ativo',
        'autor_id' => 1,
    ]);

    echo "   Conteúdos e comunicados criados com sucesso.\n";
    echo "\n=== Inicialização concluída com sucesso! ===\n";
    echo "Contas de desenvolvimento prontas para teste:\n";
    echo "- Administrador (Alpha): admin@alphafit.com.br / Admin@123456\n";
    echo "- Profissional (Alpha):  personal@alphafit.com.br / Profissional@123456\n";
    echo "- Aluno (Alpha):         aluno@alphafit.com.br / Aluno@123456\n";
    echo "- Usuário Inativo:       inativo@alphafit.com.br / Inativo@123456\n";
    echo "- Rede Inativa:          bloqueado@deltainativa.com.br / Bloqueado@123456\n";
    echo "- Admin Rede Beta:       admin@betasaude.com.br / Beta@123456\n";
} catch (PDOException $e) {
    echo "ERRO no banco de dados: " . $e->getMessage() . "\n";
    exit(1);
} catch (Throwable $e) {
    echo "ERRO inesperado: " . $e->getMessage() . "\n";
    exit(1);
}
