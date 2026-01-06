-- Adicionar campo para controlar se a notificação pré-serviço foi enviada
ALTER TABLE solicitacoes 
ADD COLUMN IF NOT EXISTS notificacao_pre_servico_enviada TINYINT(1) DEFAULT 0 
AFTER horario_confirmado_raw;

-- Adicionar campo para controlar se a notificação pós-serviço foi enviada
ALTER TABLE solicitacoes 
ADD COLUMN IF NOT EXISTS notificacao_pos_servico_enviada TINYINT(1) DEFAULT 0 
AFTER notificacao_pre_servico_enviada;

-- Criar índice para melhorar performance na busca
CREATE INDEX IF NOT EXISTS idx_notificacao_pre_servico 
ON solicitacoes(notificacao_pre_servico_enviada, status_id, horario_confirmado);

CREATE INDEX IF NOT EXISTS idx_notificacao_pos_servico 
ON solicitacoes(notificacao_pos_servico_enviada, status_id, horario_confirmado, data_agendamento, horario_agendamento);

