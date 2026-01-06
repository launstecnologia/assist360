-- Script para atualizar os templates de mensagens WhatsApp
-- Baseado nos novos formatos fornecidos

-- ============================================
-- 1. NOVA SOLICITAÇÃO
-- ============================================
UPDATE whatsapp_templates 
SET corpo = '🏠 Temos uma atualização sobre a assistência solicitada



Oi, sou a Assistente Virtual da KSS Assistência 360°👋

Recebi a sua solicitação do serviço de {{servico_tipo}} (problema reclamado) que será realizado em {{endereco_completo}}, e já estamos buscando um prestador.

Aqui está o n° do seu atendimento KSS👇

{{protocol}}

📅 Agendamento: horário a confirmar.

Você será avisado assim que houver disponibilidade para atendimento.

⚠ Acompanhe o status, reagende ou cancele a sua solicitação pelo app KSS.

📎 Mensagem gerada automaticamente pelo sistema, favor não responda.',
    updated_at = NOW()
WHERE tipo = 'Nova Solicitação' 
AND ativo = 1
ORDER BY padrao DESC, created_at DESC
LIMIT 1;

-- Se não houver template padrão, atualiza qualquer template ativo
UPDATE whatsapp_templates 
SET corpo = '🏠 Temos uma atualização sobre a assistência solicitada



Oi, sou a Assistente Virtual da KSS Assistência 360°👋

Recebi a sua solicitação do serviço de {{servico_tipo}} (problema reclamado) que será realizado em {{endereco_completo}}, e já estamos buscando um prestador.

Aqui está o n° do seu atendimento KSS👇

{{protocol}}

📅 Agendamento: horário a confirmar.

Você será avisado assim que houver disponibilidade para atendimento.

⚠ Acompanhe o status, reagende ou cancele a sua solicitação pelo app KSS.

📎 Mensagem gerada automaticamente pelo sistema, favor não responda.',
    updated_at = NOW()
WHERE tipo = 'Nova Solicitação' 
AND ativo = 1
AND (SELECT COUNT(*) FROM whatsapp_templates WHERE tipo = 'Nova Solicitação' AND ativo = 1 AND padrao = 1) = 0
LIMIT 1;

-- ============================================
-- 2. HORÁRIO CONFIRMADO
-- ============================================
UPDATE whatsapp_templates 
SET corpo = '🏠 Temos uma atualização sobre a assistência solicitada

Oi, sou a Assistente Virtual da KSS Assistência 360°👋

O horário da sua solicitação foi definido!🙂

Data: {{data_agendamento}}

Horário: {{horario_agendamento}}

Prestador: {{prestador_nome}}

Atendimento KSS: {{protocol}}

Protocolo Assistência: {{protocolo_seguradora}}

⚠ Acompanhe o status, reagende ou cancele a sua solicitação pelo app KSS.

📎 Mensagem gerada automaticamente pelo sistema, favor não responda.',
    updated_at = NOW()
WHERE tipo = 'Horário Confirmado' 
AND ativo = 1
ORDER BY padrao DESC, created_at DESC
LIMIT 1;

-- Se não houver template padrão, atualiza qualquer template ativo
UPDATE whatsapp_templates 
SET corpo = '🏠 Temos uma atualização sobre a assistência solicitada

Oi, sou a Assistente Virtual da KSS Assistência 360°👋

O horário da sua solicitação foi definido!🙂

Data: {{data_agendamento}}

Horário: {{horario_agendamento}}

Prestador: {{prestador_nome}}

Atendimento KSS: {{protocol}}

Protocolo Assistência: {{protocolo_seguradora}}

⚠ Acompanhe o status, reagende ou cancele a sua solicitação pelo app KSS.

📎 Mensagem gerada automaticamente pelo sistema, favor não responda.',
    updated_at = NOW()
WHERE tipo = 'Horário Confirmado' 
AND ativo = 1
AND (SELECT COUNT(*) FROM whatsapp_templates WHERE tipo = 'Horário Confirmado' AND ativo = 1 AND padrao = 1) = 0
LIMIT 1;

-- ============================================
-- 3. PÓS-SERVIÇO (Confirmação de Serviço)
-- ============================================
UPDATE whatsapp_templates 
SET corpo = '🏠 Temos uma atualização sobre a assistência solicitada

Oi, aqui é a Assistente Virtual da KSS Assistência 360° 👋

Queremos saber como foi a sua solicitação (concluída, prestador não compareceu, comprar peças, etc.). 

Por favor, acesse o link: {{link_acoes_servico}}

⚠Caso a sua assistência tenha sido concluída, por gentileza, avalie o nosso atendimento. A sua opinião é muito importante para podermos melhorar sempre.

Link avaliação: {{link_avaliacao}}

📎 Mensagem gerada automaticamente pelo sistema, favor não responda.',
    updated_at = NOW()
WHERE tipo = 'Confirmação de Serviço' 
AND ativo = 1
ORDER BY padrao DESC, created_at DESC
LIMIT 1;

-- Se não houver template padrão, atualiza qualquer template ativo
UPDATE whatsapp_templates 
SET corpo = '🏠 Temos uma atualização sobre a assistência solicitada

Oi, aqui é a Assistente Virtual da KSS Assistência 360° 👋

Queremos saber como foi a sua solicitação (concluída, prestador não compareceu, comprar peças, etc.). 

Por favor, acesse o link: {{link_acoes_servico}}

⚠Caso a sua assistência tenha sido concluída, por gentileza, avalie o nosso atendimento. A sua opinião é muito importante para podermos melhorar sempre.

Link avaliação: {{link_avaliacao}}

📎 Mensagem gerada automaticamente pelo sistema, favor não responda.',
    updated_at = NOW()
WHERE tipo = 'Confirmação de Serviço' 
AND ativo = 1
AND (SELECT COUNT(*) FROM whatsapp_templates WHERE tipo = 'Confirmação de Serviço' AND ativo = 1 AND padrao = 1) = 0
LIMIT 1;

