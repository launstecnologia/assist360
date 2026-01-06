-- Adicionar coluna observacao_qualificacao para armazenar observações específicas de Cortesia ou Não Qualificado

-- Tabela solicitacoes
ALTER TABLE `solicitacoes` 
ADD COLUMN IF NOT EXISTS `observacao_qualificacao` TEXT NULL DEFAULT NULL 
COMMENT 'Observação específica para qualificação (Cortesia ou Não Qualificado)'
AFTER `tipo_qualificacao`;

-- Tabela solicitacoes_manuais
ALTER TABLE `solicitacoes_manuais` 
ADD COLUMN IF NOT EXISTS `observacao_qualificacao` TEXT NULL DEFAULT NULL 
COMMENT 'Observação específica para qualificação (Cortesia ou Não Qualificado)'
AFTER `tipo_qualificacao`;

