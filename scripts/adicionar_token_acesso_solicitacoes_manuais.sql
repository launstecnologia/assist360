-- Adicionar campo token_acesso na tabela solicitacoes_manuais
-- Este token permite acesso público ao formulário de solicitação manual sem necessidade de login

ALTER TABLE solicitacoes_manuais
ADD COLUMN IF NOT EXISTS token_acesso VARCHAR(64) NULL UNIQUE AFTER id,
ADD INDEX idx_token_acesso (token_acesso);


