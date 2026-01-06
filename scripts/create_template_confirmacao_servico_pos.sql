-- Criar/Atualizar template "Confirmação de Serviço" para notificação pós-serviço
-- Este template é usado quando o horário do agendamento finaliza

-- Atualizar template existente se houver
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

-- Se não existir, criar novo template
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

