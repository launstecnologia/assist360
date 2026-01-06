-- Script para corrigir CPF em solicitações migradas de solicitações manuais
-- Execute este script no phpMyAdmin ou outro cliente MySQL

-- 1. Verificar se a coluna locatario_cpf existe
-- Se não existir, execute este comando primeiro:
-- ALTER TABLE solicitacoes ADD COLUMN locatario_cpf VARCHAR(20) NULL AFTER locatario_nome;

-- 2. Verificar quantas solicitações migradas estão sem CPF
SELECT 
    COUNT(*) as total_sem_cpf,
    'Solicitações migradas sem CPF' as descricao
FROM solicitacoes s
INNER JOIN solicitacoes_manuais sm ON sm.migrada_para_solicitacao_id = s.id
WHERE (s.locatario_cpf IS NULL OR s.locatario_cpf = '');

-- 3. Ver quais solicitações estão sem CPF (limitado a 20)
SELECT 
    s.id,
    s.locatario_nome,
    s.locatario_cpf as cpf_atual,
    sm.cpf as cpf_manual,
    sm.id as solicitacao_manual_id
FROM solicitacoes s
INNER JOIN solicitacoes_manuais sm ON sm.migrada_para_solicitacao_id = s.id
WHERE (s.locatario_cpf IS NULL OR s.locatario_cpf = '')
LIMIT 20;

-- 4. CORRIGIR: Atualizar o CPF nas solicitações migradas
-- EXECUTE ESTE COMANDO PARA CORRIGIR:
UPDATE solicitacoes s
INNER JOIN solicitacoes_manuais sm ON sm.migrada_para_solicitacao_id = s.id
SET s.locatario_cpf = sm.cpf
WHERE (s.locatario_cpf IS NULL OR s.locatario_cpf = '')
AND sm.cpf IS NOT NULL AND sm.cpf != '';

-- 5. Verificar resultado após a correção
SELECT 
    COUNT(*) as total_corrigido,
    'Verificação pós-correção' as descricao
FROM solicitacoes s
INNER JOIN solicitacoes_manuais sm ON sm.migrada_para_solicitacao_id = s.id
WHERE s.locatario_cpf IS NOT NULL AND s.locatario_cpf != '';

