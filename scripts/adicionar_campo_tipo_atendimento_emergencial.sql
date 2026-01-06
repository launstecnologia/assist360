-- Adicionar campo tipo_atendimento_emergencial na tabela solicitacoes
-- Este campo armazena se uma solicitação emergencial é para 120min ou agendada
ALTER TABLE `solicitacoes` 
ADD COLUMN IF NOT EXISTS `tipo_atendimento_emergencial` VARCHAR(20) NULL DEFAULT NULL 
COMMENT 'Tipo de atendimento emergencial: 120_minutos ou agendar' 
AFTER `is_emergencial_fora_horario`;

-- Adicionar índice para melhor performance
ALTER TABLE `solicitacoes`
ADD INDEX IF NOT EXISTS `idx_tipo_atendimento_emergencial` (`tipo_atendimento_emergencial`);

