-- Adicionar coluna token_acesso na tabela solicitacoes
-- Este token permite que pessoas vejam o status da solicitação sem precisar fazer login
-- É usado quando a solicitação é criada a partir de uma solicitação manual com CPF registrado

ALTER TABLE solicitacoes
ADD COLUMN token_acesso VARCHAR(64) UNIQUE NULL AFTER updated_at;

-- Criar índice para melhorar performance nas buscas por token
CREATE INDEX idx_token_acesso ON solicitacoes(token_acesso);

