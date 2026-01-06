-- Script: adicionar_campos_lembretes_pecas.sql
-- Objetivo: adicionar campos utilizados na automação "Precisa comprar peças"
-- Campos:
--   data_limite_peca       DATE        -> prazo máximo para compra das peças
--   data_ultimo_lembrete   DATETIME    -> último envio de lembrete diário
--   lembretes_enviados     INT         -> contador de lembretes disparados

ALTER TABLE `solicitacoes`
    ADD COLUMN `data_limite_peca` DATE NULL AFTER `horarios_opcoes`,
    ADD COLUMN `data_ultimo_lembrete` DATETIME NULL AFTER `data_limite_peca`,
    ADD COLUMN `lembretes_enviados` INT NOT NULL DEFAULT 0 AFTER `data_ultimo_lembrete`;

-- Atualizar registros existentes para evitar inconsistências
UPDATE `solicitacoes`
   SET `lembretes_enviados` = 0
 WHERE `lembretes_enviados` IS NULL;

