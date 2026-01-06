-- =====================================================
-- INSERIR/ATUALIZAR CATEGORIAS PRINCIPAIS COM SUBCATEGORIAS
-- =====================================================
-- Este script cria ou atualiza as categorias principais
-- com suas descrições (limites) e subcategorias específicas
-- =====================================================

-- =====================================================
-- 1. CHAVEIRO
-- =====================================================
SET @categoria_chaveiro_id = NULL;

-- Criar ou obter categoria Chaveiro
INSERT INTO categorias (
    nome, 
    descricao, 
    icone, 
    cor, 
    status, 
    ordem, 
    tipo_imovel, 
    parent_id,
    limite_solicitacoes_12_meses,
    created_at, 
    updated_at
)
SELECT 
    'Chaveiro',
    'Limite: até R$ 250,00 por evento, limitado a 2 intervenções por 12 meses.',
    'fas fa-key',
    '#F59E0B',
    'ATIVA',
    2,
    'AMBOS',
    NULL,
    2,
    NOW(),
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM categorias WHERE nome = 'Chaveiro' AND (parent_id IS NULL OR parent_id = 0)
);

-- Atualizar se já existir
UPDATE categorias 
SET descricao = 'Limite: até R$ 250,00 por evento, limitado a 2 intervenções por 12 meses.',
    limite_solicitacoes_12_meses = 2,
    icone = COALESCE(icone, 'fas fa-key'),
    cor = COALESCE(cor, '#F59E0B'),
    updated_at = NOW()
WHERE nome = 'Chaveiro' 
AND (parent_id IS NULL OR parent_id = 0);

-- Obter ID da categoria Chaveiro
SELECT id INTO @categoria_chaveiro_id 
FROM categorias 
WHERE nome = 'Chaveiro' AND (parent_id IS NULL OR parent_id = 0)
LIMIT 1;

-- Inserir subcategorias de Chaveiro
INSERT INTO subcategorias (
    categoria_id,
    nome,
    descricao,
    status,
    ordem,
    is_emergencial,
    prazo_minimo,
    created_at,
    updated_at
)
SELECT 
    @categoria_chaveiro_id,
    nome_temp,
    descricao_temp,
    'ATIVA',
    ordem_temp,
    0,
    1,
    NOW(),
    NOW()
FROM (
    SELECT 'Perda de chave' as nome_temp, 'Serviço de abertura de porta devido à perda de chave' as descricao_temp, 1 as ordem_temp
    UNION ALL SELECT 'Quebra de chave', 'Serviço de abertura de porta devido à quebra de chave', 2
    UNION ALL SELECT 'Emperramento de chaves', 'Serviço de abertura de porta devido ao emperramento de chaves', 3
    UNION ALL SELECT 'Arrombamento em portas externas', 'Reparo e substituição de fechaduras após arrombamento em portas externas', 4
) AS temp
WHERE NOT EXISTS (
    SELECT 1 FROM subcategorias 
    WHERE categoria_id = @categoria_chaveiro_id 
    AND nome = temp.nome_temp
);

-- =====================================================
-- 2. HIDRÁULICA (ENCANADOR)
-- =====================================================
SET @categoria_hidraulica_id = NULL;

-- Criar ou obter categoria Hidráulica
INSERT INTO categorias (
    nome, 
    descricao, 
    icone, 
    cor, 
    status, 
    ordem, 
    tipo_imovel, 
    parent_id,
    limite_solicitacoes_12_meses,
    created_at, 
    updated_at
)
SELECT 
    'Hidráulica',
    'Limite: até R$ 150,00 por evento, limitado a 2 intervenções durante 12 meses.',
    'fas fa-faucet',
    '#3B82F6',
    'ATIVA',
    3,
    'AMBOS',
    NULL,
    2,
    NOW(),
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM categorias WHERE nome = 'Hidráulica' AND (parent_id IS NULL OR parent_id = 0)
);

-- Atualizar se já existir
UPDATE categorias 
SET descricao = 'Limite: até R$ 150,00 por evento, limitado a 2 intervenções durante 12 meses.',
    limite_solicitacoes_12_meses = 2,
    icone = COALESCE(icone, 'fas fa-faucet'),
    cor = COALESCE(cor, '#3B82F6'),
    updated_at = NOW()
WHERE nome = 'Hidráulica' 
AND (parent_id IS NULL OR parent_id = 0);

-- Obter ID da categoria Hidráulica
SELECT id INTO @categoria_hidraulica_id 
FROM categorias 
WHERE nome = 'Hidráulica' AND (parent_id IS NULL OR parent_id = 0)
LIMIT 1;

-- Inserir subcategorias de Hidráulica
INSERT INTO subcategorias (
    categoria_id,
    nome,
    descricao,
    status,
    ordem,
    is_emergencial,
    prazo_minimo,
    created_at,
    updated_at
)
SELECT 
    @categoria_hidraulica_id,
    nome_temp,
    descricao_temp,
    'ATIVA',
    ordem_temp,
    0,
    1,
    NOW(),
    NOW()
FROM (
    SELECT 'Vazamento em tubulações aparentes' as nome_temp, 'Reparo de vazamentos em tubulações visíveis' as descricao_temp, 1 as ordem_temp
    UNION ALL SELECT 'Vazamento em torneiras', 'Reparo de vazamentos em torneiras', 2
    UNION ALL SELECT 'Vazamento em sifões', 'Reparo de vazamentos em sifões', 3
    UNION ALL SELECT 'Vazamento em chuveiros', 'Reparo de vazamentos em chuveiros', 4
    UNION ALL SELECT 'Vazamento em válvulas de descarga', 'Reparo de vazamentos em válvulas de descarga', 5
    UNION ALL SELECT 'Vazamento em registros', 'Reparo de vazamentos em registros', 6
) AS temp
WHERE NOT EXISTS (
    SELECT 1 FROM subcategorias 
    WHERE categoria_id = @categoria_hidraulica_id 
    AND nome = temp.nome_temp
);

-- =====================================================
-- 3. DESENTUPIMENTO
-- =====================================================
SET @categoria_desentupimento_id = NULL;

-- Criar ou obter categoria Desentupimento
INSERT INTO categorias (
    nome, 
    descricao, 
    icone, 
    cor, 
    status, 
    ordem, 
    tipo_imovel, 
    parent_id,
    limite_solicitacoes_12_meses,
    created_at, 
    updated_at
)
SELECT 
    'Desentupimento',
    'Limite: até R$ 200,00 por evento, limitado a 2 intervenções durante 12 meses.',
    'fas fa-water',
    '#06B6D4',
    'ATIVA',
    4,
    'AMBOS',
    NULL,
    2,
    NOW(),
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM categorias WHERE nome = 'Desentupimento' AND (parent_id IS NULL OR parent_id = 0)
);

-- Atualizar se já existir
UPDATE categorias 
SET descricao = 'Limite: até R$ 200,00 por evento, limitado a 2 intervenções durante 12 meses.',
    limite_solicitacoes_12_meses = 2,
    icone = COALESCE(icone, 'fas fa-water'),
    cor = COALESCE(cor, '#06B6D4'),
    updated_at = NOW()
WHERE nome = 'Desentupimento' 
AND (parent_id IS NULL OR parent_id = 0);

-- Obter ID da categoria Desentupimento
SELECT id INTO @categoria_desentupimento_id 
FROM categorias 
WHERE nome = 'Desentupimento' AND (parent_id IS NULL OR parent_id = 0)
LIMIT 1;

-- Inserir subcategorias de Desentupimento
INSERT INTO subcategorias (
    categoria_id,
    nome,
    descricao,
    status,
    ordem,
    is_emergencial,
    prazo_minimo,
    created_at,
    updated_at
)
SELECT 
    @categoria_desentupimento_id,
    nome_temp,
    descricao_temp,
    'ATIVA',
    ordem_temp,
    0,
    1,
    NOW(),
    NOW()
FROM (
    SELECT 'Desentupimento de pias' as nome_temp, 'Desentupimento de pias' as descricao_temp, 1 as ordem_temp
    UNION ALL SELECT 'Desentupimento de sifões', 'Desentupimento de sifões', 2
    UNION ALL SELECT 'Desentupimento de ralos', 'Desentupimento de ralos', 3
    UNION ALL SELECT 'Desentupimento de vasos sanitários', 'Desentupimento de vasos sanitários', 4
    UNION ALL SELECT 'Desentupimento de tubulações de esgoto', 'Desentupimento de tubulações de esgoto', 5
    UNION ALL SELECT 'Desentupimento de caixas de gordura', 'Desentupimento de caixas de gordura', 6
) AS temp
WHERE NOT EXISTS (
    SELECT 1 FROM subcategorias 
    WHERE categoria_id = @categoria_desentupimento_id 
    AND nome = temp.nome_temp
);

-- =====================================================
-- 4. ELÉTRICA (ELETRICISTA)
-- =====================================================
SET @categoria_eletrica_id = NULL;

-- Criar ou obter categoria Elétrica
INSERT INTO categorias (
    nome, 
    descricao, 
    icone, 
    cor, 
    status, 
    ordem, 
    tipo_imovel, 
    parent_id,
    limite_solicitacoes_12_meses,
    created_at, 
    updated_at
)
SELECT 
    'Elétrica',
    'Limite: até R$ 150,00 por evento, limitado a 2 intervenções por 12 meses.',
    'fas fa-bolt',
    '#FFD700',
    'ATIVA',
    5,
    'AMBOS',
    NULL,
    2,
    NOW(),
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM categorias WHERE nome = 'Elétrica' AND (parent_id IS NULL OR parent_id = 0)
);

-- Atualizar se já existir
UPDATE categorias 
SET descricao = 'Limite: até R$ 150,00 por evento, limitado a 2 intervenções por 12 meses.',
    limite_solicitacoes_12_meses = 2,
    icone = COALESCE(icone, 'fas fa-bolt'),
    cor = COALESCE(cor, '#FFD700'),
    updated_at = NOW()
WHERE nome = 'Elétrica' 
AND (parent_id IS NULL OR parent_id = 0);

-- Obter ID da categoria Elétrica
SELECT id INTO @categoria_eletrica_id 
FROM categorias 
WHERE nome = 'Elétrica' AND (parent_id IS NULL OR parent_id = 0)
LIMIT 1;

-- Inserir subcategorias de Elétrica
INSERT INTO subcategorias (
    categoria_id,
    nome,
    descricao,
    status,
    ordem,
    is_emergencial,
    prazo_minimo,
    created_at,
    updated_at
)
SELECT 
    @categoria_eletrica_id,
    nome_temp,
    descricao_temp,
    'ATIVA',
    ordem_temp,
    0,
    1,
    NOW(),
    NOW()
FROM (
    SELECT 'Reparo em tomadas' as nome_temp, 'Reparo e substituição de tomadas elétricas' as descricao_temp, 1 as ordem_temp
    UNION ALL SELECT 'Reparo em chuveiros elétricos', 'Reparo e manutenção de chuveiros elétricos', 2
    UNION ALL SELECT 'Reparo em disjuntores', 'Reparo e substituição de disjuntores', 3
    UNION ALL SELECT 'Reparo em chaves faca', 'Reparo e substituição de chaves faca', 4
    UNION ALL SELECT 'Reparo em torneiras elétricas', 'Reparo e manutenção de torneiras elétricas', 5
    UNION ALL SELECT 'Falta de energia', 'Diagnóstico e reparo de falta de energia', 6
) AS temp
WHERE NOT EXISTS (
    SELECT 1 FROM subcategorias 
    WHERE categoria_id = @categoria_eletrica_id 
    AND nome = temp.nome_temp
);

-- =====================================================
-- 5. VIDRACEIRO
-- =====================================================
SET @categoria_vidraceiro_id = NULL;

-- Criar ou obter categoria Vidraceiro
INSERT INTO categorias (
    nome, 
    descricao, 
    icone, 
    cor, 
    status, 
    ordem, 
    tipo_imovel, 
    parent_id,
    limite_solicitacoes_12_meses,
    created_at, 
    updated_at
)
SELECT 
    'Vidraceiro',
    'Limite: até R$ 150,00 por evento, limitado a 2 intervenções durante 12 meses.',
    'fas fa-window-maximize',
    '#8B5CF6',
    'ATIVA',
    6,
    'AMBOS',
    NULL,
    2,
    NOW(),
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM categorias WHERE nome = 'Vidraceiro' AND (parent_id IS NULL OR parent_id = 0)
);

-- Atualizar se já existir
UPDATE categorias 
SET descricao = 'Limite: até R$ 150,00 por evento, limitado a 2 intervenções durante 12 meses.',
    limite_solicitacoes_12_meses = 2,
    icone = COALESCE(icone, 'fas fa-window-maximize'),
    cor = COALESCE(cor, '#8B5CF6'),
    updated_at = NOW()
WHERE nome = 'Vidraceiro' 
AND (parent_id IS NULL OR parent_id = 0);

-- Obter ID da categoria Vidraceiro
SELECT id INTO @categoria_vidraceiro_id 
FROM categorias 
WHERE nome = 'Vidraceiro' AND (parent_id IS NULL OR parent_id = 0)
LIMIT 1;

-- Inserir subcategorias de Vidraceiro
INSERT INTO subcategorias (
    categoria_id,
    nome,
    descricao,
    status,
    ordem,
    is_emergencial,
    prazo_minimo,
    created_at,
    updated_at
)
SELECT 
    @categoria_vidraceiro_id,
    nome_temp,
    descricao_temp,
    'ATIVA',
    ordem_temp,
    0,
    1,
    NOW(),
    NOW()
FROM (
    SELECT 'Quebra de vidros em portas' as nome_temp, 'Substituição de vidros quebrados em portas na área externa' as descricao_temp, 1 as ordem_temp
    UNION ALL SELECT 'Quebra de vidros em janelas', 'Substituição de vidros quebrados em janelas na área externa', 2
    UNION ALL SELECT 'Quebra de cristais em portas', 'Substituição de cristais quebrados em portas na área externa', 3
    UNION ALL SELECT 'Quebra de cristais em janelas', 'Substituição de cristais quebrados em janelas na área externa', 4
) AS temp
WHERE NOT EXISTS (
    SELECT 1 FROM subcategorias 
    WHERE categoria_id = @categoria_vidraceiro_id 
    AND nome = temp.nome_temp
);

-- =====================================================
-- 6. TELHADOS E COBERTURAS
-- =====================================================
SET @categoria_telhados_id = NULL;

-- Criar ou obter categoria Telhados e Coberturas
INSERT INTO categorias (
    nome, 
    descricao, 
    icone, 
    cor, 
    status, 
    ordem, 
    tipo_imovel, 
    parent_id,
    limite_solicitacoes_12_meses,
    created_at, 
    updated_at
)
SELECT 
    'Telhados e Coberturas',
    'Limite: até R$ 250,00 por evento, limitado a 2 intervenções durante 12 meses.',
    'fas fa-home',
    '#EF4444',
    'ATIVA',
    7,
    'AMBOS',
    NULL,
    2,
    NOW(),
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM categorias WHERE nome = 'Telhados e Coberturas' AND (parent_id IS NULL OR parent_id = 0)
);

-- Atualizar se já existir
UPDATE categorias 
SET descricao = 'Limite: até R$ 250,00 por evento, limitado a 2 intervenções durante 12 meses.',
    limite_solicitacoes_12_meses = 2,
    icone = COALESCE(icone, 'fas fa-home'),
    cor = COALESCE(cor, '#EF4444'),
    updated_at = NOW()
WHERE nome = 'Telhados e Coberturas' 
AND (parent_id IS NULL OR parent_id = 0);

-- Obter ID da categoria Telhados e Coberturas
SELECT id INTO @categoria_telhados_id 
FROM categorias 
WHERE nome = 'Telhados e Coberturas' AND (parent_id IS NULL OR parent_id = 0)
LIMIT 1;

-- Inserir subcategorias de Telhados e Coberturas
INSERT INTO subcategorias (
    categoria_id,
    nome,
    descricao,
    status,
    ordem,
    is_emergencial,
    prazo_minimo,
    created_at,
    updated_at
)
SELECT 
    @categoria_telhados_id,
    nome_temp,
    descricao_temp,
    'ATIVA',
    ordem_temp,
    0,
    1,
    NOW(),
    NOW()
FROM (
    SELECT 'Instalação de lona provisória' as nome_temp, 'Instalação de lona provisória em caso de danos no telhado' as descricao_temp, 1 as ordem_temp
    UNION ALL SELECT 'Substituição de telhas cerâmicas', 'Substituição de telhas cerâmicas (limitado a 20 telhas)', 2
) AS temp
WHERE NOT EXISTS (
    SELECT 1 FROM subcategorias 
    WHERE categoria_id = @categoria_telhados_id 
    AND nome = temp.nome_temp
);

-- =====================================================
-- VERIFICAÇÃO FINAL
-- =====================================================
SELECT 
    'Categorias Principais' as tipo,
    c.id,
    c.nome,
    c.descricao,
    c.limite_solicitacoes_12_meses,
    COUNT(sc.id) as total_subcategorias
FROM categorias c
LEFT JOIN subcategorias sc ON c.id = sc.categoria_id AND sc.status = 'ATIVA'
WHERE (c.parent_id IS NULL OR c.parent_id = 0)
AND c.status = 'ATIVA'
AND c.nome IN ('Chaveiro', 'Hidráulica', 'Desentupimento', 'Elétrica', 'Vidraceiro', 'Telhados e Coberturas')
GROUP BY c.id, c.nome, c.descricao, c.limite_solicitacoes_12_meses
ORDER BY c.ordem ASC, c.nome ASC;
