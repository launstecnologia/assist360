-- Tabela para armazenar configurações de cron jobs
CREATE TABLE IF NOT EXISTS cron_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    descricao TEXT,
    classe_controller VARCHAR(255) NOT NULL,
    metodo VARCHAR(100) NOT NULL,
    frequencia_minutos INT DEFAULT 5,
    ativo TINYINT(1) DEFAULT 1,
    ultima_execucao DATETIME NULL,
    proxima_execucao DATETIME NULL,
    total_execucoes INT DEFAULT 0,
    total_erros INT DEFAULT 0,
    ultimo_erro TEXT NULL,
    configuracao JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela para histórico de execuções
CREATE TABLE IF NOT EXISTS cron_job_execucoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cron_job_id INT NOT NULL,
    status ENUM('sucesso', 'erro', 'aviso') DEFAULT 'sucesso',
    mensagem TEXT,
    dados_execucao JSON NULL,
    tempo_execucao_ms INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cron_job_id) REFERENCES cron_jobs(id) ON DELETE CASCADE,
    INDEX idx_cron_job_id (cron_job_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir cron job padrão para notificações pré-serviço
INSERT INTO cron_jobs (nome, descricao, classe_controller, metodo, frequencia_minutos, ativo, configuracao) 
VALUES (
    'Notificações Pré-Serviço',
    'Envia notificações WhatsApp 1 hora antes do prestador chegar',
    'SolicitacoesController',
    'processarNotificacoesPreServico',
    5,
    1,
    '{"janela_minutos": 60, "status_requerido": "Serviço Agendado"}'
) ON DUPLICATE KEY UPDATE descricao = VALUES(descricao);

-- Inserir cron job para notificações pós-serviço
INSERT INTO cron_jobs (nome, descricao, classe_controller, metodo, frequencia_minutos, ativo, configuracao) 
VALUES (
    'Notificações Pós-Serviço',
    'Envia notificações WhatsApp após o horário agendado para confirmar o serviço',
    'SolicitacoesController',
    'processarNotificacoesPosServico',
    5,
    1,
    '{"status_requerido": "Serviço Agendado", "minutos_apos_agendamento": 5}'
) ON DUPLICATE KEY UPDATE descricao = VALUES(descricao);

