-- Adicionar campo is_lida na tabela solicitacao_mensagens
ALTER TABLE `solicitacao_mensagens` 
ADD COLUMN IF NOT EXISTS `is_lida` TINYINT(1) DEFAULT 0 COMMENT 'Se a mensagem foi lida (1) ou não (0)',
ADD KEY `idx_is_lida` (`is_lida`),
ADD KEY `idx_tipo_lida` (`tipo`, `is_lida`);

-- Atualizar registros existentes: se status = 'LIDA', então is_lida = 1
UPDATE `solicitacao_mensagens` 
SET `is_lida` = 1 
WHERE `status` = 'LIDA' AND `tipo` = 'RECEBIDA';

