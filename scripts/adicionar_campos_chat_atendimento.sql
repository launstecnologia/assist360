-- Adicionar campos para controle de instância WhatsApp por atendimento
ALTER TABLE `solicitacoes` 
ADD COLUMN IF NOT EXISTS `chat_whatsapp_instance_id` INT NULL COMMENT 'ID da instância WhatsApp em uso no chat',
ADD COLUMN IF NOT EXISTS `chat_atendimento_ativo` TINYINT(1) DEFAULT 0 COMMENT 'Se o atendimento via chat está ativo (1) ou encerrado (0)',
ADD COLUMN IF NOT EXISTS `chat_atendimento_iniciado_em` DATETIME NULL COMMENT 'Data/hora de início do atendimento',
ADD COLUMN IF NOT EXISTS `chat_atendimento_encerrado_em` DATETIME NULL COMMENT 'Data/hora de encerramento do atendimento',
ADD KEY `idx_chat_instance` (`chat_whatsapp_instance_id`),
ADD KEY `idx_chat_ativo` (`chat_atendimento_ativo`),
ADD CONSTRAINT `fk_solicitacoes_chat_instance` FOREIGN KEY (`chat_whatsapp_instance_id`) REFERENCES `whatsapp_instances` (`id`) ON DELETE SET NULL;

