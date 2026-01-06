-- Script para adicionar campo integracao_ativa na tabela imobiliarias
-- Execute este script no banco de dados para adicionar o campo

-- Verificar se a coluna já existe antes de adicionar
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'imobiliarias' 
    AND COLUMN_NAME = 'integracao_ativa'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE imobiliarias ADD COLUMN integracao_ativa TINYINT(1) NOT NULL DEFAULT 1 COMMENT "Indica se a integração com a API está ativa. Quando desativada, redireciona para solicitação manual." AFTER status',
    'SELECT "Coluna integracao_ativa já existe" AS mensagem'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Atualizar todas as imobiliárias existentes para ter integração ativa por padrão
UPDATE imobiliarias SET integracao_ativa = 1 WHERE integracao_ativa IS NULL OR integracao_ativa = 0;

