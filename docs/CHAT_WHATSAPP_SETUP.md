# Sistema de Chat WhatsApp - Configuração

## Visão Geral

O sistema de chat permite que o admin converse diretamente com o locatário via WhatsApp através da Evolution API. Cada solicitação possui um chat próprio onde o admin pode enviar e receber mensagens.

## Estrutura

### Banco de Dados

1. **Tabela `solicitacao_mensagens`**
   - Armazena todas as mensagens do chat
   - Campos principais:
     - `solicitacao_id`: ID da solicitação
     - `whatsapp_instance_id`: ID da instância WhatsApp usada
     - `numero_remetente` / `numero_destinatario`: Números envolvidos
     - `mensagem`: Texto da mensagem
     - `tipo`: ENVIADA (admin->locatário) ou RECEBIDA (locatário->admin)
     - `status`: ENVIANDO, ENVIADA, ENTREGUE, LIDA, ERRO

### Endpoints

1. **GET `/admin/chat/{solicitacao_id}/mensagens`**
   - Busca todas as mensagens de uma solicitação
   - Marca mensagens recebidas como lidas automaticamente

2. **POST `/admin/chat/{solicitacao_id}/enviar`**
   - Envia mensagem via WhatsApp
   - Parâmetros: `mensagem`, `whatsapp_instance_id`

3. **GET `/admin/chat/instancias`**
   - Lista instâncias WhatsApp disponíveis

4. **POST `/webhook/whatsapp`** (público)
   - Recebe mensagens da Evolution API
   - Processa eventos `messages.upsert` e `messages.update`

## Configuração do Webhook na Evolution API

### 1. Obter URL do Webhook

A URL do webhook deve ser:
```
https://seu-dominio.com.br/webhook/whatsapp
```

### 2. Configurar na Evolution API

Ao criar ou atualizar uma instância, configure o webhook:

```json
{
  "instanceName": "nome-da-instancia",
  "webhook": {
    "url": "https://seu-dominio.com.br/webhook/whatsapp",
    "enabled": true,
    "events": [
      "MESSAGES_UPSERT",
      "MESSAGES_UPDATE"
    ]
  }
}
```

### 3. Eventos Processados

- **MESSAGES_UPSERT**: Mensagens recebidas do locatário
- **MESSAGES_UPDATE**: Atualizações de status (entregue, lida)

## Interface no Kanban

O chat está disponível no offcanvas de detalhes da solicitação:

1. Abra uma solicitação no Kanban
2. Clique na aba "Chat WhatsApp"
3. Selecione a instância WhatsApp
4. Envie e receba mensagens

### Funcionalidades

- **Badge de notificações**: Mostra quantidade de mensagens não lidas
- **Polling automático**: Atualiza mensagens a cada 5 segundos
- **Status de entrega**: Mostra ✓ (entregue) ou ✓✓ (lida)
- **Histórico completo**: Todas as mensagens são salvas no banco

## Como Usar

1. **Enviar Mensagem**:
   - Selecione a instância WhatsApp
   - Digite a mensagem
   - Clique em "Enviar" ou pressione Enter

2. **Receber Mensagens**:
   - Mensagens são recebidas automaticamente via webhook
   - Aparecem no chat em tempo real (polling a cada 5s)
   - Badge mostra quantidade de não lidas

3. **Configurar Webhook**:
   - Acesse a instância WhatsApp no admin
   - Configure a URL do webhook
   - Ative os eventos necessários

## Troubleshooting

### Mensagens não aparecem

1. Verifique se o webhook está configurado na Evolution API
2. Verifique os logs em `storage/logs/app.log`
3. Confirme que a instância está CONECTADA
4. Verifique se o número do cliente está correto na solicitação

### Webhook não recebe mensagens

1. Verifique se a URL está acessível publicamente
2. Verifique se o SSL está configurado (HTTPS)
3. Verifique os logs do webhook no console
4. Teste a URL manualmente

### Erro ao enviar mensagem

1. Verifique se a instância está CONECTADA
2. Verifique se o número do destinatário está correto
3. Verifique os logs da Evolution API
4. Confirme que a API Key está correta

