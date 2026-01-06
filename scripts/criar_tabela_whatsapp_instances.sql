-- Criar tabela para gerenciar instâncias da Evolution API
CREATE TABLE IF NOT EXISTS `whatsapp_instances` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL COMMENT 'Nome da instância (ex: Notificações Principal)',
  `instance_name` varchar(100) NOT NULL UNIQUE COMMENT 'Nome da instância na Evolution API',
  `numero_whatsapp` varchar(20) DEFAULT NULL COMMENT 'Número do WhatsApp conectado',
  `qrcode` text DEFAULT NULL COMMENT 'QR Code base64 para conexão',
  `status` enum('DESCONECTADO','CONECTANDO','CONECTADO','DESCONECTANDO') NOT NULL DEFAULT 'DESCONECTADO',
  `is_ativo` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Se está ativo para envio de notificações',
  `is_padrao` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Se é a instância padrão para envio',
  `api_url` varchar(255) NOT NULL COMMENT 'URL base da Evolution API',
  `api_key` varchar(255) DEFAULT NULL COMMENT 'API Key da Evolution API',
  `token` varchar(255) DEFAULT NULL COMMENT 'Token de autenticação (Bearer)',
  `observacoes` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_instance_name` (`instance_name`),
  KEY `idx_status` (`status`),
  KEY `idx_ativo` (`is_ativo`),
  KEY `idx_padrao` (`is_padrao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Instâncias da Evolution API para envio de WhatsApp';

