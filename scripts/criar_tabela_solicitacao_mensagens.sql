-- Tabela para armazenar mensagens do chat entre admin e locatário
CREATE TABLE IF NOT EXISTS `solicitacao_mensagens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `solicitacao_id` int(11) NOT NULL COMMENT 'ID da solicitação',
  `whatsapp_instance_id` int(11) DEFAULT NULL COMMENT 'ID da instância WhatsApp usada',
  `instance_name` varchar(100) DEFAULT NULL COMMENT 'Nome da instância na Evolution API',
  `numero_remetente` varchar(20) NOT NULL COMMENT 'Número do remetente (admin ou locatário)',
  `numero_destinatario` varchar(20) NOT NULL COMMENT 'Número do destinatário',
  `mensagem` text NOT NULL COMMENT 'Texto da mensagem',
  `tipo` enum('ENVIADA','RECEBIDA') NOT NULL DEFAULT 'ENVIADA' COMMENT 'Tipo: ENVIADA (admin->locatário) ou RECEBIDA (locatário->admin)',
  `status` enum('ENVIANDO','ENVIADA','ENTREGUE','LIDA','ERRO') NOT NULL DEFAULT 'ENVIANDO' COMMENT 'Status da mensagem',
  `message_id` varchar(255) DEFAULT NULL COMMENT 'ID da mensagem retornado pela Evolution API',
  `erro` text DEFAULT NULL COMMENT 'Mensagem de erro se houver',
  `metadata` json DEFAULT NULL COMMENT 'Metadados adicionais (timestamps da API, etc)',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_solicitacao` (`solicitacao_id`),
  KEY `idx_instance` (`whatsapp_instance_id`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_solicitacao_mensagens_solicitacao` FOREIGN KEY (`solicitacao_id`) REFERENCES `solicitacoes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_solicitacao_mensagens_instance` FOREIGN KEY (`whatsapp_instance_id`) REFERENCES `whatsapp_instances` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Mensagens do chat entre admin e locatário via WhatsApp';

