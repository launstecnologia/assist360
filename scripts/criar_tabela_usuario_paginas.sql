-- Criar tabela de relacionamento entre usuários (operadores) e páginas/abas permitidas
-- Permite que o administrador escolha quais abas cada operador pode visualizar

CREATE TABLE IF NOT EXISTS usuario_paginas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    pagina VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_usuario_pagina (usuario_id, pagina),
    INDEX idx_usuario (usuario_id),
    INDEX idx_pagina (pagina),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Páginas disponíveis no sistema:
-- dashboard, relatorios, kanban, upload
-- solicitacoes-manuais, templates-whatsapp, whatsapp-instances

