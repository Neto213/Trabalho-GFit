CREATE TABLE IF NOT EXISTS lgpd_consentimentos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rede_id INT NOT NULL,
    usuario_id INT NOT NULL,
    finalidade VARCHAR(80) NOT NULL,
    status ENUM('concedido','revogado') NOT NULL,
    versao_aviso VARCHAR(30) NOT NULL,
    ip_hash CHAR(32) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_lgpd_consent_user (usuario_id, finalidade, id),
    INDEX idx_lgpd_consent_network (rede_id, created_at),
    FOREIGN KEY (rede_id) REFERENCES redes(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lgpd_solicitacoes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rede_id INT NOT NULL,
    usuario_id INT NOT NULL,
    tipo ENUM('acesso','correcao','exclusao','portabilidade','informacao','oposicao','revogacao') NOT NULL,
    detalhes TEXT NOT NULL,
    status ENUM('aberta','em_analise','concluida','negada') NOT NULL DEFAULT 'aberta',
    resposta TEXT NULL,
    respondido_por INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    responded_at DATETIME NULL,
    INDEX idx_lgpd_request_network (rede_id, status, created_at),
    INDEX idx_lgpd_request_user (usuario_id, created_at),
    FOREIGN KEY (rede_id) REFERENCES redes(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (respondido_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lgpd_incidentes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rede_id INT NOT NULL,
    registrado_por INT NOT NULL,
    resumo VARCHAR(255) NOT NULL,
    impacto TEXT NOT NULL,
    medidas TEXT NOT NULL,
    ocorrido_at DATETIME NULL,
    discovered_at DATETIME NOT NULL,
    anpd_notificada_at DATETIME NULL,
    titulares_notificados_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_lgpd_incident_network (rede_id, discovered_at),
    FOREIGN KEY (rede_id) REFERENCES redes(id),
    FOREIGN KEY (registrado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE gfit_avaliacoes
    MODIFY peso DECIMAL(5,2) NULL,
    MODIFY altura DECIMAL(5,2) NULL,
    MODIFY observacoes TEXT NULL,
    ADD COLUMN IF NOT EXISTS dados_protegidos LONGTEXT NULL AFTER observacoes,
    ADD COLUMN IF NOT EXISTS versao_criptografia SMALLINT UNSIGNED NULL AFTER dados_protegidos;
