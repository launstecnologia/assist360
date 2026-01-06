-- Tabela para armazenar URLs encurtadas
CREATE TABLE IF NOT EXISTS urls_encurtadas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) NOT NULL UNIQUE,
    solicitacao_id INT NOT NULL,
    tipo VARCHAR(50) NOT NULL DEFAULT 'rastreamento', -- rastreamento, cancelamento, etc
    url_original TEXT NOT NULL,
    url_encurtada VARCHAR(255) NOT NULL,
    acessos INT DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_codigo (codigo),
    INDEX idx_solicitacao (solicitacao_id),
    INDEX idx_tipo (tipo),
    FOREIGN KEY (solicitacao_id) REFERENCES solicitacoes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

