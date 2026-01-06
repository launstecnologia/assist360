-- Criar template de mensagem WhatsApp para "Não Qualificado"
-- Verificar se o template já existe antes de criar
INSERT INTO whatsapp_templates (nome, tipo, corpo, variaveis, ativo, padrao, created_at, updated_at)
SELECT 
    'Não Qualificado',
    'Não Qualificado',
    'Olá {{cliente_nome}},

Infelizmente, sua solicitação de assistência não se enquadra nos critérios estabelecidos.

{{observacao}}

Se tiver dúvidas, entre em contato conosco.

Atenciosamente,
Equipe Assistência 360°',
    '["cliente_nome", "observacao"]',
    1,
    1,
    NOW(),
    NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM whatsapp_templates WHERE tipo = 'Não Qualificado' AND ativo = 1
);

