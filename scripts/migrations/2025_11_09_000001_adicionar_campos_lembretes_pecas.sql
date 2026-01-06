-- Migration: 2025_11_09_000001_adicionar_campos_lembretes_pecas.sql
-- Objetivo: adicionar campos utilizados pela automação "Precisa comprar peças"
-- Tabela: solicitacoes

ALTER TABLE `solicitacoes`
    ADD COLUMN `data_limite_peca` DATE NULL AFTER `horarios_opcoes`,
    ADD COLUMN `data_ultimo_lembrete` DATETIME NULL AFTER `data_limite_peca`,
    ADD COLUMN `lembretes_enviados` INT NOT NULL DEFAULT 0 AFTER `data_ultimo_lembrete`;

-- Garantir contador inicial consistente
UPDATE `solicitacoes`
   SET `lembretes_enviados` = 0
 WHERE `lembretes_enviados` IS NULL;

