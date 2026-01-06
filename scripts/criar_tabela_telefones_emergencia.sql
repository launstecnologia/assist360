-- Criar tabela de telefones de emergência
CREATE TABLE IF NOT EXISTS telefones_emergencia (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    numero VARCHAR(20) NOT NULL COMMENT 'Número do telefone (ex: 0800 123 4567)',
    descricao TEXT NULL COMMENT 'Descrição opcional sobre quando usar este telefone',
    is_ativo TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Se o telefone está ativo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ativo (is_ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Telefones 0800 para emergências fora do horário comercial';

-- Adicionar coluna is_emergencial na tabela subcategorias
ALTER TABLE subcategorias 
ADD COLUMN IF NOT EXISTS is_emergencial TINYINT(1) NOT NULL DEFAULT 0 
COMMENT 'Se esta subcategoria é emergencial (não precisa agendamento)' 
AFTER ordem;

-- Adicionar coluna is_emergencial_fora_horario na tabela solicitacoes
ALTER TABLE solicitacoes 
ADD COLUMN IF NOT EXISTS is_emergencial_fora_horario TINYINT(1) NOT NULL DEFAULT 0 
COMMENT 'Se a solicitação foi criada como emergência fora do horário comercial' 
AFTER prioridade;

