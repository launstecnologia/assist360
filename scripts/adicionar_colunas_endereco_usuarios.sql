-- Adicionar colunas de endereço na tabela usuarios
-- Execute este script se quiser adicionar campos de endereço aos usuários

-- Adicionar coluna endereco (logradouro)
ALTER TABLE usuarios 
ADD COLUMN endereco VARCHAR(255) NULL AFTER telefone;

-- Adicionar coluna numero
ALTER TABLE usuarios 
ADD COLUMN numero VARCHAR(20) NULL AFTER endereco;

-- Adicionar coluna complemento
ALTER TABLE usuarios 
ADD COLUMN complemento VARCHAR(100) NULL AFTER numero;

-- Adicionar coluna bairro
ALTER TABLE usuarios 
ADD COLUMN bairro VARCHAR(100) NULL AFTER complemento;

-- Adicionar coluna cidade
ALTER TABLE usuarios 
ADD COLUMN cidade VARCHAR(100) NULL AFTER bairro;

-- Adicionar coluna uf
ALTER TABLE usuarios 
ADD COLUMN uf VARCHAR(2) NULL AFTER cidade;

-- Adicionar coluna cep
ALTER TABLE usuarios 
ADD COLUMN cep VARCHAR(9) NULL AFTER uf;

