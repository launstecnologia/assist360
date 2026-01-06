-- =====================================================
-- ADICIONAR CAMPOS tipo_imovel E local_manutencao NA TABELA solicitacoes
-- =====================================================
-- Este script adiciona as colunas se elas não existirem
-- =====================================================

-- Adicionar coluna local_manutencao (se não existir)
SET @col_exists_local = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'solicitacoes'
    AND COLUMN_NAME = 'local_manutencao'
);

SET @sql_local = IF(@col_exists_local = 0,
    'ALTER TABLE `solicitacoes` ADD COLUMN `local_manutencao` VARCHAR(255) NULL DEFAULT NULL COMMENT ''Local onde será realizada a manutenção''',
    'SELECT "Coluna local_manutencao já existe" AS mensagem'
);

PREPARE stmt_local FROM @sql_local;
EXECUTE stmt_local;
DEALLOCATE PREPARE stmt_local;

-- Adicionar coluna tipo_imovel (se não existir)
SET @col_exists_tipo = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'solicitacoes'
    AND COLUMN_NAME = 'tipo_imovel'
);

SET @sql_tipo = IF(@col_exists_tipo = 0,
    'ALTER TABLE `solicitacoes` ADD COLUMN `tipo_imovel` VARCHAR(50) NULL DEFAULT NULL COMMENT ''Tipo do imóvel (RESIDENCIAL, COMERCIAL, CASA, APARTAMENTO, etc)''',
    'SELECT "Coluna tipo_imovel já existe" AS mensagem'
);

PREPARE stmt_tipo FROM @sql_tipo;
EXECUTE stmt_tipo;
DEALLOCATE PREPARE stmt_tipo;

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
AND COLUMN_NAME IN ('local_manutencao', 'tipo_imovel')
ORDER BY ORDINAL_POSITION;

