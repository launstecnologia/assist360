-- Script SQL para corrigir a solicitação 249
-- Move de "Não Qualificado" para "Solicitação Manual"

-- 1. Buscar dados da solicitação 249
SELECT 
    id,
    locatario_cpf,
    locatario_nome,
    locatario_telefone,
    imovel_endereco,
    imovel_numero,
    imovel_complemento,
    imovel_bairro,
    imovel_cidade,
    imovel_estado,
    imovel_cep,
    categoria_id,
    subcategoria_id,
    imobiliaria_id,
    numero_contrato,
    descricao_problema,
    observacoes,
    validacao_bolsao,
    tipo_qualificacao,
    observacao_qualificacao,
    created_at
FROM solicitacoes 
WHERE id = 249;

-- 2. Verificar se já existe solicitação manual para este CPF/imobiliária/categoria na mesma data
SELECT id 
FROM solicitacoes_manuais 
WHERE cpf = (SELECT locatario_cpf FROM solicitacoes WHERE id = 249)
AND imobiliaria_id = (SELECT imobiliaria_id FROM solicitacoes WHERE id = 249)
AND categoria_id = (SELECT categoria_id FROM solicitacoes WHERE id = 249)
AND DATE(created_at) = (SELECT DATE(created_at) FROM solicitacoes WHERE id = 249);

-- 3. Criar solicitação manual com os dados da solicitação 249
-- (Ajuste os valores conforme necessário baseado nos dados da query 1)
INSERT INTO solicitacoes_manuais (
    imobiliaria_id,
    nome_completo,
    cpf,
    whatsapp,
    tipo_imovel,
    cep,
    endereco,
    numero,
    complemento,
    bairro,
    cidade,
    estado,
    categoria_id,
    subcategoria_id,
    numero_contrato,
    local_manutencao,
    descricao_problema,
    observacoes,
    validacao_bolsao,
    tipo_qualificacao,
    observacao_qualificacao,
    termos_aceitos,
    created_at,
    updated_at
)
SELECT 
    imobiliaria_id,
    locatario_nome,
    locatario_cpf,
    locatario_telefone,
    'RESIDENCIAL' as tipo_imovel, -- Ajuste se necessário
    imovel_cep,
    imovel_endereco,
    imovel_numero,
    imovel_complemento,
    imovel_bairro,
    imovel_cidade,
    imovel_estado,
    categoria_id,
    subcategoria_id,
    numero_contrato,
    SUBSTRING_INDEX(observacoes, '\n', 1) as local_manutencao, -- Primeira linha das observações
    descricao_problema,
    observacoes,
    COALESCE(validacao_bolsao, 0),
    NULL as tipo_qualificacao, -- Resetar para admin decidir
    NULL as observacao_qualificacao, -- Limpar observação
    1 as termos_aceitos,
    created_at,
    NOW() as updated_at
FROM solicitacoes
WHERE id = 249
AND NOT EXISTS (
    SELECT 1 FROM solicitacoes_manuais 
    WHERE cpf = solicitacoes.locatario_cpf
    AND imobiliaria_id = solicitacoes.imobiliaria_id
    AND categoria_id = solicitacoes.categoria_id
    AND DATE(created_at) = DATE(solicitacoes.created_at)
);

-- 4. Atualizar a solicitação 249: remover tipo_qualificacao e observacao_qualificacao
UPDATE solicitacoes 
SET 
    tipo_qualificacao = NULL,
    observacao_qualificacao = NULL
WHERE id = 249;

-- 5. Atualizar status da solicitação 249 para "Nova Solicitação"
UPDATE solicitacoes s
INNER JOIN status st ON st.nome IN ('Nova Solicitação', 'Nova', 'NOVA')
SET s.status_id = st.id
WHERE s.id = 249
AND st.status = 'ATIVO'
ORDER BY st.ordem ASC
LIMIT 1;

-- 6. Verificar resultado
SELECT 
    s.id,
    s.locatario_nome,
    s.tipo_qualificacao,
    s.observacao_qualificacao,
    st.nome as status_nome,
    (SELECT COUNT(*) FROM solicitacoes_manuais WHERE cpf = s.locatario_cpf AND imobiliaria_id = s.imobiliaria_id AND categoria_id = s.categoria_id) as solicitacoes_manuais_count
FROM solicitacoes s
LEFT JOIN status st ON s.status_id = st.id
WHERE s.id = 249;

