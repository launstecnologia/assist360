-- Adicionar colunas numero_contrato e local_manutencao na tabela solicitacoes_manuais
-- Data: 2025-11-08
-- Este script adiciona as colunas necessárias para o formulário de nova solicitação manual

START TRANSACTION;

-- Verificar e adicionar coluna numero_contrato
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'solicitacoes_manuais'
    AND COLUMN_NAME = 'numero_contrato'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE solicitacoes_manuais ADD COLUMN numero_contrato VARCHAR(50) NULL AFTER estado',
    'SELECT "Coluna numero_contrato já existe" AS mensagem'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar e adicionar coluna local_manutencao
SET @col_exists_local = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'solicitacoes_manuais'
    AND COLUMN_NAME = 'local_manutencao'
);

SET @sql_local = IF(@col_exists_local = 0,
    'ALTER TABLE solicitacoes_manuais ADD COLUMN local_manutencao VARCHAR(255) NULL AFTER descricao_problema',
    'SELECT "Coluna local_manutencao já existe" AS mensagem'
);

PREPARE stmt_local FROM @sql_local;
EXECUTE stmt_local;
DEALLOCATE PREPARE stmt_local;

COMMIT;

-- Verificar resultado
SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'solicitacoes_manuais'
AND COLUMN_NAME IN ('numero_contrato', 'local_manutencao')
ORDER BY COLUMN_NAME;
