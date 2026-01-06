-- Adicionar coluna caminho_arquivo na tabela historico_uploads
ALTER TABLE historico_uploads 
ADD COLUMN caminho_arquivo VARCHAR(500) NULL 
COMMENT 'Caminho relativo do arquivo CSV salvo no servidor'
AFTER nome_arquivo;

-- Adicionar índice para melhorar performance
ALTER TABLE historico_uploads 
ADD INDEX idx_caminho_arquivo (caminho_arquivo);

