# Configuração de URLs para Links de Token

Este documento explica como configurar a URL base para os links enviados nas mensagens WhatsApp (links de confirmação, cancelamento, rastreamento, etc.).

## Configuração

### Opção 1: Variável de Ambiente (Recomendado)

Adicione no arquivo `.env`:

```env
# URL base para links enviados nas mensagens WhatsApp
# Esta URL será usada para todos os links de token, confirmação, etc.
WHATSAPP_LINKS_BASE_URL=https://seu-dominio.com.br

# OU use a mesma URL da aplicação
APP_URL=https://seu-dominio.com.br
```

**Prioridade:**
1. `WHATSAPP_LINKS_BASE_URL` (específico para WhatsApp)
2. `APP_URL` (URL geral da aplicação)
3. `http://localhost` (fallback padrão)

### Opção 2: Configuração no código

Edite `app/Config/config.php`:

```php
'whatsapp' => [
    // ... outras configurações ...
    'links_base_url' => env('WHATSAPP_LINKS_BASE_URL', env('APP_URL', 'http://localhost')),
],
```

## Exemplos de Uso

### Desenvolvimento Local
```env
WHATSAPP_LINKS_BASE_URL=http://localhost:8000
```

### Desenvolvimento com Subpasta
```env
WHATSAPP_LINKS_BASE_URL=http://localhost/kss
```

### Produção
```env
WHATSAPP_LINKS_BASE_URL=https://app.kssseguros.com.br
```

### Produção com Subpasta
```env
WHATSAPP_LINKS_BASE_URL=https://dominio.com.br/kss
```

## Links Gerados

Os seguintes links são gerados automaticamente usando a URL base configurada:

### Link de Rastreamento
```
{URL_BASE}/locatario/solicitacao/{ID}
```
Exemplo: `https://app.kssseguros.com.br/locatario/solicitacao/18`

### Link de Confirmação de Horário
```
{URL_BASE}/confirmacao-horario?token={TOKEN}
```
Exemplo: `https://app.kssseguros.com.br/confirmacao-horario?token=abc123...`

### Link de Cancelamento
```
{URL_BASE}/cancelamento-horario?token={TOKEN}
```
Exemplo: `https://app.kssseguros.com.br/cancelamento-horario?token=abc123...`

### Link de Status
```
{URL_BASE}/status-servico?token={TOKEN}
```
Exemplo: `https://app.kssseguros.com.br/status-servico?token=abc123...`

## Variáveis Disponíveis nos Templates

Os templates WhatsApp podem usar as seguintes variáveis para links:

- `{{link_rastreamento}}` - Link para rastrear a solicitação
- `{{link_confirmacao}}` - Link para confirmar horário (com token)
- `{{link_cancelamento}}` - Link para cancelar horário (com token)
- `{{link_status}}` - Link para ver status do serviço (com token)

## Exemplo de Template

```text
🏠 Nova Solicitação - KSS Seguros

📋 Protocolo: {{protocol}}

🔗 Acompanhe sua solicitação:
{{link_rastreamento}}

✅ Confirme o horário:
{{link_confirmacao}}
```

## Verificação

Para verificar se a configuração está correta, você pode:

1. Enviar uma mensagem de teste via WhatsApp
2. Verificar o log em `storage/logs/whatsapp_evolution_api.log`
3. Verificar os links gerados na mensagem

## Importante

- ⚠️ **NÃO** inclua barra final (`/`) na URL base
- ✅ Use `https://` em produção para segurança
- ✅ Certifique-se de que a URL está acessível publicamente
- ✅ Teste os links após alterar a configuração

## Troubleshooting

### Links não funcionam

1. Verifique se a variável `WHATSAPP_LINKS_BASE_URL` está configurada no `.env`
2. Verifique se a URL está acessível (sem firewall bloqueando)
3. Verifique os logs em `storage/logs/whatsapp_evolution_api.log`
4. Certifique-se de que não há barra final na URL

### Links apontam para localhost

1. Verifique se `WHATSAPP_LINKS_BASE_URL` está configurado no `.env`
2. Verifique se o arquivo `.env` está sendo carregado corretamente
3. Limpe o cache se houver algum sistema de cache

### Links têm caminho duplicado

1. Verifique se a URL base não inclui o caminho completo
2. Use apenas o domínio: `https://dominio.com.br`
3. Não inclua subpastas na URL base se já estiverem nas rotas

