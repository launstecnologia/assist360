-- Adicionar coluna condicoes_gerais na tabela subcategorias
ALTER TABLE subcategorias 
ADD COLUMN condicoes_gerais TEXT NULL 
AFTER descricao;

-- Adicionar comentário na coluna
ALTER TABLE subcategorias 
MODIFY COLUMN condicoes_gerais TEXT NULL 
COMMENT 'Condições gerais e termos específicos da subcategoria';

