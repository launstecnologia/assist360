-- Script para atualizar o template "Nova Solicitação" com as variáveis corretas
-- Execute este script no banco de dados para atualizar o template padrão

UPDATE whatsapp_templates 
SET corpo = '🏠 Nova Solicitação - Seguro Imobiliário KSS e {{imobiliaria_nome}}



📋 Nº Atendimento: {{protocol}}

🏷 Contrato: {{contrato_numero}}

🔖 Protocolo Seguradora: {{protocolo_seguradora}}

👤 Nome: {{cliente_nome}}

📄 CPF: {{cliente_cpf}}

📞 Telefone: {{cliente_telefone}}

🏢 Imobiliária: {{imobiliaria_nome}}

📍 Endereço: {{endereco_completo}}

📝 Descrição do Problema:
{{descricao_problema}}

📅 Agendamento: Horário à Confirmar

🔗 Acompanhe sua solicitação em:
{{link_rastreamento}}

🚫 Caso deseje cancelar sua solicitação, acesse:
{{link_cancelamento_solicitacao}}

⚠ OBSERVAÇÕES IMPORTANTES:

🏢 Condomínio: Se o serviço for realizado em apartamento ou condomínio, é obrigatório comunicar previamente a administração ou portaria sobre a visita técnica agendada.

👥 Responsável no Local: É obrigatória a presença de uma pessoa maior de 18 anos no local durante todo o período de execução do serviço para acompanhar e autorizar os trabalhos.

Caso não tiver ninguém no local, será considerado assistência perdida.

⏳ Próximos Passos: Aguarde a confirmação das opções de horários informadas para realização da assistência. Caso nenhuma das opções tenha disponibilidade, novas opções serão oferecidas.

---

Solicitação criada automaticamente pelo sistema

Não responda essa mensagem',
    updated_at = NOW()
WHERE tipo = 'Nova Solicitação' 
AND ativo = 1
AND padrao = 1
LIMIT 1;

-- Se não houver template padrão, atualiza qualquer template ativo do tipo "Nova Solicitação"
UPDATE whatsapp_templates 
SET corpo = '🏠 Nova Solicitação - Seguro Imobiliário KSS e {{imobiliaria_nome}}



📋 Nº Atendimento: {{protocol}}

🏷 Contrato: {{contrato_numero}}

🔖 Protocolo Seguradora: {{protocolo_seguradora}}

👤 Nome: {{cliente_nome}}

📄 CPF: {{cliente_cpf}}

📞 Telefone: {{cliente_telefone}}

🏢 Imobiliária: {{imobiliaria_nome}}

📍 Endereço: {{endereco_completo}}

📝 Descrição do Problema:
{{descricao_problema}}

📅 Agendamento: Horário à Confirmar

🔗 Acompanhe sua solicitação em:
{{link_rastreamento}}

🚫 Caso deseje cancelar sua solicitação, acesse:
{{link_cancelamento_solicitacao}}

⚠ OBSERVAÇÕES IMPORTANTES:

🏢 Condomínio: Se o serviço for realizado em apartamento ou condomínio, é obrigatório comunicar previamente a administração ou portaria sobre a visita técnica agendada.

👥 Responsável no Local: É obrigatória a presença de uma pessoa maior de 18 anos no local durante todo o período de execução do serviço para acompanhar e autorizar os trabalhos.

Caso não tiver ninguém no local, será considerado assistência perdida.

⏳ Próximos Passos: Aguarde a confirmação das opções de horários informadas para realização da assistência. Caso nenhuma das opções tenha disponibilidade, novas opções serão oferecidas.

---

Solicitação criada automaticamente pelo sistema

Não responda essa mensagem',
    updated_at = NOW()
WHERE tipo = 'Nova Solicitação' 
AND ativo = 1
AND (SELECT COUNT(*) FROM whatsapp_templates WHERE tipo = 'Nova Solicitação' AND ativo = 1 AND padrao = 1) = 0
LIMIT 1;

