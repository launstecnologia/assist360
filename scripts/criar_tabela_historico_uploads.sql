-- Tabela para armazenar histórico de uploads de planilhas CSV
-- Registra informações sobre cada arquivo CSV importado por imobiliária
CREATE TABLE IF NOT EXISTS historico_uploads (
    id INT PRIMARY KEY AUTO_INCREMENT,
    imobiliaria_id INT NOT NULL,
    nome_arquivo VARCHAR(255) NOT NULL,
    tamanho_arquivo INT NOT NULL COMMENT 'Tamanho do arquivo em bytes',
    usuario_id INT NOT NULL,
    total_registros INT NOT NULL DEFAULT 0 COMMENT 'Total de registros processados',
    registros_sucesso INT NOT NULL DEFAULT 0 COMMENT 'Registros processados com sucesso',
    registros_erro INT NOT NULL DEFAULT 0 COMMENT 'Registros com erro',
    detalhes_erros TEXT NULL COMMENT 'JSON com detalhes dos erros encontrados',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (imobiliaria_id) REFERENCES imobiliarias(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_imobiliaria (imobiliaria_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Histórico de uploads de planilhas CSV por imobiliária';

