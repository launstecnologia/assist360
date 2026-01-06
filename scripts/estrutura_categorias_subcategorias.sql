-- =====================================================
-- ESTRUTURA DAS TABELAS: CATEGORIAS E SUBCATEGORIAS
-- =====================================================
-- Este script contém a estrutura completa das tabelas
-- e exemplos de INSERT para gerar novos registros
-- =====================================================

-- =====================================================
-- TABELA: categorias
-- =====================================================
CREATE TABLE IF NOT EXISTS `categorias` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(255) NOT NULL COMMENT 'Nome da categoria',
  `descricao` TEXT NULL COMMENT 'Descrição da categoria',
  `icone` VARCHAR(50) NULL COMMENT 'Ícone FontAwesome (ex: fa-tools)',
  `cor` VARCHAR(7) NULL DEFAULT '#3B82F6' COMMENT 'Cor hexadecimal (ex: #3B82F6)',
  `status` ENUM('ATIVA', 'INATIVA') NOT NULL DEFAULT 'ATIVA' COMMENT 'Status da categoria',
  `ordem` INT(11) DEFAULT 0 COMMENT 'Ordem de exibição',
  `tipo_imovel` ENUM('RESIDENCIAL', 'COMERCIAL', 'AMBOS') NOT NULL DEFAULT 'AMBOS' COMMENT 'Tipo de imóvel: Residencial, Comercial ou Ambos',
  `tipo_assistencia` VARCHAR(50) NULL COMMENT 'Tipo de assistência (opcional)',
  `prazo_minimo` INT(11) NULL COMMENT 'Prazo mínimo em dias (opcional)',
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_ordem` (`ordem`),
  KEY `idx_tipo_imovel` (`tipo_imovel`),
  KEY `idx_tipo_assistencia` (`tipo_assistencia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Categorias de serviços';

-- =====================================================
-- TABELA: subcategorias
-- =====================================================
CREATE TABLE IF NOT EXISTS `subcategorias` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `categoria_id` INT(11) NOT NULL COMMENT 'ID da categoria pai',
  `nome` VARCHAR(255) NOT NULL COMMENT 'Nome da subcategoria',
  `descricao` TEXT NULL COMMENT 'Descrição da subcategoria',
  `tempo_estimado` INT(11) NULL COMMENT 'Tempo estimado em minutos',
  `status` ENUM('ATIVA', 'INATIVA') NOT NULL DEFAULT 'ATIVA' COMMENT 'Status da subcategoria',
  `ordem` INT(11) DEFAULT 0 COMMENT 'Ordem de exibição',
  `is_emergencial` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Se é emergencial (não precisa agendamento)',
  `prazo_minimo` INT(11) NULL COMMENT 'Prazo mínimo em dias para agendamento',
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_categoria` (`categoria_id`),
  KEY `idx_status` (`status`),
  KEY `idx_ordem` (`ordem`),
  KEY `idx_emergencial` (`is_emergencial`),
  CONSTRAINT `fk_subcategorias_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Subcategorias de serviços';

-- =====================================================
-- EXEMPLOS DE INSERT - CATEGORIAS
-- =====================================================
-- Use estes exemplos como base para criar novas categorias
-- Ajuste os valores conforme necessário

-- Exemplo 1: Categoria de Elétrica (Ambos os tipos)
INSERT INTO `categorias` (`nome`, `descricao`, `icone`, `cor`, `status`, `ordem`, `tipo_imovel`, `created_at`, `updated_at`)
VALUES (
    'Elétrica',
    'Serviços relacionados a instalações elétricas',
    'fa-bolt',
    '#FFD700',
    'ATIVA',
    1,
    'AMBOS',
    NOW(),
    NOW()
);

-- Exemplo 2: Categoria de Hidráulica (Ambos os tipos)
INSERT INTO `categorias` (`nome`, `descricao`, `icone`, `cor`, `status`, `ordem`, `tipo_imovel`, `created_at`, `updated_at`)
VALUES (
    'Hidráulica',
    'Serviços relacionados a instalações hidráulicas',
    'fa-tint',
    '#1E90FF',
    'ATIVA',
    2,
    'AMBOS',
    NOW(),
    NOW()
);

-- Exemplo 3: Categoria de Alvenaria (Ambos os tipos)
INSERT INTO `categorias` (`nome`, `descricao`, `icone`, `cor`, `status`, `ordem`, `tipo_imovel`, `created_at`, `updated_at`)
VALUES (
    'Alvenaria',
    'Serviços relacionados a alvenaria e construção',
    'fa-hammer',
    '#8B4513',
    'ATIVA',
    3,
    'AMBOS',
    NOW(),
    NOW()
);

-- Exemplo 4: Categoria de Pintura (Ambos os tipos)
INSERT INTO `categorias` (`nome`, `descricao`, `icone`, `cor`, `status`, `ordem`, `tipo_imovel`, `created_at`, `updated_at`)
VALUES (
    'Pintura',
    'Serviços relacionados a pintura',
    'fa-paint-brush',
    '#FF6347',
    'ATIVA',
    4,
    'AMBOS',
    NOW(),
    NOW()
);

-- Exemplo 5: Categoria de Serralheria (Ambos os tipos)
INSERT INTO `categorias` (`nome`, `descricao`, `icone`, `cor`, `status`, `ordem`, `tipo_imovel`, `created_at`, `updated_at`)
VALUES (
    'Serralheria',
    'Serviços relacionados a serralheria',
    'fa-wrench',
    '#696969',
    'ATIVA',
    5,
    'AMBOS',
    NOW(),
    NOW()
);

-- Exemplo 6: Categoria apenas Residencial
INSERT INTO `categorias` (`nome`, `descricao`, `icone`, `cor`, `status`, `ordem`, `tipo_imovel`, `created_at`, `updated_at`)
VALUES (
    'Jardinagem Residencial',
    'Serviços de jardinagem para imóveis residenciais',
    'fa-leaf',
    '#228B22',
    'ATIVA',
    6,
    'RESIDENCIAL',
    NOW(),
    NOW()
);

-- Exemplo 7: Categoria apenas Comercial
INSERT INTO `categorias` (`nome`, `descricao`, `icone`, `cor`, `status`, `ordem`, `tipo_imovel`, `created_at`, `updated_at`)
VALUES (
    'Manutenção Predial',
    'Serviços de manutenção para prédios comerciais',
    'fa-building',
    '#4682B4',
    'ATIVA',
    7,
    'COMERCIAL',
    NOW(),
    NOW()
);

-- =====================================================
-- EXEMPLOS DE INSERT - SUBCATEGORIAS
-- =====================================================
-- IMPORTANTE: Substitua o categoria_id pelo ID real da categoria criada acima
-- Use: SELECT id FROM categorias WHERE nome = 'Nome da Categoria';

-- Exemplo 1: Subcategorias de Elétrica (assumindo categoria_id = 1)
INSERT INTO `subcategorias` (`categoria_id`, `nome`, `descricao`, `tempo_estimado`, `status`, `ordem`, `is_emergencial`, `created_at`, `updated_at`)
VALUES 
(
    (SELECT id FROM categorias WHERE nome = 'Elétrica' LIMIT 1),
    'Troca de Tomada',
    'Troca de tomadas elétricas',
    30,
    'ATIVA',
    1,
    0,
    NOW(),
    NOW()
),
(
    (SELECT id FROM categorias WHERE nome = 'Elétrica' LIMIT 1),
    'Troca de Interruptor',
    'Troca de interruptores',
    20,
    'ATIVA',
    2,
    0,
    NOW(),
    NOW()
),
(
    (SELECT id FROM categorias WHERE nome = 'Elétrica' LIMIT 1),
    'Instalação de Luminária',
    'Instalação de luminárias e lustres',
    60,
    'ATIVA',
    3,
    0,
    NOW(),
    NOW()
),
(
    (SELECT id FROM categorias WHERE nome = 'Elétrica' LIMIT 1),
    'Reparo de Curto Circuito',
    'Reparo de curto circuito - EMERGÊNCIA',
    120,
    'ATIVA',
    4,
    1,
    NOW(),
    NOW()
);

-- Exemplo 2: Subcategorias de Hidráulica (assumindo categoria_id = 2)
INSERT INTO `subcategorias` (`categoria_id`, `nome`, `descricao`, `tempo_estimado`, `status`, `ordem`, `is_emergencial`, `created_at`, `updated_at`)
VALUES 
(
    (SELECT id FROM categorias WHERE nome = 'Hidráulica' LIMIT 1),
    'Vazamento de Torneira',
    'Reparo de vazamento em torneiras',
    45,
    'ATIVA',
    1,
    0,
    NOW(),
    NOW()
),
(
    (SELECT id FROM categorias WHERE nome = 'Hidráulica' LIMIT 1),
    'Vazamento de Cano',
    'Reparo de vazamento em canos',
    90,
    'ATIVA',
    2,
    0,
    NOW(),
    NOW()
),
(
    (SELECT id FROM categorias WHERE nome = 'Hidráulica' LIMIT 1),
    'Desentupimento',
    'Desentupimento de ralos e canos',
    60,
    'ATIVA',
    3,
    0,
    NOW(),
    NOW()
),
(
    (SELECT id FROM categorias WHERE nome = 'Hidráulica' LIMIT 1),
    'Vazamento Crítico',
    'Vazamento crítico que requer atenção imediata - EMERGÊNCIA',
    120,
    'ATIVA',
    4,
    1,
    NOW(),
    NOW()
);

-- Exemplo 3: Subcategorias de Alvenaria (assumindo categoria_id = 3)
INSERT INTO `subcategorias` (`categoria_id`, `nome`, `descricao`, `tempo_estimado`, `status`, `ordem`, `is_emergencial`, `created_at`, `updated_at`)
VALUES 
(
    (SELECT id FROM categorias WHERE nome = 'Alvenaria' LIMIT 1),
    'Reboco',
    'Serviço de reboco em paredes',
    180,
    'ATIVA',
    1,
    0,
    NOW(),
    NOW()
),
(
    (SELECT id FROM categorias WHERE nome = 'Alvenaria' LIMIT 1),
    'Assentamento de Azulejo',
    'Assentamento de azulejos',
    240,
    'ATIVA',
    2,
    0,
    NOW(),
    NOW()
),
(
    (SELECT id FROM categorias WHERE nome = 'Alvenaria' LIMIT 1),
    'Reparo de Tijolo',
    'Reparo de tijolos soltos ou quebrados',
    120,
    'ATIVA',
    3,
    0,
    NOW(),
    NOW()
);

-- Exemplo 4: Subcategorias de Pintura (assumindo categoria_id = 4)
INSERT INTO `subcategorias` (`categoria_id`, `nome`, `descricao`, `tempo_estimado`, `status`, `ordem`, `is_emergencial`, `created_at`, `updated_at`)
VALUES 
(
    (SELECT id FROM categorias WHERE nome = 'Pintura' LIMIT 1),
    'Pintura Interna',
    'Pintura de ambientes internos',
    300,
    'ATIVA',
    1,
    0,
    NOW(),
    NOW()
),
(
    (SELECT id FROM categorias WHERE nome = 'Pintura' LIMIT 1),
    'Pintura Externa',
    'Pintura de fachadas e áreas externas',
    360,
    'ATIVA',
    2,
    0,
    NOW(),
    NOW()
),
(
    (SELECT id FROM categorias WHERE nome = 'Pintura' LIMIT 1),
    'Retoque de Pintura',
    'Retoque em áreas específicas',
    60,
    'ATIVA',
    3,
    0,
    NOW(),
    NOW()
);

-- Exemplo 5: Subcategorias de Serralheria (assumindo categoria_id = 5)
INSERT INTO `subcategorias` (`categoria_id`, `nome`, `descricao`, `tempo_estimado`, `status`, `ordem`, `is_emergencial`, `created_at`, `updated_at`)
VALUES 
(
    (SELECT id FROM categorias WHERE nome = 'Serralheria' LIMIT 1),
    'Reparo de Porta',
    'Reparo de portas e fechaduras',
    90,
    'ATIVA',
    1,
    0,
    NOW(),
    NOW()
),
(
    (SELECT id FROM categorias WHERE nome = 'Serralheria' LIMIT 1),
    'Reparo de Janela',
    'Reparo de janelas e ferragens',
    60,
    'ATIVA',
    2,
    0,
    NOW(),
    NOW()
),
(
    (SELECT id FROM categorias WHERE nome = 'Serralheria' LIMIT 1),
    'Instalação de Grade',
    'Instalação de grades de proteção',
    180,
    'ATIVA',
    3,
    0,
    NOW(),
    NOW()
);

-- =====================================================
-- QUERIES ÚTEIS PARA CONSULTA
-- =====================================================

-- Listar todas as categorias ativas
-- SELECT * FROM categorias WHERE status = 'ATIVA' ORDER BY ordem, nome;

-- Listar todas as subcategorias de uma categoria específica
-- SELECT * FROM subcategorias WHERE categoria_id = ? AND status = 'ATIVA' ORDER BY ordem, nome;

-- Listar categorias com suas subcategorias
-- SELECT 
--     c.id as categoria_id,
--     c.nome as categoria_nome,
--     sc.id as subcategoria_id,
--     sc.nome as subcategoria_nome,
--     sc.is_emergencial
-- FROM categorias c
-- LEFT JOIN subcategorias sc ON c.id = sc.categoria_id AND sc.status = 'ATIVA'
-- WHERE c.status = 'ATIVA'
-- ORDER BY c.ordem, c.nome, sc.ordem, sc.nome;

-- =====================================================
-- NOTAS IMPORTANTES
-- =====================================================
-- 1. O campo 'categoria_id' na tabela subcategorias é obrigatório
-- 2. O campo 'tipo_imovel' pode ser:
--    - 'RESIDENCIAL': Apenas para imóveis residenciais
--    - 'COMERCIAL': Apenas para imóveis comerciais
--    - 'AMBOS': Para ambos os tipos de imóveis (padrão)
-- 3. O campo 'is_emergencial' indica se a subcategoria não precisa agendamento
-- 4. O campo 'ordem' controla a ordem de exibição (menor número aparece primeiro)
-- 5. O campo 'status' pode ser 'ATIVA' ou 'INATIVA'
-- 6. Os campos 'created_at' e 'updated_at' são preenchidos automaticamente se usar NOW()
-- 7. O campo 'cor' aceita valores hexadecimais (ex: #3B82F6)
-- 8. O campo 'icone' aceita classes FontAwesome (ex: fa-tools, fa-bolt)
-- 9. O campo 'tempo_estimado' é em minutos
-- 10. O campo 'prazo_minimo' é em dias (opcional)

-- =====================================================
-- QUERIES ÚTEIS COM TIPO DE IMÓVEL
-- =====================================================

-- Listar categorias apenas residenciais
-- SELECT * FROM categorias WHERE status = 'ATIVA' AND tipo_imovel IN ('RESIDENCIAL', 'AMBOS') ORDER BY ordem, nome;

-- Listar categorias apenas comerciais
-- SELECT * FROM categorias WHERE status = 'ATIVA' AND tipo_imovel IN ('COMERCIAL', 'AMBOS') ORDER BY ordem, nome;

-- Listar categorias por tipo específico
-- SELECT * FROM categorias WHERE status = 'ATIVA' AND tipo_imovel = 'RESIDENCIAL' ORDER BY ordem, nome;
-- SELECT * FROM categorias WHERE status = 'ATIVA' AND tipo_imovel = 'COMERCIAL' ORDER BY ordem, nome;
-- SELECT * FROM categorias WHERE status = 'ATIVA' AND tipo_imovel = 'AMBOS' ORDER BY ordem, nome;

