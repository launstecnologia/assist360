ALTER TABLE solicitacoes ADD COLUMN local_manutencao VARCHAR(255) NULL AFTER descricao_card;
ALTER TABLE solicitacoes ADD COLUMN finalidade_locacao ENUM('RESIDENCIAL', 'COMERCIAL') NULL AFTER local_manutencao;
ALTER TABLE solicitacoes ADD COLUMN tipo_imovel ENUM('CASA', 'APARTAMENTO') NULL AFTER finalidade_locacao;

