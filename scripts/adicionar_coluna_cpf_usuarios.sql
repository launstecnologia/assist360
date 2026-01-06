-- Adicionar coluna CPF na tabela usuarios
-- Execute este script apenas se a coluna CPF não existir

-- Verificar se a coluna já existe antes de adicionar
SET @dbname = DATABASE();
SET @tablename = 'usuarios';
SET @columnname = 'cpf';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  "SELECT 'Coluna CPF já existe na tabela usuarios' AS resultado;",
  CONCAT("ALTER TABLE ", @tablename, " ADD COLUMN ", @columnname, " VARCHAR(14) NULL AFTER telefone;")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Adicionar índice único para CPF (opcional, mas recomendado)
-- Descomente as linhas abaixo se quiser garantir unicidade do CPF
-- ALTER TABLE usuarios ADD UNIQUE INDEX unique_cpf (cpf);

