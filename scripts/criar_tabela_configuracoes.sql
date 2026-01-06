-- Criar tabela de configurações
CREATE TABLE IF NOT EXISTS configuracoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(100) NOT NULL UNIQUE COMMENT 'Chave única da configuração',
    valor TEXT NULL COMMENT 'Valor da configuração (pode ser JSON)',
    tipo ENUM('string', 'number', 'boolean', 'json', 'time') NOT NULL DEFAULT 'string' COMMENT 'Tipo do valor',
    descricao TEXT NULL COMMENT 'Descrição da configuração',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_chave (chave),
    INDEX idx_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Configurações gerais do sistema';

-- Inserir configurações padrão de emergência
INSERT INTO configuracoes (chave, valor, tipo, descricao) VALUES
('telefone_emergencia', '', 'string', 'Telefone de emergência 0800'),
('horario_comercial_inicio', '08:00', 'time', 'Horário de início do atendimento comercial (formato HH:MM)'),
('horario_comercial_fim', '17:30', 'time', 'Horário de fim do atendimento comercial (formato HH:MM)'),
('dias_semana_comerciais', '[1,2,3,4,5]', 'json', 'Dias da semana considerados comerciais (1=Segunda, 7=Domingo)')
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

