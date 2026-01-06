-- Corrigir todas as solicitações com tipo_qualificacao = 'NAO_QUALIFICADA'
-- que não estão com o status correto "Não Qualificados"

-- Primeiro, encontrar o ID do status "Não Qualificados" ou "Não qualificado"
SET @status_nao_qualificado_id = (
    SELECT id FROM status 
    WHERE nome IN ('Não qualificado', 'Não Qualificados') 
    AND status = 'ATIVO' 
    LIMIT 1
);

-- Verificar quantas solicitações precisam ser corrigidas
SELECT 
    COUNT(*) as total_para_corrigir,
    GROUP_CONCAT(id) as ids
FROM solicitacoes
WHERE tipo_qualificacao = 'NAO_QUALIFICADA'
AND status_id != @status_nao_qualificado_id;

-- Atualizar todas as solicitações
UPDATE solicitacoes 
SET status_id = @status_nao_qualificado_id
WHERE tipo_qualificacao = 'NAO_QUALIFICADA'
AND status_id != @status_nao_qualificado_id;

-- Verificar resultado
SELECT 
    s.id,
    s.status_id,
    st.nome as status_nome,
    s.tipo_qualificacao,
    s.created_at
FROM solicitacoes s
LEFT JOIN status st ON s.status_id = st.id
WHERE s.tipo_qualificacao = 'NAO_QUALIFICADA'
ORDER BY s.id DESC
LIMIT 20;

