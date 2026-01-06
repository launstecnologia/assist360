-- Script para criar condições do ciclo de agendamento

-- Inserir condições se não existirem
INSERT INTO condicoes (nome, cor, icone, ordem, status) 
SELECT 'Aguardando Resposta do Prestador', '#f59e0b', 'fa-clock', 1, 'ATIVO'
WHERE NOT EXISTS (SELECT 1 FROM condicoes WHERE nome = 'Aguardando Resposta do Prestador');

INSERT INTO condicoes (nome, cor, icone, ordem, status) 
SELECT 'Data Aceita pelo Prestador', '#10b981', 'fa-check-circle', 2, 'ATIVO'
WHERE NOT EXISTS (SELECT 1 FROM condicoes WHERE nome = 'Data Aceita pelo Prestador');

INSERT INTO condicoes (nome, cor, icone, ordem, status) 
SELECT 'Prestador sem disponibilidade', '#ef4444', 'fa-times-circle', 3, 'ATIVO'
WHERE NOT EXISTS (SELECT 1 FROM condicoes WHERE nome = 'Prestador sem disponibilidade');

INSERT INTO condicoes (nome, cor, icone, ordem, status) 
SELECT 'Aguardando Confirmação do Locatário', '#3b82f6', 'fa-hourglass-half', 4, 'ATIVO'
WHERE NOT EXISTS (SELECT 1 FROM condicoes WHERE nome = 'Aguardando Confirmação do Locatário');

INSERT INTO condicoes (nome, cor, icone, ordem, status) 
SELECT 'Data Aceita pelo Locatário', '#10b981', 'fa-check-double', 5, 'ATIVO'
WHERE NOT EXISTS (SELECT 1 FROM condicoes WHERE nome = 'Data Aceita pelo Locatário');

INSERT INTO condicoes (nome, cor, icone, ordem, status) 
SELECT 'Datas Recusadas pelo Locatário', '#f97316', 'fa-xmark-circle', 6, 'ATIVO'
WHERE NOT EXISTS (SELECT 1 FROM condicoes WHERE nome = 'Datas Recusadas pelo Locatário');

INSERT INTO condicoes (nome, cor, icone, ordem, status) 
SELECT 'Serviço Agendado / Data Confirmada', '#059669', 'fa-calendar-check', 7, 'ATIVO'
WHERE NOT EXISTS (SELECT 1 FROM condicoes WHERE nome = 'Serviço Agendado / Data Confirmada');

