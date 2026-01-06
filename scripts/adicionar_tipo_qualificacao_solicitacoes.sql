-- Adicionar campo tipo_qualificacao na tabela solicitacoes
-- Tipos: BOLSAO (CPF no bolsão), CORTESIA (admin escolheu), NAO_QUALIFICADA (admin escolheu ou excedeu limite), REGRA_2 (validação por data de assinatura)
ALTER TABLE `solicitacoes` 
ADD COLUMN IF NOT EXISTS `tipo_qualificacao` ENUM('BOLSAO', 'CORTESIA', 'NAO_QUALIFICADA', 'REGRA_2') NULL DEFAULT NULL 
COMMENT 'Tipo de Qualificação: BOLSAO = CPF no bolsão, CORTESIA = cortesia escolhida pelo admin, NAO_QUALIFICADA = excedeu limite ou admin escolheu, REGRA_2 = validação por data assinatura' 
AFTER `validacao_bolsao`;

-- Adicionar campo tipo_qualificacao na tabela solicitacoes_manuais
-- Tipos: BOLSAO (CPF no bolsão), CORTESIA (admin escolheu), NAO_QUALIFICADA (admin escolheu), REGRA_2 (validação por data de assinatura)
ALTER TABLE `solicitacoes_manuais` 
ADD COLUMN IF NOT EXISTS `tipo_qualificacao` ENUM('BOLSAO', 'CORTESIA', 'NAO_QUALIFICADA', 'REGRA_2') NULL DEFAULT NULL 
COMMENT 'Tipo de Qualificação: BOLSAO = CPF no bolsão, CORTESIA = escolhida pelo admin, NAO_QUALIFICADA = escolhida pelo admin, REGRA_2 = validação por data assinatura' 
AFTER `validacao_bolsao`;

-- Atualizar registros existentes baseado na validação_bolsao
-- Solicitações normais: manter como CORTESIA (já é o default)
-- Solicitações manuais: definir como NAO_QUALIFICADA se validacao_bolsao = 0
UPDATE `solicitacoes_manuais` 
SET `tipo_qualificacao` = 'NAO_QUALIFICADA' 
WHERE `validacao_bolsao` = 0;

-- Adicionar índices para melhor performance
ALTER TABLE `solicitacoes`
ADD INDEX IF NOT EXISTS `idx_tipo_qualificacao` (`tipo_qualificacao`);

ALTER TABLE `solicitacoes_manuais`
ADD INDEX IF NOT EXISTS `idx_tipo_qualificacao` (`tipo_qualificacao`);


