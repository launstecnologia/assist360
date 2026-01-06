-- Corrigir tipo_qualificacao da solicitação KS184
-- Como validacao_bolsao = 1, deve ser BOLSAO

UPDATE solicitacoes 
SET tipo_qualificacao = 'BOLSAO' 
WHERE id = 184 
AND validacao_bolsao = 1;

-- Verificar se foi atualizado
SELECT id, locatario_nome, validacao_bolsao, tipo_qualificacao, status_id, created_at
FROM solicitacoes 
WHERE id = 184;

-- Verificar se aparece no Kanban (deve mostrar se tipo_qualificacao IS NULL, 'CORTESIA' ou 'BOLSAO')
SELECT 
    s.id,
    s.locatario_nome,
    s.tipo_qualificacao,
    s.validacao_bolsao,
    s.status_id,
    st.nome as status_nome
FROM solicitacoes s
LEFT JOIN status st ON s.status_id = st.id
WHERE s.id = 184
AND (s.tipo_qualificacao IS NULL OR s.tipo_qualificacao = 'CORTESIA' OR s.tipo_qualificacao = 'BOLSAO');

