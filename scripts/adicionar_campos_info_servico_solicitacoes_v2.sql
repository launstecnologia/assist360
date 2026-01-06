-- =====================================================
-- ADICIONAR CAMPOS DE INFORMAÇÕES DO SERVIÇO NA TABELA solicitacoes
-- =====================================================
-- Versão simplificada que tenta adicionar e ignora erros se já existir
-- =====================================================

-- Adicionar coluna local_manutencao
-- Se já existir, o erro será ignorado pelo sistema
ALTER TABLE `solicitacoes` ADD COLUMN `local_manutencao` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Local onde será realizada a manutenção' AFTER `descricao_card`;

-- Adicionar coluna finalidade_locacao
ALTER TABLE `solicitacoes` ADD COLUMN `finalidade_locacao` ENUM('RESIDENCIAL', 'COMERCIAL') NULL DEFAULT NULL COMMENT 'Finalidade da locação: Residencial ou Comercial' AFTER `local_manutencao`;

-- Adicionar coluna tipo_imovel
ALTER TABLE `solicitacoes` ADD COLUMN `tipo_imovel` ENUM('CASA', 'APARTAMENTO') NULL DEFAULT NULL COMMENT 'Tipo do imóvel: Casa ou Apartamento' AFTER `finalidade_locacao`;

