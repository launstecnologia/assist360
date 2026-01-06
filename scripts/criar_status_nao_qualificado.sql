-- Criar status "Não qualificado" na tabela status
-- Verificar se o status já existe antes de criar
INSERT INTO status (nome, cor, icone, ordem, visivel_kanban, status, created_at, updated_at)
SELECT 
    'Não qualificado',
    '#6B7280', -- Cor cinza
    'fa-times-circle',
    COALESCE(MAX(ordem), 0) + 1,
    1, -- Visível no Kanban
    'ATIVO',
    NOW(),
    NOW()
FROM status
WHERE NOT EXISTS (
    SELECT 1 FROM status WHERE nome = 'Não qualificado' AND status = 'ATIVO'
);

