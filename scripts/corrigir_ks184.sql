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

