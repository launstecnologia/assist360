-- Criar template "Lembrete Pré-Serviço"
INSERT INTO whatsapp_templates (nome, tipo, corpo, ativo, padrao, created_at, updated_at)
VALUES (
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
) ON DUPLICATE KEY UPDATE 
    corpo = VALUES(corpo),
    ativo = 1,
    padrao = 1,
    updated_at = NOW();

