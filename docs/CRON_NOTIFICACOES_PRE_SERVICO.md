# Configuração do Cron Job para Notificações Pré-Serviço

Este documento explica como configurar o cron job para enviar notificações 1 hora antes do prestador chegar.

## Opções de Configuração

### Opção 1: Via HTTP (Recomendado)

Use um serviço de cron job online ou configure no servidor para chamar a URL via HTTP.

**URL:**
```
https://seu-dominio.com/cron/notificacoes-pre-servico?token=SEU_TOKEN_SECRETO
```

**Configurar no crontab:**
```bash
# Executar a cada 5 minutos
*/5 * * * * wget -q -O - "https://seu-dominio.com/cron/notificacoes-pre-servico?token=SEU_TOKEN_SECRETO" > /dev/null 2>&1
```

Ou usando curl:
```bash
*/5 * * * * curl -s "https://seu-dominio.com/cron/notificacoes-pre-servico?token=SEU_TOKEN_SECRETO" > /dev/null 2>&1
```

### Opção 2: Via Script PHP Direto

Execute o script PHP diretamente no servidor.

**Configurar no crontab:**
```bash
# Executar a cada 5 minutos
*/5 * * * * /usr/bin/php /caminho/completo/para/kss/cron_notificacoes_pre_servico.php >> /var/log/kss_cron.log 2>&1
```

## Configuração do Token Secreto

1. Adicione no arquivo `.env`:
```env
CRON_SECRET_TOKEN=seu_token_secreto_aqui_2024
```

2. Ou use o token padrão: `kss_cron_secret_2024`

**⚠️ IMPORTANTE:** Altere o token padrão por segurança!

## Frequência Recomendada

- **Mínimo:** A cada 5 minutos (para garantir que não perca nenhuma notificação)
- **Ideal:** A cada 1-2 minutos (mais preciso, mas mais carga no servidor)

## Verificação

Para testar manualmente, acesse:
```
https://seu-dominio.com/cron/notificacoes-pre-servico?token=SEU_TOKEN_SECRETO
```

Você deve receber uma resposta JSON com:
```json
{
  "success": true,
  "message": "Notificações pré-serviço processadas",
  "enviadas": 0,
  "total_verificadas": 0,
  "timestamp": "2024-01-15 10:30:00"
}
```

## Logs

Os logs são salvos em:
- `storage/logs/whatsapp_evolution_api.log` - Logs de envio WhatsApp
- Logs do PHP (error_log) - Erros gerais

## Troubleshooting

### Notificações não estão sendo enviadas

1. Verifique se o cron está executando:
   ```bash
   tail -f /var/log/kss_cron.log
   ```

2. Verifique se há solicitações elegíveis:
   - Status: "Serviço Agendado"
   - `horario_confirmado = 1`
   - `notificacao_pre_servico_enviada = 0`
   - Dentro da janela de 1 hora antes

3. Verifique os logs de erro do PHP

### Token inválido

- Verifique se o token no `.env` corresponde ao token na URL do cron
- Certifique-se de que o `.env` está sendo carregado corretamente

