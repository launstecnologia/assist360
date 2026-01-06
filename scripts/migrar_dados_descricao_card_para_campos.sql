-- =====================================================
-- MIGRAR DADOS DE descricao_card PARA CAMPOS INDIVIDUAIS
-- =====================================================
-- Este script extrai local_manutencao, finalidade_locacao e tipo_imovel
-- de descricao_card e salva nos campos individuais
-- =====================================================

-- Atualizar local_manutencao (primeira linha que não contém "Finalidade:" ou "Tipo:")
UPDATE solicitacoes
SET local_manutencao = TRIM(SUBSTRING_INDEX(descricao_card, CHAR(10), 1))
WHERE descricao_card IS NOT NULL 
  AND descricao_card != ''
  AND local_manutencao IS NULL
  AND SUBSTRING_INDEX(descricao_card, CHAR(10), 1) NOT LIKE '%Finalidade:%'
  AND SUBSTRING_INDEX(descricao_card, CHAR(10), 1) NOT LIKE '%Tipo:%';

-- Atualizar finalidade_locacao (linha que contém "Finalidade:")
UPDATE solicitacoes
SET finalidade_locacao = TRIM(REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(descricao_card, 'Finalidade:', -1), CHAR(10), 1), 'Finalidade:', ''))
WHERE descricao_card IS NOT NULL 
  AND descricao_card LIKE '%Finalidade:%'
  AND finalidade_locacao IS NULL;

-- Atualizar tipo_imovel (linha que contém "Tipo:")
UPDATE solicitacoes
SET tipo_imovel = TRIM(REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(descricao_card, 'Tipo:', -1), CHAR(10), 1), 'Tipo:', ''))
WHERE descricao_card IS NOT NULL 
  AND descricao_card LIKE '%Tipo:%'
  AND tipo_imovel IS NULL;

-- Verificar resultado
SELECT 
    id,
    numero_solicitacao,
    local_manutencao,
    finalidade_locacao,
    tipo_imovel,
    LEFT(descricao_card, 50) as descricao_card_preview
FROM solicitacoes
WHERE local_manutencao IS NOT NULL 
   OR finalidade_locacao IS NOT NULL 
   OR tipo_imovel IS NOT NULL
ORDER BY id DESC
LIMIT 10;

