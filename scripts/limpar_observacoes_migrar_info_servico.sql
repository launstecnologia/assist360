-- Script para limpar informações técnicas das observações e movê-las para descricao_card
-- Este script processa solicitações que têm "Finalidade:" e "Tipo:" nas observações
-- e move essas informações para descricao_card, limpando as observações

-- IMPORTANTE: Este script deve ser executado via PHP devido à complexidade da lógica
-- Use o script PHP: limpar_observacoes_migrar_info_servico.php

-- Script SQL alternativo (mais simples, mas pode precisar de ajustes):
UPDATE solicitacoes
SET 
    -- Extrair primeira linha como local_manutencao (se não for Finalidade ou Tipo)
    descricao_card = CASE 
        WHEN observacoes LIKE '%Finalidade:%' OR observacoes LIKE '%Tipo:%' THEN
            CONCAT(
                -- Primeira linha (local_manutencao) - pegar até a primeira quebra de linha
                CASE 
                    WHEN SUBSTRING_INDEX(observacoes, CHAR(10), 1) NOT LIKE '%Finalidade:%' 
                     AND SUBSTRING_INDEX(observacoes, CHAR(10), 1) NOT LIKE '%Tipo:%'
                    THEN SUBSTRING_INDEX(observacoes, CHAR(10), 1)
                    ELSE ''
                END,
                -- Adicionar Finalidade se existir
                CASE 
                    WHEN observacoes LIKE '%Finalidade:%' 
                    THEN CONCAT(
                        CHAR(10),
                        SUBSTRING_INDEX(
                            SUBSTRING_INDEX(observacoes, 'Finalidade:', -1),
                            CHAR(10),
                            1
                        )
                    )
                    ELSE ''
                END,
                -- Adicionar Tipo se existir
                CASE 
                    WHEN observacoes LIKE '%Tipo:%' 
                    THEN CONCAT(
                        CHAR(10),
                        SUBSTRING_INDEX(
                            SUBSTRING_INDEX(observacoes, 'Tipo:', -1),
                            CHAR(10),
                            1
                        )
                    )
                    ELSE ''
                END
            )
        ELSE descricao_card
    END,
    -- Limpar observações removendo linhas com Finalidade e Tipo
    observacoes = CASE 
        WHEN observacoes LIKE '%Finalidade:%' OR observacoes LIKE '%Tipo:%' THEN
            -- Remover linhas que contêm Finalidade: ou Tipo:
            TRIM(
                REPLACE(
                    REPLACE(
                        REPLACE(
                            observacoes,
                            CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(observacoes, 'Finalidade:', 1), CHAR(10), -1), 'Finalidade:', SUBSTRING_INDEX(SUBSTRING_INDEX(observacoes, 'Finalidade:', -1), CHAR(10), 1)),
                            ''
                        ),
                        CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(observacoes, 'Tipo:', 1), CHAR(10), -1), 'Tipo:', SUBSTRING_INDEX(SUBSTRING_INDEX(observacoes, 'Tipo:', -1), CHAR(10), 1)),
                        ''
                    ),
                    -- Remover primeira linha se for local_manutencao
                    SUBSTRING_INDEX(observacoes, CHAR(10), 1),
                    ''
                )
            )
        ELSE observacoes
    END
WHERE 
    observacoes IS NOT NULL 
    AND observacoes != ''
    AND (observacoes LIKE '%Finalidade:%' OR observacoes LIKE '%Tipo:%')
    AND (descricao_card IS NULL OR descricao_card = '');
