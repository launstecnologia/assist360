-- Script para adicionar coluna horarios_indisponiveis na tabela solicitacoes
-- Execute este script se a coluna ainda não existir

ALTER TABLE solicitacoes 
ADD COLUMN IF NOT EXISTS horarios_indisponiveis TINYINT(1) DEFAULT 0 COMMENT 'Indica se nenhum horário está disponível (1 = sim, 0 = não)';






