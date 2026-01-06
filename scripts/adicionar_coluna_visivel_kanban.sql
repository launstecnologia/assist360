-- Script para adicionar a coluna visivel_kanban na tabela status
-- Execute este script no seu banco de dados MySQL/MariaDB

ALTER TABLE `status` 
ADD COLUMN `visivel_kanban` TINYINT(1) DEFAULT 1 NOT NULL 
AFTER `ordem`;

-- Atualizar registros existentes para serem visíveis no Kanban por padrão
UPDATE `status` SET `visivel_kanban` = 1 WHERE `visivel_kanban` IS NULL;

