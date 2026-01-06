-- Adiciona coluna validacao_utilizacao na tabela solicitacoes
-- Este campo indica se o CPF está dentro do limite de utilizações permitido
-- 1 = APROVADO (dentro do limite), 0 = RECUSADO (limite excedido), NULL = não verificado

ALTER TABLE solicitacoes 
ADD COLUMN IF NOT EXISTS validacao_utilizacao TINYINT(1) NULL DEFAULT NULL 
COMMENT 'Validação de limite de utilização por CPF: 1=aprovado, 0=recusado, NULL=não verificado';

-- Índice para facilitar consultas de validação
CREATE INDEX IF NOT EXISTS idx_solicitacoes_validacao_utilizacao ON solicitacoes(validacao_utilizacao);

