-- Script para apagar todos os dados da tabela locatarios_contratos
-- ATENÇÃO: Esta operação é irreversível!

-- Opção 1: TRUNCATE (mais rápido, reinicia auto_increment)
TRUNCATE TABLE locatarios_contratos;

-- Opção 2: DELETE (mais lento, mas permite WHERE se necessário)
-- DELETE FROM locatarios_contratos;

