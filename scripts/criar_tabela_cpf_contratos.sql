-- Tabela para armazenar CPF e número do contrato por imobiliária
-- Usada para verificar se uma solicitação manual deve ir para o kanban ou para admin
CREATE TABLE IF NOT EXISTS locatarios_contratos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    imobiliaria_id INT NOT NULL,
    cpf VARCHAR(14) NOT NULL,
    numero_contrato VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (imobiliaria_id) REFERENCES imobiliarias(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cpf_contrato_imobiliaria (imobiliaria_id, cpf, numero_contrato),
    INDEX idx_cpf_imobiliaria (imobiliaria_id, cpf)
);

