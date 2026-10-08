-- Schema para Gestão de Conteúdo e Avisos

CREATE TABLE IF NOT EXISTS conteudos_comunicados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rede_id INT NOT NULL,
    unidade_id INT NULL DEFAULT NULL,
    titulo VARCHAR(200) NOT NULL,
    categoria VARCHAR(50) NOT NULL DEFAULT 'Geral',
    publico_alvo ENUM('todos', 'alunos', 'profissionais') NOT NULL DEFAULT 'todos',
    conteudo TEXT NOT NULL,
    fixado TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('ativo', 'inativo') NOT NULL DEFAULT 'ativo',
    autor_id INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_comunicados_rede (rede_id),
    INDEX idx_comunicados_unidade (unidade_id),
    INDEX idx_comunicados_status (status),
    CONSTRAINT fk_comunicados_rede FOREIGN KEY (rede_id) REFERENCES redes(id) ON DELETE CASCADE,
    CONSTRAINT fk_comunicados_unidade FOREIGN KEY (unidade_id) REFERENCES unidades(id) ON DELETE SET NULL,
    CONSTRAINT fk_comunicados_autor FOREIGN KEY (autor_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rede_conteudos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rede_id INT NOT NULL UNIQUE,
    slogan VARCHAR(255) NULL,
    descricao TEXT NULL,
    horario_funcionamento VARCHAR(255) NULL,
    telefone_contato VARCHAR(50) NULL,
    email_contato VARCHAR(191) NULL,
    banner_aviso VARCHAR(255) NULL,
    banner_ativo TINYINT(1) NOT NULL DEFAULT 0,
    regras_gerais TEXT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_rede_conteudos_rede FOREIGN KEY (rede_id) REFERENCES redes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
