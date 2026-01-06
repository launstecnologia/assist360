-- Adicionar campo validacao_bolsao na tabela solicitacoes
-- Este campo indica se o CPF foi validado na listagem da imobiliária (1 = Bolsão)
ALTER TABLE `solicitacoes` 
ADD COLUMN IF NOT EXISTS `validacao_bolsao` TINYINT(1) NOT NULL DEFAULT 0 
COMMENT 'Validação Bolsão: 1 = CPF validado na listagem da imobiliária, 0 = Não validado' 
AFTER `descricao_card`;

-- Adicionar campo validacao_bolsao na tabela solicitacoes_manuais
ALTER TABLE `solicitacoes_manuais` 
ADD COLUMN IF NOT EXISTS `validacao_bolsao` TINYINT(1) NOT NULL DEFAULT 0 
COMMENT 'Validação Bolsão: 1 = CPF validado na listagem da imobiliária, 0 = Não validado' 
AFTER `descricao_problema`;

-- Adicionar índices para melhor performance
ALTER TABLE `solicitacoes`
ADD INDEX IF NOT EXISTS `idx_validacao_bolsao` (`validacao_bolsao`);

ALTER TABLE `solicitacoes_manuais`
ADD INDEX IF NOT EXISTS `idx_validacao_bolsao` (`validacao_bolsao`);

