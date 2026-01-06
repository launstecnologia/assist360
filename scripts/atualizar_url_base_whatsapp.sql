-- Atualizar URL base do WhatsApp para o domínio correto
-- Esta URL será usada para gerar os links nas mensagens WhatsApp

UPDATE configuracoes 
SET valor = 'https://assist360.ksssolucoes.com.br',
    updated_at = CURRENT_TIMESTAMP
WHERE chave = 'whatsapp_links_base_url';

-- Se não existir, criar
INSERT INTO configuracoes (chave, valor, tipo, descricao) 
VALUES ('whatsapp_links_base_url', 'https://assist360.ksssolucoes.com.br', 'string', 'URL base para links enviados nas mensagens WhatsApp (links de token, confirmação, cancelamento, etc.). Exemplo: https://assist360.ksssolucoes.com.br')
ON DUPLICATE KEY UPDATE 
    valor = 'https://assist360.ksssolucoes.com.br',
    updated_at = CURRENT_TIMESTAMP;

