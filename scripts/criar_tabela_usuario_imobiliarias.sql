-- Criar tabela de relacionamento entre usuários (operadores) e imobiliárias
-- Permite que o administrador escolha quais imobiliárias cada operador pode visualizar e trabalhar

CREATE TABLE IF NOT EXISTS usuario_imobiliarias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    imobiliaria_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_usuario_imobiliaria (usuario_id, imobiliaria_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_imobiliaria (imobiliaria_id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (imobiliaria_id) REFERENCES imobiliarias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

