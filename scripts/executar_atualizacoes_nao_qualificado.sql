-- ============================================
-- SCRIPT CONSOLIDADO: Atualizações "Não Qualificado"
-- ============================================
-- Este script contém todas as atualizações necessárias para a funcionalidade
-- de "Não Qualificado" e validações relacionadas.
-- Execute este script no banco de dados.
-- ============================================

START TRANSACTION;

-- ============================================
-- 1. Criar status "Não qualificado"
-- ============================================
INSERT INTO status (nome, cor, icone, ordem, visivel_kanban, status, created_at, updated_at)
SELECT 
    'Não qualificado',
    '#6B7280', -- Cor cinza
    'fa-times-circle',
    COALESCE((SELECT MAX(ordem) FROM status s2), 0) + 1,
    1, -- Visível no Kanban
    'ATIVO',
    NOW(),
    NOW()
FROM (SELECT 1) AS dummy
WHERE NOT EXISTS (
    SELECT 1 FROM status WHERE nome = 'Não qualificado' AND status = 'ATIVO'
);

-- ============================================
-- 2. Criar template WhatsApp "Não Qualificado"
-- ============================================
INSERT INTO whatsapp_templates (nome, tipo, corpo, variaveis, ativo, padrao, created_at, updated_at)
SELECT 
    'Não Qualificado',
    'Não Qualificado',
    'Olá {{cliente_nome}},

Infelizmente, sua solicitação de assistência não se enquadra nos critérios estabelecidos.

{{observacao}}

Se tiver dúvidas, entre em contato conosco.

Atenciosamente,
Equipe Assistência 360°',
    '["cliente_nome", "observacao"]',
    1,
    1,
    NOW(),
    NOW()
FROM (SELECT 1) AS dummy
WHERE NOT EXISTS (
    SELECT 1 FROM whatsapp_templates WHERE tipo = 'Não Qualificado' AND ativo = 1
);

-- ============================================
-- 3. Adicionar coluna observacao_qualificacao
-- ============================================

-- Tabela solicitacoes
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'solicitacoes'
    AND COLUMN_NAME = 'observacao_qualificacao'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE solicitacoes ADD COLUMN observacao_qualificacao TEXT NULL DEFAULT NULL COMMENT ''Observação específica para qualificação (Cortesia ou Não Qualificado)'' AFTER tipo_qualificacao',
    'SELECT "Coluna observacao_qualificacao já existe em solicitacoes" AS mensagem'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Tabela solicitacoes_manuais
SET @col_exists_manual = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'solicitacoes_manuais'
    AND COLUMN_NAME = 'observacao_qualificacao'
);

SET @sql_manual = IF(@col_exists_manual = 0,
    'ALTER TABLE solicitacoes_manuais ADD COLUMN observacao_qualificacao TEXT NULL DEFAULT NULL COMMENT ''Observação específica para qualificação (Cortesia ou Não Qualificado)'' AFTER tipo_qualificacao',
    'SELECT "Coluna observacao_qualificacao já existe em solicitacoes_manuais" AS mensagem'
);

PREPARE stmt_manual FROM @sql_manual;
EXECUTE stmt_manual;
DEALLOCATE PREPARE stmt_manual;

-- ============================================
-- 4. Criar tabela de contagem de CPF (se não existir)
-- ============================================
CREATE TABLE IF NOT EXISTS solicitacoes_manuais_contagem_cpf (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cpf VARCHAR(11) NOT NULL,
    imobiliaria_id INT NOT NULL,
    categoria_id INT NULL, -- Opcional: para contagem por categoria
    quantidade_total INT NOT NULL DEFAULT 0,
    quantidade_12_meses INT NOT NULL DEFAULT 0,
    primeira_solicitacao DATETIME NULL,
    ultima_solicitacao DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_cpf_imobiliaria_categoria (cpf, imobiliaria_id, categoria_id),
    KEY idx_cpf (cpf),
    KEY idx_imobiliaria_id (imobiliaria_id),
    KEY idx_categoria_id (categoria_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. Criar triggers para atualização automática da contagem
-- ============================================
-- NOTA IMPORTANTE: Os triggers precisam ser criados em um script separado
-- devido à limitação do DELIMITER em execuções via PDO/MySQLi.
-- 
-- Execute o arquivo separado: scripts/criar_triggers_contagem_cpf.sql
-- Esse arquivo deve ser executado diretamente no cliente MySQL (phpMyAdmin, MySQL Workbench, etc.)
-- ============================================

-- ============================================
-- 6. Popular tabela de contagem com dados existentes (opcional)
-- ============================================
-- Descomente as linhas abaixo se quiser popular a tabela com dados existentes

/*
-- Inserir contagens gerais (categoria_id = NULL)
INSERT INTO solicitacoes_manuais_contagem_cpf (cpf, imobiliaria_id, categoria_id, quantidade_total, quantidade_12_meses, primeira_solicitacao, ultima_solicitacao)
SELECT
    REPLACE(REPLACE(REPLACE(sm.cpf, '.', ''), '-', ''), ' ', '') AS cpf_limpo,
    sm.imobiliaria_id,
    NULL AS categoria_id,
    COUNT(*) AS quantidade_total,
    SUM(CASE WHEN sm.created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) THEN 1 ELSE 0 END) AS quantidade_12_meses,
    MIN(sm.created_at) AS primeira_solicitacao,
    MAX(sm.created_at) AS ultima_solicitacao
FROM solicitacoes_manuais sm
GROUP BY cpf_limpo, sm.imobiliaria_id
ON DUPLICATE KEY UPDATE
    quantidade_total = VALUES(quantidade_total),
    quantidade_12_meses = VALUES(quantidade_12_meses),
    primeira_solicitacao = VALUES(primeira_solicitacao),
    ultima_solicitacao = VALUES(ultima_solicitacao);

-- Inserir contagens por categoria
INSERT INTO solicitacoes_manuais_contagem_cpf (cpf, imobiliaria_id, categoria_id, quantidade_total, quantidade_12_meses, primeira_solicitacao, ultima_solicitacao)
SELECT
    REPLACE(REPLACE(REPLACE(sm.cpf, '.', ''), '-', ''), ' ', '') AS cpf_limpo,
    sm.imobiliaria_id,
    sm.categoria_id,
    COUNT(*) AS quantidade_total,
    SUM(CASE WHEN sm.created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) THEN 1 ELSE 0 END) AS quantidade_12_meses,
    MIN(sm.created_at) AS primeira_solicitacao,
    MAX(sm.created_at) AS ultima_solicitacao
FROM solicitacoes_manuais sm
WHERE sm.categoria_id IS NOT NULL
GROUP BY cpf_limpo, sm.imobiliaria_id, sm.categoria_id
ON DUPLICATE KEY UPDATE
    quantidade_total = VALUES(quantidade_total),
    quantidade_12_meses = VALUES(quantidade_12_meses),
    primeira_solicitacao = VALUES(primeira_solicitacao),
    ultima_solicitacao = VALUES(ultima_solicitacao);
*/

COMMIT;

-- ============================================
-- Verificação final
-- ============================================
SELECT 'Script executado com sucesso!' AS resultado;

-- Verificar se o status foi criado
SELECT id, nome, cor, visivel_kanban FROM status WHERE nome = 'Não qualificado';

-- Verificar se o template foi criado
SELECT id, nome, tipo, ativo FROM whatsapp_templates WHERE tipo = 'Não Qualificado';

-- Verificar se as colunas foram criadas
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME IN ('solicitacoes', 'solicitacoes_manuais')
AND COLUMN_NAME = 'observacao_qualificacao';

-- Verificar se a tabela de contagem foi criada
SELECT COUNT(*) AS total_registros FROM solicitacoes_manuais_contagem_cpf;

