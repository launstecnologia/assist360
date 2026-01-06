-- Atualizar ENUM tipo_qualificacao para incluir BOLSAO e REGRA_2
-- Valores: BOLSAO, CORTESIA, NAO_QUALIFICADA, REGRA_2

-- Para tabela solicitacoes
ALTER TABLE `solicitacoes` 
MODIFY COLUMN `tipo_qualificacao` ENUM('BOLSAO', 'CORTESIA', 'NAO_QUALIFICADA', 'REGRA_2') NULL DEFAULT NULL 
COMMENT 'Tipo de Qualificação: BOLSAO = CPF no bolsão, CORTESIA = cortesia escolhida pelo admin, NAO_QUALIFICADA = excedeu limite ou admin escolheu, REGRA_2 = validação por data assinatura';

-- Para tabela solicitacoes_manuais
ALTER TABLE `solicitacoes_manuais` 
MODIFY COLUMN `tipo_qualificacao` ENUM('BOLSAO', 'CORTESIA', 'NAO_QUALIFICADA', 'REGRA_2') NULL DEFAULT NULL 
COMMENT 'Tipo de Qualificação: BOLSAO = CPF no bolsão, CORTESIA = escolhida pelo admin, NAO_QUALIFICADA = escolhida pelo admin, REGRA_2 = validação por data assinatura';

