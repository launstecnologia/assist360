-- =====================================================
-- INSERIR CATEGORIAS: MANUTENÇÃO E PREVENÇÃO
-- =====================================================
-- Este script cria a estrutura hierárquica:
-- - Categoria Pai: "Manutenção e Prevenção"
-- - Categorias Filhas: "Limpeza" e "Instalação"
-- - Subcategorias dentro de cada categoria filha
-- =====================================================

-- Verificar e criar categoria pai "Manutenção e Prevenção"
SET @categoria_pai_id = NULL;

SELECT id INTO @categoria_pai_id 
FROM categorias 
WHERE nome = 'Manutenção e Prevenção' 
LIMIT 1;

-- Se não existir, criar a categoria pai
INSERT INTO categorias (
    nome, 
    descricao, 
    icone, 
    cor, 
    status, 
    ordem, 
    tipo_imovel, 
    parent_id,
    created_at, 
    updated_at
)
SELECT 
    'Manutenção e Prevenção',
    'Categoria separadora para serviços de manutenção e prevenção',
    'fas fa-tools',
    '#10B981',
    'ATIVA',
    1,
    'AMBOS',
    NULL,
    NOW(),
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM categorias WHERE nome = 'Manutenção e Prevenção'
);

-- Obter o ID da categoria pai
SELECT id INTO @categoria_pai_id 
FROM categorias 
WHERE nome = 'Manutenção e Prevenção' 
LIMIT 1;

-- =====================================================
-- CATEGORIA FILHA: LIMPEZA
-- =====================================================
SET @categoria_limpeza_id = NULL;

SELECT id INTO @categoria_limpeza_id 
FROM categorias 
WHERE nome = 'Limpeza' AND parent_id = @categoria_pai_id
LIMIT 1;

INSERT INTO categorias (
    nome, 
    descricao, 
    icone, 
    cor, 
    status, 
    ordem, 
    tipo_imovel, 
    parent_id,
    created_at, 
    updated_at
)
SELECT 
    'Limpeza',
    'Serviços de limpeza e higienização',
    'fas fa-broom',
    '#3B82F6',
    'ATIVA',
    1,
    'AMBOS',
    @categoria_pai_id,
    NOW(),
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM categorias WHERE nome = 'Limpeza' AND parent_id = @categoria_pai_id
);

SELECT id INTO @categoria_limpeza_id 
FROM categorias 
WHERE nome = 'Limpeza' AND parent_id = @categoria_pai_id
LIMIT 1;

-- Subcategorias de Limpeza
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
    @categoria_limpeza_id,
    nome_temp,
    descricao_temp,
    'ATIVA',
    ordem_temp,
    0,
    1,
    NOW(),
    NOW()
FROM (
    SELECT 'Limpeza de caixa d''água' as nome_temp, 'Limpeza e higienização completa de caixa d''água' as descricao_temp, 1 as ordem_temp
    UNION ALL SELECT 'Limpeza de caixa de gordura', 'Limpeza e desentupimento de caixa de gordura', 2
    UNION ALL SELECT 'Limpeza de calhas', 'Limpeza e desobstrução de calhas e rufos', 3
    UNION ALL SELECT 'Limpeza de ralos', 'Limpeza e desentupimento de ralos', 4
    UNION ALL SELECT 'Limpeza de sifões', 'Limpeza e desentupimento de sifões', 5
) AS temp
WHERE NOT EXISTS (
    SELECT 1 FROM subcategorias 
    WHERE categoria_id = @categoria_limpeza_id 
    AND nome = temp.nome_temp
);

-- =====================================================
-- CATEGORIA FILHA: INSTALAÇÃO
-- =====================================================
SET @categoria_instalacao_id = NULL;

SELECT id INTO @categoria_instalacao_id 
FROM categorias 
WHERE nome = 'Instalação' AND parent_id = @categoria_pai_id
LIMIT 1;

INSERT INTO categorias (
    nome, 
    descricao, 
    icone, 
    cor, 
    status, 
    ordem, 
    tipo_imovel, 
    parent_id,
    created_at, 
    updated_at
)
SELECT 
    'Instalação',
    'Serviços de instalação e substituição de equipamentos',
    'fas fa-wrench',
    '#8B5CF6',
    'ATIVA',
    2,
    'AMBOS',
    @categoria_pai_id,
    NOW(),
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM categorias WHERE nome = 'Instalação' AND parent_id = @categoria_pai_id
);

SELECT id INTO @categoria_instalacao_id 
FROM categorias 
WHERE nome = 'Instalação' AND parent_id = @categoria_pai_id
LIMIT 1;

-- Subcategorias de Instalação
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
    @categoria_instalacao_id,
    nome_temp,
    descricao_temp,
    'ATIVA',
    ordem_temp,
    0,
    1,
    NOW(),
    NOW()
FROM (
    SELECT 'Instalação e substituição de ventilador' as nome_temp, 'Instalação ou substituição de ventiladores' as descricao_temp, 1 as ordem_temp
    UNION ALL SELECT 'Instalação e substituição de ar-condicionado (mini-split)', 'Instalação ou substituição de ar-condicionado tipo mini-split', 2
    UNION ALL SELECT 'Instalação e substituição de ar-condicionado (compacto)', 'Instalação ou substituição de ar-condicionado compacto', 3
    UNION ALL SELECT 'Instalação e substituição de antenas', 'Instalação ou substituição de antenas de TV e internet', 4
    UNION ALL SELECT 'Instalação e substituição de lâmpadas', 'Instalação ou substituição de lâmpadas', 5
    UNION ALL SELECT 'Instalação e substituição de luminárias', 'Instalação ou substituição de luminárias', 6
    UNION ALL SELECT 'Instalação e substituição de reatores', 'Instalação ou substituição de reatores elétricos', 7
    UNION ALL SELECT 'Padronização de tomadas', 'Padronização e instalação de tomadas elétricas', 8
    UNION ALL SELECT 'Locação de caçamba (entulho de obras)', 'Locação de caçamba para descarte de entulho de obras', 9
    UNION ALL SELECT 'Rejuntamento de piso', 'Rejuntamento e reparo de pisos', 10
) AS temp
WHERE NOT EXISTS (
    SELECT 1 FROM subcategorias 
    WHERE categoria_id = @categoria_instalacao_id 
    AND nome = temp.nome_temp
);

-- =====================================================
-- VERIFICAÇÃO FINAL
-- =====================================================
SELECT 
    'Categoria Pai' as tipo,
    id,
    nome,
    parent_id,
    status
FROM categorias 
WHERE nome = 'Manutenção e Prevenção'
UNION ALL
SELECT 
    'Categoria Filha' as tipo,
    id,
    nome,
    parent_id,
    status
FROM categorias 
WHERE parent_id = @categoria_pai_id
ORDER BY tipo, nome;

SELECT 
    'Subcategorias de Limpeza' as categoria,
    COUNT(*) as total
FROM subcategorias 
WHERE categoria_id = @categoria_limpeza_id
UNION ALL
SELECT 
    'Subcategorias de Instalação' as categoria,
    COUNT(*) as total
FROM subcategorias 
WHERE categoria_id = @categoria_instalacao_id;

