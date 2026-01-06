-- =====================================================
-- ADICIONAR CAMPOS DE INFORMAÇÕES DO SERVIÇO NA TABELA solicitacoes
-- =====================================================
-- Este script adiciona as colunas:
--   - local_manutencao: Local onde será realizada a manutenção
--   - finalidade_locacao: Finalidade da locação (RESIDENCIAL ou COMERCIAL)
--   - tipo_imovel: Tipo do imóvel (CASA ou APARTAMENTO)
-- =====================================================

START TRANSACTION;

-- Verificar e adicionar coluna local_manutencao
SET @col_exists_local = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'solicitacoes'
    AND COLUMN_NAME = 'local_manutencao'
);

SET @sql_local = IF(@col_exists_local = 0,
    'ALTER TABLE `solicitacoes` ADD COLUMN `local_manutencao` VARCHAR(255) NULL DEFAULT NULL COMMENT ''Local onde será realizada a manutenção'' AFTER `descricao_card`',
    'SELECT "Coluna local_manutencao já existe" AS mensagem'
);

PREPARE stmt_local FROM @sql_local;
EXECUTE stmt_local;
DEALLOCATE PREPARE stmt_local;

-- Verificar e adicionar coluna finalidade_locacao
SET @col_exists_finalidade = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'solicitacoes'
    AND COLUMN_NAME = 'finalidade_locacao'
);

SET @sql_finalidade = IF(@col_exists_finalidade = 0,
    'ALTER TABLE `solicitacoes` ADD COLUMN `finalidade_locacao` ENUM(''RESIDENCIAL'', ''COMERCIAL'') NULL DEFAULT NULL COMMENT ''Finalidade da locação: Residencial ou Comercial'' AFTER `local_manutencao`',
    'SELECT "Coluna finalidade_locacao já existe" AS mensagem'
);

PREPARE stmt_finalidade FROM @sql_finalidade;
EXECUTE stmt_finalidade;
DEALLOCATE PREPARE stmt_finalidade;

-- Verificar e adicionar coluna tipo_imovel
SET @col_exists_tipo = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'solicitacoes'
    AND COLUMN_NAME = 'tipo_imovel'
);

SET @sql_tipo = IF(@col_exists_tipo = 0,
    'ALTER TABLE `solicitacoes` ADD COLUMN `tipo_imovel` ENUM(''CASA'', ''APARTAMENTO'') NULL DEFAULT NULL COMMENT ''Tipo do imóvel: Casa ou Apartamento'' AFTER `finalidade_locacao`',
    'SELECT "Coluna tipo_imovel já existe" AS mensagem'
);

PREPARE stmt_tipo FROM @sql_tipo;
EXECUTE stmt_tipo;
DEALLOCATE PREPARE stmt_tipo;

COMMIT;

-- Verificar resultado - mostrar as colunas adicionadas
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

-- Mensagem de sucesso
SELECT 
    'Script executado com sucesso!' AS resultado,
    CONCAT(
        'Colunas verificadas/criadas: ',
        IF(@col_exists_local = 0, 'local_manutencao ', ''),
        IF(@col_exists_finalidade = 0, 'finalidade_locacao ', ''),
        IF(@col_exists_tipo = 0, 'tipo_imovel ', ''),
        IF(@col_exists_local > 0 AND @col_exists_finalidade > 0 AND @col_exists_tipo > 0, 
           'Todas as colunas já existiam', '')
    ) AS detalhes;

