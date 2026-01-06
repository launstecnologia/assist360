-- Corrigir solicitação 207 para usar o status "Não Qualificados"
-- Primeiro, encontrar o ID do status "Não Qualificados"
UPDATE solicitacoes 
SET status_id = (
    SELECT id FROM status 
    WHERE nome IN ('Não qualificado', 'Não Qualificados') 
    AND status = 'ATIVO' 
    LIMIT 1
)
WHERE id = 207 
AND tipo_qualificacao = 'NAO_QUALIFICADA';

-- Verificar se foi atualizado
SELECT 
    s.id,
    s.status_id,
    st.nome as status_nome,
    s.tipo_qualificacao
FROM solicitacoes s
LEFT JOIN status st ON s.status_id = st.id
WHERE s.id = 207;

