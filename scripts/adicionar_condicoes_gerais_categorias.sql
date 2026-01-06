-- Adicionar coluna condicoes_gerais na tabela categorias
ALTER TABLE categorias 
ADD COLUMN condicoes_gerais TEXT NULL 
AFTER descricao;

-- Adicionar comentário na coluna
ALTER TABLE categorias 
MODIFY COLUMN condicoes_gerais TEXT NULL 
COMMENT 'Condições gerais e termos específicos da categoria';

