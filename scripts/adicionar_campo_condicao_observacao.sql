-- Adicionar campo condicao_observacao na tabela solicitacoes
-- Este campo armazena observações quando a condição é "outros"
ALTER TABLE `solicitacoes` 
ADD COLUMN IF NOT EXISTS `condicao_observacao` TEXT NULL DEFAULT NULL 
COMMENT 'Observação quando condição é "outros"' 
AFTER `condicao_id`;

