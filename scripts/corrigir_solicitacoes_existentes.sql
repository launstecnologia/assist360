-- Corrigir solicitações existentes com validacao_bolsao = 1
-- Atualizar tipo_qualificacao para 'BOLSAO' onde aplicável

-- Solicitações normais com validacao_bolsao = 1 e tipo_qualificacao vazio/NULL
UPDATE solicitacoes 
SET tipo_qualificacao = 'BOLSAO' 
WHERE validacao_bolsao = 1 
AND (tipo_qualificacao IS NULL OR tipo_qualificacao = '');

-- Solicitações manuais com validacao_bolsao = 1 e tipo_qualificacao vazio/NULL
-- (Embora solicitações manuais normalmente não devam ter validacao_bolsao = 1, 
-- vamos corrigir caso existam dados inconsistentes)
UPDATE solicitacoes_manuais 
SET tipo_qualificacao = 'BOLSAO' 
WHERE validacao_bolsao = 1 
AND (tipo_qualificacao IS NULL OR tipo_qualificacao = '');

-- Verificar quantas foram corrigidas
SELECT 
    'solicitacoes' as tabela,
    COUNT(*) as total_corrigidas
FROM solicitacoes
WHERE validacao_bolsao = 1 AND tipo_qualificacao = 'BOLSAO'

UNION ALL

SELECT 
    'solicitacoes_manuais' as tabela,
    COUNT(*) as total_corrigidas
FROM solicitacoes_manuais
WHERE validacao_bolsao = 1 AND tipo_qualificacao = 'BOLSAO';

