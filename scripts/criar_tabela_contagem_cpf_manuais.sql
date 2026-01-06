-- ============================================
-- Script: Criar tabela de contagem de CPF
-- ============================================
-- Este script cria apenas a tabela, sem os triggers
-- Os triggers devem ser criados separadamente via: criar_triggers_contagem_cpf.sql
-- ============================================

-- Criar tabela para rastrear quantidade de solicitações manuais por CPF e Categoria
CREATE TABLE IF NOT EXISTS solicitacoes_manuais_contagem_cpf (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cpf VARCHAR(11) NOT NULL,
    imobiliaria_id INT NOT NULL,
    categoria_id INT NULL,
    quantidade_total INT NOT NULL DEFAULT 0,
    quantidade_12_meses INT NOT NULL DEFAULT 0,
    primeira_solicitacao DATETIME NULL,
    ultima_solicitacao DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_cpf_imobiliaria_categoria (cpf, imobiliaria_id, categoria_id),
    KEY idx_cpf (cpf),
    KEY idx_imobiliaria_id (imobiliaria_id),
    KEY idx_categoria_id (categoria_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Atualizar contagem para solicitações manuais já existentes
-- Contagem geral (categoria_id = NULL)
INSERT INTO solicitacoes_manuais_contagem_cpf (cpf, imobiliaria_id, categoria_id, quantidade_total, quantidade_12_meses, primeira_solicitacao, ultima_solicitacao)
SELECT 
    REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') as cpf_limpo,
    imobiliaria_id,
    NULL as categoria_id,
    COUNT(*) as quantidade_total,
    SUM(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) THEN 1 ELSE 0 END) as quantidade_12_meses,
    MIN(created_at) as primeira_solicitacao,
    MAX(created_at) as ultima_solicitacao
FROM solicitacoes_manuais
WHERE cpf IS NOT NULL 
AND cpf != ''
AND LENGTH(REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '')) >= 11
GROUP BY cpf_limpo, imobiliaria_id
ON DUPLICATE KEY UPDATE
    quantidade_total = VALUES(quantidade_total),
    quantidade_12_meses = VALUES(quantidade_12_meses),
    primeira_solicitacao = VALUES(primeira_solicitacao),
    ultima_solicitacao = VALUES(ultima_solicitacao);

-- Contagem por categoria (se categoria_id existir)
INSERT INTO solicitacoes_manuais_contagem_cpf (cpf, imobiliaria_id, categoria_id, quantidade_total, quantidade_12_meses, primeira_solicitacao, ultima_solicitacao)
SELECT 
    REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') as cpf_limpo,
    imobiliaria_id,
    categoria_id,
    COUNT(*) as quantidade_total,
    SUM(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) THEN 1 ELSE 0 END) as quantidade_12_meses,
    MIN(created_at) as primeira_solicitacao,
    MAX(created_at) as ultima_solicitacao
FROM solicitacoes_manuais
WHERE cpf IS NOT NULL 
AND cpf != ''
AND categoria_id IS NOT NULL
AND LENGTH(REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '')) >= 11
GROUP BY cpf_limpo, imobiliaria_id, categoria_id
ON DUPLICATE KEY UPDATE
    quantidade_total = VALUES(quantidade_total),
    quantidade_12_meses = VALUES(quantidade_12_meses),
    primeira_solicitacao = VALUES(primeira_solicitacao),
    ultima_solicitacao = VALUES(ultima_solicitacao);
