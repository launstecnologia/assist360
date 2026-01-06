-- Script simples para adicionar coluna CPF na tabela usuarios
-- Execute este script no banco de dados

-- Adicionar coluna CPF (se não existir, pode dar erro, mas é seguro ignorar)
ALTER TABLE usuarios 
ADD COLUMN cpf VARCHAR(14) NULL AFTER telefone;

-- Se quiser tornar o CPF único (descomente a linha abaixo)
-- ALTER TABLE usuarios ADD UNIQUE INDEX unique_cpf (cpf);

