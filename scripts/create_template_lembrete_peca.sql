-- Criar template "Lembrete Peça" com link para compra de peça
INSERT INTO whatsapp_templates (nome, tipo, corpo, variaveis, ativo, padrao, created_at, updated_at)
VALUES (
    'Lembrete Peça - Padrão',
    'lembrete_peca',
    'Olá {{cliente_nome}}!

🔔 Lembrete: Compra de Peça

Você ainda não informou a compra da peça necessária para o serviço.

📋 Protocolo: {{protocol}}
📅 Prazo para compra: {{data_limite}}
⏰ Dias restantes: {{dias_restantes}}

Por favor, clique no link abaixo para informar que você comprou a peça e selecionar novos horários para o atendimento:

{{link_compra_peca}}

Após informar a compra, nossa equipe entrará em contato para agendar o serviço.

Atenciosamente,
Equipe KSS Assistência 360',
    JSON_ARRAY('cliente_nome', 'protocol', 'data_limite', 'dias_restantes', 'link_compra_peca'),
    1,
    1,
    NOW(),
    NOW()
) ON DUPLICATE KEY UPDATE 
    corpo = VALUES(corpo),
    variaveis = VALUES(variaveis),
    ativo = 1,
    padrao = 1,
    updated_at = NOW();

