-- Criar tabela condicoes
CREATE TABLE IF NOT EXISTS `condicoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `cor` varchar(7) NOT NULL DEFAULT '#3B82F6',
  `icone` varchar(50) DEFAULT NULL,
  `ordem` int(11) DEFAULT 1,
  `status` enum('ATIVO','INATIVO') NOT NULL DEFAULT 'ATIVO',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_ordem` (`ordem`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adicionar coluna condicao_id na tabela solicitacoes
ALTER TABLE `solicitacoes` 
ADD COLUMN IF NOT EXISTS `condicao_id` int(11) DEFAULT NULL,
ADD KEY `idx_condicao_id` (`condicao_id`),
ADD CONSTRAINT `fk_solicitacoes_condicao` FOREIGN KEY (`condicao_id`) REFERENCES `condicoes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

