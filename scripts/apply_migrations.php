<?php

/**
 * Script de aplicação de migrações no banco de dados.
 * Executar via CLI: php scripts/apply_migrations.php
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

require_once __DIR__ . '/../app/Support/Config.php';
require_once __DIR__ . '/../app/Support/Database.php';

use App\Support\Config;
use App\Support\Database;

Config::load(__DIR__ . '/../.env');

echo "=== Aplicando Migrações - Gestão de Academia ===\n";

try {
    $pdo = Database::getConnection();

    // 1. Aplicar colunas na tabela usuarios se não existirem
    echo "1. Verificando colunas de MFA na tabela 'usuarios'...\n";
    $columns = $pdo->query("SHOW COLUMNS FROM usuarios")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('mfa_enabled', $columns, true)) {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN mfa_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER session_version");
        echo "   + Coluna 'mfa_enabled' adicionada.\n";
    }
    if (!in_array('mfa_secret', $columns, true)) {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN mfa_secret TEXT NULL DEFAULT NULL AFTER mfa_enabled");
        echo "   + Coluna 'mfa_secret' adicionada.\n";
    }
    if (!in_array('mfa_enforced', $columns, true)) {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN mfa_enforced TINYINT(1) NOT NULL DEFAULT 0 AFTER mfa_secret");
        echo "   + Coluna 'mfa_enforced' adicionada.\n";
    }
    if (!in_array('mfa_enforced_at', $columns, true)) {
        $pdo->exec("ALTER TABLE usuarios ADD COLUMN mfa_enforced_at DATETIME NULL DEFAULT NULL AFTER mfa_enforced");
        echo "   + Coluna 'mfa_enforced_at' adicionada.\n";
    }

    // 2. Criar tabela mfa_recovery_codes
    echo "2. Criando tabela 'mfa_recovery_codes'...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS mfa_recovery_codes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            code_hash VARCHAR(255) NOT NULL,
            used_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_mfa_recovery_usuario (usuario_id),
            CONSTRAINT fk_mfa_recovery_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 3. Criar tabela mfa_used_codes
    echo "3. Criando tabela 'mfa_used_codes'...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS mfa_used_codes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            code_hash CHAR(64) NOT NULL,
            period_timestamp BIGINT NOT NULL,
            consumed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_user_code_period (usuario_id, code_hash, period_timestamp),
            INDEX idx_mfa_used_consumed (consumed_at),
            CONSTRAINT fk_mfa_used_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $pdo->exec(file_get_contents(__DIR__ . '/../database/migrations/2026_09_24_training.sql'));
    $pdo->exec(file_get_contents(__DIR__ . '/../database/migrations/2026_10_01_lgpd.sql'));
    echo "=== Migrações aplicadas com sucesso! ===\n";
} catch (Throwable $e) {
    echo "ERRO ao aplicar migrações: " . $e->getMessage() . "\n";
    exit(1);
}
