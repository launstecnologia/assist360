-- =====================================================
-- ADICIONAR CAMPOS DE INFORMAÇÕES DO SERVIÇO NA TABELA solicitacoes
-- =====================================================
-- Script simplificado sem PREPARE/EXECUTE para evitar problemas
-- =====================================================

-- Adicionar coluna local_manutencao (se não existir)
ALTER TABLE `solicitacoes` 
ADD COLUMN IF NOT EXISTS `local_manutencao` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Local onde será realizada a manutenção' AFTER `descricao_card`;

-- Adicionar coluna finalidade_locacao (se não existir)
ALTER TABLE `solicitacoes` 
ADD COLUMN IF NOT EXISTS `finalidade_locacao` ENUM('RESIDENCIAL', 'COMERCIAL') NULL DEFAULT NULL COMMENT 'Finalidade da locação: Residencial ou Comercial' AFTER `local_manutencao`;

-- Adicionar coluna tipo_imovel (se não existir)
ALTER TABLE `solicitacoes` 
ADD COLUMN IF NOT EXISTS `tipo_imovel` ENUM('CASA', 'APARTAMENTO') NULL DEFAULT NULL COMMENT 'Tipo do imóvel: Casa ou Apartamento' AFTER `finalidade_locacao`;

-- Verificar resultado
SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'solicitacoes'
AND COLUMN_NAME IN ('local_manutencao', 'finalidade_locacao', 'tipo_imovel')
ORDER BY ORDINAL_POSITION;

