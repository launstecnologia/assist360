-- Consultar quantidade de solicitações manuais por CPF
-- Exemplo de uso: substitua '12345678901' pelo CPF desejado e 1 pela imobiliária

-- Opção 1: Usando a tabela de contagem (mais rápido) - Por categoria
SELECT 
    cpf,
    imobiliaria_id,
    categoria_id,
    c.nome as categoria_nome,
    quantidade_total,
    quantidade_12_meses,
    primeira_solicitacao,
    ultima_solicitacao,
    updated_at
FROM solicitacoes_manuais_contagem_cpf cmc
LEFT JOIN categorias c ON cmc.categoria_id = c.id
WHERE cpf = '12345678901'  -- Substitua pelo CPF (sem formatação)
AND imobiliaria_id = 1     -- Substitua pelo ID da imobiliária
AND categoria_id = 1;      -- Substitua pelo ID da categoria (opcional - remova para ver todas)

-- Opção 1b: Total de todas as categorias
SELECT 
    cpf,
    imobiliaria_id,
    SUM(quantidade_total) as quantidade_total_geral,
    SUM(quantidade_12_meses) as quantidade_12_meses_geral,
    COUNT(DISTINCT categoria_id) as categorias_diferentes
FROM solicitacoes_manuais_contagem_cpf
WHERE cpf = '12345678901'
AND imobiliaria_id = 1
GROUP BY cpf, imobiliaria_id;

-- Opção 2: Contar diretamente da tabela de solicitações manuais - Por categoria
SELECT 
    REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') as cpf_limpo,
    imobiliaria_id,
    categoria_id,
    c.nome as categoria_nome,
    COUNT(*) as quantidade_total,
    SUM(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) THEN 1 ELSE 0 END) as quantidade_12_meses,
    MIN(DATE(created_at)) as primeira_solicitacao,
    MAX(DATE(created_at)) as ultima_solicitacao
FROM solicitacoes_manuais sm
LEFT JOIN categorias c ON sm.categoria_id = c.id
WHERE REPLACE(REPLACE(REPLACE(sm.cpf, '.', ''), '-', ''), ' ', '') = '12345678901'  -- Substitua pelo CPF
AND sm.imobiliaria_id = 1  -- Substitua pelo ID da imobiliária
AND sm.categoria_id = 1    -- Substitua pelo ID da categoria (opcional - remova para ver todas)
GROUP BY cpf_limpo, imobiliaria_id, categoria_id, c.nome;

-- Opção 3: Listar todas as solicitações de um CPF
SELECT 
    id,
    nome_completo,
    cpf,
    categoria_id,
    subcategoria_id,
    created_at,
    migrada_para_solicitacao_id,
    CASE WHEN migrada_para_solicitacao_id IS NOT NULL THEN 'Migrada' ELSE 'Pendente' END as status_migracao
FROM solicitacoes_manuais
WHERE REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = '12345678901'  -- Substitua pelo CPF
AND imobiliaria_id = 1  -- Substitua pelo ID da imobiliária
ORDER BY created_at DESC;

