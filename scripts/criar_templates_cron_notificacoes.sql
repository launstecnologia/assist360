-- ============================================
-- Criar Templates de WhatsApp para Crons
-- ============================================
-- Este script cria os templates necessários para:
-- 1. Notificação Pré-Serviço (1 hora antes)
-- 2. Notificação Pós-Serviço (após horário final)

-- ============================================
-- 1. TEMPLATE: Lembrete Pré-Serviço
-- ============================================
-- Atualizar se já existir
UPDATE whatsapp_templates 
SET corpo = 'Olá {{cliente_nome}}!

Nosso prestador de serviço estará chegando em aproximadamente 1 hora.

📅 Data: {{data_agendamento}}
⏰ Período de chegada: {{periodo_chegada}}

Por favor, esteja disponível neste período para receber o prestador.

Após a conclusão da visita, clique no link abaixo para nos informar como foi o serviço:

{{link_acoes_servico}}

Protocolo: {{protocol}}

Atenciosamente,
Equipe KSS Assistência 360',
    ativo = 1,
    padrao = 1,
    updated_at = NOW()
WHERE tipo = 'Lembrete Pré-Serviço' 
AND padrao = 1;

-- Criar se não existir
INSERT INTO whatsapp_templates (nome, tipo, corpo, ativo, padrao, created_at, updated_at)
SELECT 
    'Lembrete Pré-Serviço - Padrão',
    'Lembrete Pré-Serviço',
    'Olá {{cliente_nome}}!

Nosso prestador de serviço estará chegando em aproximadamente 1 hora.

📅 Data: {{data_agendamento}}
⏰ Período de chegada: {{periodo_chegada}}

Por favor, esteja disponível neste período para receber o prestador.

Após a conclusão da visita, clique no link abaixo para nos informar como foi o serviço:

{{link_acoes_servico}}

Protocolo: {{protocol}}

Atenciosamente,
Equipe KSS Assistência 360',
    1,
    1,
    NOW(),
    NOW()
FROM (SELECT 1) AS tmp
WHERE NOT EXISTS (
    SELECT 1 FROM whatsapp_templates 
    WHERE tipo = 'Lembrete Pré-Serviço' 
    AND padrao = 1
);

-- ============================================
-- 2. TEMPLATE: Confirmação de Serviço (Pós-Serviço)
-- ============================================
-- Atualizar se já existir
UPDATE whatsapp_templates 
SET corpo = 'Olá {{cliente_nome}}!

O horário agendado para o serviço foi finalizado. Por favor, nos informe como foi o atendimento clicando no link abaixo:

{{link_acoes_servico}}

📅 Data: {{data_agendamento}}
⏰ Horário: {{horario_agendamento}}

Protocolo: {{protocol}}

Atenciosamente,
Equipe KSS Assistência 360',
    ativo = 1,
    padrao = 1,
    updated_at = NOW()
WHERE tipo = 'Confirmação de Serviço' 
AND padrao = 1;

-- Criar se não existir
INSERT INTO whatsapp_templates (nome, tipo, corpo, ativo, padrao, created_at, updated_at)
SELECT 
    'Confirmação de Serviço - Padrão',
    'Confirmação de Serviço',
    'Olá {{cliente_nome}}!

O horário agendado para o serviço foi finalizado. Por favor, nos informe como foi o atendimento clicando no link abaixo:

{{link_acoes_servico}}

📅 Data: {{data_agendamento}}
⏰ Horário: {{horario_agendamento}}

Protocolo: {{protocol}}

Atenciosamente,
Equipe KSS Assistência 360',
    1,
    1,
    NOW(),
    NOW()
FROM (SELECT 1) AS tmp
WHERE NOT EXISTS (
    SELECT 1 FROM whatsapp_templates 
    WHERE tipo = 'Confirmação de Serviço' 
    AND padrao = 1
);

-- Verificar se foram criados
SELECT 
    'Verificação dos Templates Criados' AS resultado,
    tipo,
    nome,
    ativo,
    padrao,
    LENGTH(corpo) AS tamanho_corpo
FROM whatsapp_templates
WHERE tipo IN ('Lembrete Pré-Serviço', 'Confirmação de Serviço')
ORDER BY tipo;

