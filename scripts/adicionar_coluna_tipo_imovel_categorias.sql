-- =====================================================
-- ADICIONAR COLUNA tipo_imovel NA TABELA categorias
-- =====================================================
-- Este script adiciona a coluna tipo_imovel para indicar
-- se a categoria é para imóveis residenciais, comerciais ou ambos
-- =====================================================

-- Verificar se a coluna já existe antes de adicionar
SET @dbname = DATABASE();
SET @tablename = 'categorias';
SET @columnname = 'tipo_imovel';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  "SELECT 'Coluna tipo_imovel já existe na tabela categorias' AS resultado;",
  CONCAT("ALTER TABLE ", @tablename, " ADD COLUMN ", @columnname, " ENUM('RESIDENCIAL', 'COMERCIAL', 'AMBOS') NOT NULL DEFAULT 'AMBOS' COMMENT 'Tipo de imóvel: Residencial, Comercial ou Ambos' AFTER ordem;")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Adicionar índice para melhor performance (se não existir)
SET @indexname = 'idx_tipo_imovel';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (INDEX_NAME = @indexname)
  ) > 0,
  "SELECT 'Índice idx_tipo_imovel já existe na tabela categorias' AS resultado;",
  CONCAT("ALTER TABLE ", @tablename, " ADD INDEX ", @indexname, " (", @columnname, ");")
));
PREPARE indexIfNotExists FROM @preparedStatement;
EXECUTE indexIfNotExists;
DEALLOCATE PREPARE indexIfNotExists;

-- Atualizar categorias existentes para 'AMBOS' (caso não tenham valor)
UPDATE `categorias` 
SET `tipo_imovel` = 'AMBOS' 
WHERE `tipo_imovel` IS NULL OR `tipo_imovel` = '';

-- Verificar resultado
SELECT 
    id,
    nome,
    tipo_imovel,
    status
FROM `categorias`
ORDER BY nome;

