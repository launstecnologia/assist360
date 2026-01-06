-- Adiciona coluna limite_solicitacoes_12_meses na tabela categorias
-- Este campo define o limite máximo de solicitações permitidas por contrato
-- em uma categoria específica dentro de um período de 12 meses
-- NULL = ilimitado

ALTER TABLE categorias 
ADD COLUMN limite_solicitacoes_12_meses INT NULL 
COMMENT 'Limite de solicitações permitidas por contrato em 12 meses. NULL = ilimitado';

