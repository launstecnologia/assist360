-- =====================================================
-- ADICIONAR COLUNA parent_id NA TABELA categorias
-- =====================================================
-- Este script adiciona suporte a hierarquia de categorias
-- =====================================================

-- Verificar se a coluna já existe
SET @col_exists = (
    SELECT COUNT(*) 
    FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'categorias'
    AND COLUMN_NAME = 'parent_id'
);

-- Adicionar coluna se não existir
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE `categorias` ADD COLUMN `parent_id` INT(11) NULL DEFAULT NULL COMMENT ''ID da categoria pai (para hierarquia)'' AFTER `id`',
    'SELECT ''Coluna parent_id já existe'' as mensagem'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar índice se não existir
SET @idx_exists = (
    SELECT COUNT(*) 
    FROM information_schema.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'categorias'
    AND INDEX_NAME = 'idx_parent_id'
);

SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE `categorias` ADD KEY `idx_parent_id` (`parent_id`)',
    'SELECT ''Índice idx_parent_id já existe'' as mensagem'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adicionar constraint de foreign key se não existir
SET @constraint_exists = (
    SELECT COUNT(*) 
    FROM information_schema.TABLE_CONSTRAINTS 
    WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'categorias'
    AND CONSTRAINT_NAME = 'fk_categorias_parent'
);

SET @sql = IF(@constraint_exists = 0,
    'ALTER TABLE `categorias` ADD CONSTRAINT `fk_categorias_parent` FOREIGN KEY (`parent_id`) REFERENCES `categorias` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE',
    'SELECT ''Constraint fk_categorias_parent já existe'' as mensagem'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar se foi criado com sucesso
SELECT 
    COLUMN_NAME, 
    DATA_TYPE, 
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'categorias'
AND COLUMN_NAME = 'parent_id';




