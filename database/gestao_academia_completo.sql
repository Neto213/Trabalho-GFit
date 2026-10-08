-- ==========================================================
-- SCRIPT COMPLETO DE BANCO DE DADOS: gestao_academia
-- Compatível com MySQL 5.7+ / MariaDB / phpMyAdmin (XAMPP)
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `gestao_academia` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `gestao_academia`;

SET FOREIGN_KEY_CHECKS = 0;

-- Estrutura da tabela `redes`
DROP TABLE IF EXISTS `redes`;
CREATE TABLE `redes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `status` enum('ativo','inativo') NOT NULL DEFAULT 'ativo',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dados iniciais da tabela `redes`
INSERT INTO `redes` (`id`, `nome`, `status`, `created_at`, `updated_at`) VALUES ('1', 'Rede Alpha Fit', 'ativo', '2026-09-10 11:14:33', '2026-09-10 11:14:33');
INSERT INTO `redes` (`id`, `nome`, `status`, `created_at`, `updated_at`) VALUES ('2', 'Rede Beta Saúde', 'ativo', '2026-09-10 11:14:33', '2026-09-10 11:14:33');
INSERT INTO `redes` (`id`, `nome`, `status`, `created_at`, `updated_at`) VALUES ('3', 'Rede Delta Inativa', 'inativo', '2026-09-10 11:14:33', '2026-09-10 11:14:33');

-- Estrutura da tabela `unidades`
DROP TABLE IF EXISTS `unidades`;
CREATE TABLE `unidades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rede_id` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `status` enum('ativo','inativo') NOT NULL DEFAULT 'ativo',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_unidades_rede` (`rede_id`),
  CONSTRAINT `fk_unidades_rede` FOREIGN KEY (`rede_id`) REFERENCES `redes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dados iniciais da tabela `unidades`
INSERT INTO `unidades` (`id`, `rede_id`, `nome`, `status`, `created_at`, `updated_at`) VALUES ('1', '1', 'Alpha Centro', 'ativo', '2026-09-10 11:14:33', '2026-09-10 11:14:33');
INSERT INTO `unidades` (`id`, `rede_id`, `nome`, `status`, `created_at`, `updated_at`) VALUES ('2', '1', 'Alpha Jardins', 'ativo', '2026-09-10 11:14:33', '2026-09-10 11:14:33');
INSERT INTO `unidades` (`id`, `rede_id`, `nome`, `status`, `created_at`, `updated_at`) VALUES ('3', '2', 'Beta Norte', 'ativo', '2026-09-10 11:14:33', '2026-09-10 11:14:33');
INSERT INTO `unidades` (`id`, `rede_id`, `nome`, `status`, `created_at`, `updated_at`) VALUES ('4', '3', 'Delta Sul', 'inativo', '2026-09-10 11:14:33', '2026-09-10 11:14:33');

-- Estrutura da tabela `usuarios`
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rede_id` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `email` varchar(191) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `perfil` enum('admin','profissional','aluno') NOT NULL,
  `status` enum('ativo','inativo') NOT NULL DEFAULT 'ativo',
  `session_version` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_usuarios_rede` (`rede_id`),
  CONSTRAINT `fk_usuarios_rede` FOREIGN KEY (`rede_id`) REFERENCES `redes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dados iniciais da tabela `usuarios`
INSERT INTO `usuarios` (`id`, `rede_id`, `nome`, `email`, `senha_hash`, `perfil`, `status`, `session_version`, `created_at`, `updated_at`) VALUES ('1', '1', 'Carlos Administrador', 'admin@alphafit.com.br', '$2y$10$yOnOPiQwU5uWipM0F/1r7uYibjElrm/sy2N/WYOx0T7qmJ32MOgpO', 'admin', 'ativo', '15', '2026-09-10 11:14:33', '2026-09-10 11:35:35');
INSERT INTO `usuarios` (`id`, `rede_id`, `nome`, `email`, `senha_hash`, `perfil`, `status`, `session_version`, `created_at`, `updated_at`) VALUES ('2', '1', 'Renata Profissional', 'personal@alphafit.com.br', '$2y$10$myTu5/ppT143XqX8OlXiTeXrUgx70LWejQdx5yQ1.1Jz0FCxjGmY2', 'profissional', 'ativo', '1', '2026-09-10 11:14:34', '2026-09-10 11:30:23');
INSERT INTO `usuarios` (`id`, `rede_id`, `nome`, `email`, `senha_hash`, `perfil`, `status`, `session_version`, `created_at`, `updated_at`) VALUES ('3', '1', 'Lucas Aluno', 'aluno@alphafit.com.br', '$2y$10$MxtanxSdAmu/5d9zct.ygumdVa/FtDx1hidBGRmHe0oLGfC9gKBxO', 'aluno', 'ativo', '1', '2026-09-10 11:14:34', '2026-09-10 11:30:23');
INSERT INTO `usuarios` (`id`, `rede_id`, `nome`, `email`, `senha_hash`, `perfil`, `status`, `session_version`, `created_at`, `updated_at`) VALUES ('4', '1', 'Mariana Inativa', 'inativo@alphafit.com.br', '$2y$10$4SlCXPKcnwqLgieBU.6TnuiEBR.iznQBvb00./FuiZ0LUiMS.KILq', 'aluno', 'inativo', '1', '2026-09-10 11:14:34', '2026-09-10 11:30:24');
INSERT INTO `usuarios` (`id`, `rede_id`, `nome`, `email`, `senha_hash`, `perfil`, `status`, `session_version`, `created_at`, `updated_at`) VALUES ('5', '3', 'Eduardo Rede Inativa', 'bloqueado@deltainativa.com.br', '$2y$10$Dkic66wM5z60hE.BFLyEhuDOyOb/13sAhF5THLuZptXctEsnM9KuW', 'admin', 'ativo', '1', '2026-09-10 11:14:34', '2026-09-10 11:30:24');
INSERT INTO `usuarios` (`id`, `rede_id`, `nome`, `email`, `senha_hash`, `perfil`, `status`, `session_version`, `created_at`, `updated_at`) VALUES ('6', '2', 'Beatriz Admin Beta', 'admin@betasaude.com.br', '$2y$10$R92LNPOHUYcOafne3cAH2OtB6lLFL8CO/cPl1RPBGkIAx/qyVUDdq', 'admin', 'ativo', '1', '2026-09-10 11:14:34', '2026-09-10 11:30:24');

-- Estrutura da tabela `usuario_unidades`
DROP TABLE IF EXISTS `usuario_unidades`;
CREATE TABLE `usuario_unidades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `unidade_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_usuario_unidade` (`usuario_id`,`unidade_id`),
  KEY `fk_usuario_unidades_unidade` (`unidade_id`),
  CONSTRAINT `fk_usuario_unidades_unidade` FOREIGN KEY (`unidade_id`) REFERENCES `unidades` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_usuario_unidades_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dados iniciais da tabela `usuario_unidades`
INSERT INTO `usuario_unidades` (`id`, `usuario_id`, `unidade_id`, `created_at`) VALUES ('1', '1', '1', '2026-09-10 11:14:33');
INSERT INTO `usuario_unidades` (`id`, `usuario_id`, `unidade_id`, `created_at`) VALUES ('2', '1', '2', '2026-09-10 11:14:33');
INSERT INTO `usuario_unidades` (`id`, `usuario_id`, `unidade_id`, `created_at`) VALUES ('3', '2', '1', '2026-09-10 11:14:34');
INSERT INTO `usuario_unidades` (`id`, `usuario_id`, `unidade_id`, `created_at`) VALUES ('4', '3', '1', '2026-09-10 11:14:34');
INSERT INTO `usuario_unidades` (`id`, `usuario_id`, `unidade_id`, `created_at`) VALUES ('5', '4', '1', '2026-09-10 11:14:34');
INSERT INTO `usuario_unidades` (`id`, `usuario_id`, `unidade_id`, `created_at`) VALUES ('6', '5', '4', '2026-09-10 11:14:34');
INSERT INTO `usuario_unidades` (`id`, `usuario_id`, `unidade_id`, `created_at`) VALUES ('7', '6', '3', '2026-09-10 11:14:34');

-- Estrutura da tabela `recuperacao_senha`
DROP TABLE IF EXISTS `recuperacao_senha`;
CREATE TABLE `recuperacao_senha` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_token_hash` (`token_hash`),
  KEY `fk_recuperacao_usuario` (`usuario_id`),
  CONSTRAINT `fk_recuperacao_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dados iniciais da tabela `recuperacao_senha`
INSERT INTO `recuperacao_senha` (`id`, `usuario_id`, `token_hash`, `expires_at`, `used_at`, `created_at`) VALUES ('1', '3', '9e897c086db7ebfdb15050e1e39e005a5f30eb5b5fda9c815711151433c80265', '2026-09-10 16:48:53', '2026-09-10 11:19:06', '2026-09-10 11:18:53');
INSERT INTO `recuperacao_senha` (`id`, `usuario_id`, `token_hash`, `expires_at`, `used_at`, `created_at`) VALUES ('2', '1', 'b5e68652c337e4904b2fa07c0a7bb647b0124ebc2ea9679978931ddaaaa6198c', '2026-09-10 16:48:53', '2026-09-10 11:18:53', '2026-09-10 11:18:53');
INSERT INTO `recuperacao_senha` (`id`, `usuario_id`, `token_hash`, `expires_at`, `used_at`, `created_at`) VALUES ('3', '3', '592b7b6643b8e0222aa02d138c446f2cd7820e55628167fcff3ed9daadbf8a56', '2026-09-10 16:49:06', '2026-09-10 11:29:06', '2026-09-10 11:19:06');
INSERT INTO `recuperacao_senha` (`id`, `usuario_id`, `token_hash`, `expires_at`, `used_at`, `created_at`) VALUES ('4', '1', 'f26cb855a3a29cd9b011283467ef839e70e0fdb3bc46ea942243d571dd31c587', '2026-09-10 16:49:06', '2026-09-10 11:19:06', '2026-09-10 11:19:06');
INSERT INTO `recuperacao_senha` (`id`, `usuario_id`, `token_hash`, `expires_at`, `used_at`, `created_at`) VALUES ('5', '3', 'eb3a288f0ebe0b38a2df274359e1d035ad4654fc9aeef24ad3c69e1d0edcc8a3', '2026-09-10 16:59:06', '2026-09-10 11:31:01', '2026-09-10 11:29:06');
INSERT INTO `recuperacao_senha` (`id`, `usuario_id`, `token_hash`, `expires_at`, `used_at`, `created_at`) VALUES ('6', '1', 'afc504c47a4a9a9c74ced07d97151c5b07edfbe4ab771d12cc5127ba3fe7cf84', '2026-09-10 16:59:06', '2026-09-10 11:29:06', '2026-09-10 11:29:06');
INSERT INTO `recuperacao_senha` (`id`, `usuario_id`, `token_hash`, `expires_at`, `used_at`, `created_at`) VALUES ('7', '1', '89ab468d4ac68bc5628e18eebf3f459377a6afce1d83bff3d9514a798a7e88be', '2026-09-10 17:00:27', '2026-09-10 11:30:27', '2026-09-10 11:30:27');
INSERT INTO `recuperacao_senha` (`id`, `usuario_id`, `token_hash`, `expires_at`, `used_at`, `created_at`) VALUES ('8', '3', '99388fe6b861af484531d02152dc5d7b21a1c98b1a10a1f65df9fbd034335087', '2026-09-10 17:01:01', '2026-09-10 11:34:26', '2026-09-10 11:31:01');
INSERT INTO `recuperacao_senha` (`id`, `usuario_id`, `token_hash`, `expires_at`, `used_at`, `created_at`) VALUES ('9', '1', 'aa8c3c18cf7a0fb98ea2ab736a43b7793fa83702a37100e9945bb72eb4660183', '2026-09-10 17:01:01', '2026-09-10 11:31:01', '2026-09-10 11:31:01');
INSERT INTO `recuperacao_senha` (`id`, `usuario_id`, `token_hash`, `expires_at`, `used_at`, `created_at`) VALUES ('10', '3', 'e788d3b4e159db8d10b31d679b00fbc8828c566286e0ced5f6aff0050709f037', '2026-09-10 17:04:26', '2026-09-10 11:35:35', '2026-09-10 11:34:26');
INSERT INTO `recuperacao_senha` (`id`, `usuario_id`, `token_hash`, `expires_at`, `used_at`, `created_at`) VALUES ('11', '1', '3d9b8e81ed489a5ce17d6547d4c652a3c9848457372aa7f1c4722532028c6e91', '2026-09-10 17:04:26', '2026-09-10 11:34:26', '2026-09-10 11:34:26');
INSERT INTO `recuperacao_senha` (`id`, `usuario_id`, `token_hash`, `expires_at`, `used_at`, `created_at`) VALUES ('12', '3', 'e07a09b7e88f33ed5f18506cdf13987590e4146727f7f0dc971984a2e388cd99', '2026-09-10 17:05:35', NULL, '2026-09-10 11:35:35');
INSERT INTO `recuperacao_senha` (`id`, `usuario_id`, `token_hash`, `expires_at`, `used_at`, `created_at`) VALUES ('13', '1', 'f4e4b32832775594cd8541c87aeed11cbea9630fc2d3a78c3f947f9667f314ad', '2026-09-10 17:05:35', '2026-09-10 11:35:35', '2026-09-10 11:35:35');

-- Estrutura da tabela `tentativas_acesso`
DROP TABLE IF EXISTS `tentativas_acesso`;
CREATE TABLE `tentativas_acesso` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chave_identificadora` varchar(255) NOT NULL,
  `tipo` varchar(32) NOT NULL,
  `tentativas` int(11) NOT NULL DEFAULT 1,
  `bloqueado_ate` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_chave_tipo` (`chave_identificadora`,`tipo`)
) ENGINE=InnoDB AUTO_INCREMENT=69 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dados iniciais da tabela `tentativas_acesso`
INSERT INTO `tentativas_acesso` (`id`, `chave_identificadora`, `tipo`, `tentativas`, `bloqueado_ate`, `created_at`, `updated_at`) VALUES ('60', 'login:192.168.1.100:inexistente@alphafit.com.br', 'login', '1', NULL, '2026-09-10 11:35:35', '2026-09-10 11:35:35');
INSERT INTO `tentativas_acesso` (`id`, `chave_identificadora`, `tipo`, `tentativas`, `bloqueado_ate`, `created_at`, `updated_at`) VALUES ('66', 'recovery:192.168.1.100:aluno@alphafit.com.br', 'recuperacao', '1', NULL, '2026-09-10 11:35:35', '2026-09-10 11:35:35');
INSERT INTO `tentativas_acesso` (`id`, `chave_identificadora`, `tipo`, `tentativas`, `bloqueado_ate`, `created_at`, `updated_at`) VALUES ('67', 'recovery:192.168.1.100:fantasma@inexistente.com', 'recuperacao', '1', NULL, '2026-09-10 11:35:35', '2026-09-10 11:35:35');

-- Estrutura da tabela `conteudos_comunicados`
DROP TABLE IF EXISTS `conteudos_comunicados`;
CREATE TABLE `conteudos_comunicados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rede_id` int(11) NOT NULL,
  `unidade_id` int(11) DEFAULT NULL,
  `titulo` varchar(200) NOT NULL,
  `categoria` varchar(50) NOT NULL DEFAULT 'Geral',
  `publico_alvo` enum('todos','alunos','profissionais') NOT NULL DEFAULT 'todos',
  `conteudo` text NOT NULL,
  `fixado` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('ativo','inativo') NOT NULL DEFAULT 'ativo',
  `autor_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_comunicados_rede` (`rede_id`),
  KEY `idx_comunicados_unidade` (`unidade_id`),
  KEY `idx_comunicados_status` (`status`),
  KEY `fk_comunicados_autor` (`autor_id`),
  CONSTRAINT `fk_comunicados_autor` FOREIGN KEY (`autor_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comunicados_rede` FOREIGN KEY (`rede_id`) REFERENCES `redes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comunicados_unidade` FOREIGN KEY (`unidade_id`) REFERENCES `unidades` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dados iniciais da tabela `conteudos_comunicados`
INSERT INTO `conteudos_comunicados` (`id`, `rede_id`, `unidade_id`, `titulo`, `categoria`, `publico_alvo`, `conteudo`, `fixado`, `status`, `autor_id`, `created_at`, `updated_at`) VALUES ('1', '1', NULL, 'Bem-vindo à nova plataforma de Gestão de Academia', 'Novidades', 'todos', 'Estamos felizes em apresentar o novo portal de gestão da academia. Aqui você acompanha avisos, treinos e informações da sua unidade com máxima agilidade e segurança.', '1', 'ativo', '1', '2026-09-10 11:30:24', '2026-09-10 11:30:24');
INSERT INTO `conteudos_comunicados` (`id`, `rede_id`, `unidade_id`, `titulo`, `categoria`, `publico_alvo`, `conteudo`, `fixado`, `status`, `autor_id`, `created_at`, `updated_at`) VALUES ('2', '1', '1', 'Manutenção preventiva dos equipamentos da Unidade Centro', 'Manutenção', 'alunos', 'Nesta sexta-feira realizaremos a revisão das esteiras e aparelhos de cabo da Unidade Alpha Centro entre 13:00 e 15:00. O espaço permanecerá liberado para uso parcial.', '0', 'ativo', '1', '2026-09-10 11:30:24', '2026-09-10 11:30:24');
INSERT INTO `conteudos_comunicados` (`id`, `rede_id`, `unidade_id`, `titulo`, `categoria`, `publico_alvo`, `conteudo`, `fixado`, `status`, `autor_id`, `created_at`, `updated_at`) VALUES ('3', '1', NULL, 'Alinhamento com a equipe de profissionais e instrutores', 'Operacional', 'profissionais', 'Lembramos a todos os profissionais da Rede Alpha que os registros de novos treinos e avaliações devem seguir os protocolos padronizados de biossegurança e postura.', '0', 'ativo', '1', '2026-09-10 11:30:24', '2026-09-10 11:30:24');
INSERT INTO `conteudos_comunicados` (`id`, `rede_id`, `unidade_id`, `titulo`, `categoria`, `publico_alvo`, `conteudo`, `fixado`, `status`, `autor_id`, `created_at`, `updated_at`) VALUES ('5', '1', NULL, 'Aulão de Funcional ao Ar Livre', 'Eventos', 'todos', 'Neste sábado às 9h teremos aulão no estacionamento com hidratação gratuita.', '1', 'ativo', '1', '2026-09-10 11:35:29', '2026-09-10 11:35:29');

-- Estrutura da tabela `rede_conteudos`
DROP TABLE IF EXISTS `rede_conteudos`;
CREATE TABLE `rede_conteudos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rede_id` int(11) NOT NULL,
  `slogan` varchar(255) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `horario_funcionamento` varchar(255) DEFAULT NULL,
  `telefone_contato` varchar(50) DEFAULT NULL,
  `email_contato` varchar(191) DEFAULT NULL,
  `banner_aviso` varchar(255) DEFAULT NULL,
  `banner_ativo` tinyint(1) NOT NULL DEFAULT 0,
  `regras_gerais` text DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `rede_id` (`rede_id`),
  CONSTRAINT `fk_rede_conteudos_rede` FOREIGN KEY (`rede_id`) REFERENCES `redes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dados iniciais da tabela `rede_conteudos`
INSERT INTO `rede_conteudos` (`id`, `rede_id`, `slogan`, `descricao`, `horario_funcionamento`, `telefone_contato`, `email_contato`, `banner_aviso`, `banner_ativo`, `regras_gerais`, `updated_at`) VALUES ('1', '1', 'Sua melhor versão começa aqui.', 'Academia de alta performance.', '06h às 23h', '11999999999', 'contato@alphafit.com.br', 'Feriado com horário especial', '1', 'Uso de toalha obrigatório', '2026-09-10 11:34:27');
INSERT INTO `rede_conteudos` (`id`, `rede_id`, `slogan`, `descricao`, `horario_funcionamento`, `telefone_contato`, `email_contato`, `banner_aviso`, `banner_ativo`, `regras_gerais`, `updated_at`) VALUES ('2', '2', 'Saúde e movimento todos os dias.', 'Rede Beta Saúde com foco em longevidade e condicionamento físico.', 'Seg a Sex: 06:00 às 22:00 | Sáb: 08:00 às 14:00', '(11) 91234-5678', 'atendimento@betasaude.com.br', '', '0', 'Respeite as orientações dos instrutores.', '2026-09-10 11:30:24');

SET FOREIGN_KEY_CHECKS = 1;
