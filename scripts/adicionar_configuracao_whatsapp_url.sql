-- Script para adicionar configuração da URL base do WhatsApp
-- Esta URL será usada para gerar os links nas mensagens WhatsApp

INSERT INTO configuracoes (chave, valor, tipo, descricao) VALUES
('whatsapp_links_base_url', 'https://kss.launs.com.br', 'string', 'URL base para links enviados nas mensagens WhatsApp (links de token, confirmação, cancelamento, etc.). Exemplo: https://seu-dominio.com.br')
ON DUPLICATE KEY UPDATE 
    updated_at = CURRENT_TIMESTAMP;

