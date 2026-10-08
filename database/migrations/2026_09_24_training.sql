CREATE TABLE IF NOT EXISTS gfit_treinos (
 id INT AUTO_INCREMENT PRIMARY KEY, rede_id INT NOT NULL, aluno_id INT NOT NULL,
 profissional_id INT NOT NULL, titulo VARCHAR(150) NOT NULL, objetivo VARCHAR(255) NOT NULL,
 exercicios TEXT NOT NULL, status ENUM('ativo','arquivado') NOT NULL DEFAULT 'ativo',
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_treino_aluno(rede_id,aluno_id),
 FOREIGN KEY (rede_id) REFERENCES redes(id), FOREIGN KEY (aluno_id) REFERENCES usuarios(id),
 FOREIGN KEY (profissional_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS gfit_avaliacoes (
 id INT AUTO_INCREMENT PRIMARY KEY, rede_id INT NOT NULL, aluno_id INT NOT NULL,
 profissional_id INT NOT NULL, peso DECIMAL(5,2) NOT NULL, altura DECIMAL(5,2) NOT NULL,
 observacoes TEXT NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_avaliacao_aluno(rede_id,aluno_id),
 FOREIGN KEY (rede_id) REFERENCES redes(id), FOREIGN KEY (aluno_id) REFERENCES usuarios(id),
 FOREIGN KEY (profissional_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS gfit_frequencias (
 id INT AUTO_INCREMENT PRIMARY KEY, rede_id INT NOT NULL, aluno_id INT NOT NULL,
 treino_id INT NOT NULL, dia DATE NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uk_frequencia(aluno_id,treino_id,dia), INDEX idx_frequencia_rede(rede_id,dia),
 FOREIGN KEY (rede_id) REFERENCES redes(id), FOREIGN KEY (aluno_id) REFERENCES usuarios(id),
 FOREIGN KEY (treino_id) REFERENCES gfit_treinos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
