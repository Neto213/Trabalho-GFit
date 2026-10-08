-- Migration: 2026_09_17_add_mfa_tables.sql
-- Adiciona suporte a MFA (TOTP com criptografia autenticada AES-256-GCM),
-- códigos de recuperação de uso único e proteção anti-replay / concorrência.

-- 1. Novas colunas na tabela usuarios (se ainda não existirem)
ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS mfa_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER session_version,
    ADD COLUMN IF NOT EXISTS mfa_secret TEXT NULL DEFAULT NULL AFTER mfa_enabled,
    ADD COLUMN IF NOT EXISTS mfa_enforced TINYINT(1) NOT NULL DEFAULT 0 AFTER mfa_secret,
    ADD COLUMN IF NOT EXISTS mfa_enforced_at DATETIME NULL DEFAULT NULL AFTER mfa_enforced;

-- 2. Tabela de códigos de recuperação (Backup Codes)
CREATE TABLE IF NOT EXISTS mfa_recovery_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    code_hash VARCHAR(255) NOT NULL,
    used_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_mfa_recovery_usuario (usuario_id),
    CONSTRAINT fk_mfa_recovery_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabela de códigos TOTP consumidos (Proteção estrita anti-replay e concorrência)
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
