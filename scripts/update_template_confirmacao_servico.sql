-- Atualizar template "Confirmação de Serviço" para incluir link de ações
UPDATE whatsapp_templates 
SET corpo = 'Olá {{cliente_nome}}!

O horário agendado para o serviço já passou. Por favor, nos informe como foi o atendimento clicando no link abaixo:

{{link_acoes_servico}}

📅 Data: {{data_agendamento}}
⏰ Horário: {{horario_agendamento}}

Protocolo: {{protocol}}

Atenciosamente,
Equipe KSS Assistência 360',
    updated_at = NOW()
WHERE tipo = 'Confirmação de Serviço' 
AND padrao = 1;

-- Se não existir, criar
INSERT INTO whatsapp_templates (nome, tipo, corpo, ativo, padrao, created_at, updated_at)
SELECT 
    'Confirmação de Serviço - Padrão',
    'Confirmação de Serviço',
    'Olá {{cliente_nome}}!

O horário agendado para o serviço já passou. Por favor, nos informe como foi o atendimento clicando no link abaixo:

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

