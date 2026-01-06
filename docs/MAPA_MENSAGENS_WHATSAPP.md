# 📱 Mapa de Mensagens WhatsApp - Sistema KSS

## 📋 Visão Geral

Este documento mapeia **todos os templates de mensagens WhatsApp** e explica **quando cada mensagem é enviada** no sistema.

---

## 🎯 Templates Disponíveis

O sistema possui **5 tipos de templates** configurados no banco de dados:

1. **Nova Solicitação**
2. **Horário Confirmado**
3. **Horário Sugerido**
4. **Confirmação de Serviço**
5. **Atualização de Status**

---

## 📨 1. NOVA SOLICITAÇÃO

### 📝 Quando é enviada:
- **Evento:** Quando uma nova solicitação é criada
- **Método:** `SolicitacoesController::criarSolicitacao()`
- **Linha:** `app/Controllers/SolicitacoesController.php:381`
- **Momento:** Imediatamente após criar a solicitação no banco

### 🔍 Contexto:
```php
// Após criar a solicitação com sucesso
$solicitacaoId = $this->solicitacaoModel->create($data);
$this->enviarNotificacaoWhatsApp($solicitacaoId, 'Nova Solicitação');
```

### 📋 Variáveis disponíveis:
- `{{protocol}}` - Número do protocolo
- `{{contrato_numero}}` - Número do contrato
- `{{protocolo_seguradora}}` - Protocolo da seguradora
- `{{cliente_nome}}` - Nome do cliente
- `{{cliente_cpf}}` - CPF do cliente
- `{{cliente_telefone}}` - Telefone do cliente
- `{{imobiliaria_nome}}` - Nome da imobiliária
- `{{endereco_completo}}` - Endereço formatado
- `{{descricao_problema}}` - Descrição do problema
- `{{link_rastreamento}}` - Link para rastrear solicitação

### 🎯 Objetivo:
Informar o cliente que sua solicitação foi recebida e está sendo processada.

---

## ✅ 2. HORÁRIO CONFIRMADO

### 📝 Quando é enviada:
- **Evento:** Quando o operador confirma um horário de atendimento
- **Método:** `SolicitacoesController::confirmarHorario()`
- **Linhas:** 
  - `app/Controllers/SolicitacoesController.php:915`
  - `app/Controllers/SolicitacoesController.php:1003`
  - `app/Controllers/SolicitacoesController.php:1694`
- **Momento:** Imediatamente após confirmar um horário no sistema

### 🔍 Contexto:
```php
// Após confirmar horário
$this->enviarNotificacaoWhatsApp($id, 'Horário Confirmado', [
    'data_agendamento' => date('d/m/Y', $timestamp),
    'horario_agendamento' => date('H:i', $timestamp) . '-' . date('H:i', strtotime('+3 hours', $timestamp)),
    'horario_servico' => $horarioCompleto
]);
```

### 📋 Variáveis disponíveis:
- `{{protocol}}` - Número do protocolo
- `{{contrato_numero}}` - Número do contrato
- `{{cliente_nome}}` - Nome do cliente
- `{{endereco_completo}}` - Endereço formatado
- `{{data_agendamento}}` - Data do agendamento (dd/mm/yyyy)
- `{{horario_agendamento}}` - Horário do agendamento (ex: 14:00-17:00)
- `{{descricao_problema}}` - Descrição do problema
- `{{link_cancelamento}}` - Link para cancelar (com token de 48h)

### 🎯 Objetivo:
Confirmar o horário agendado e permitir cancelamento via link.

### 🔑 Token de Confirmação:
- **Gera token único** válido por 48 horas
- Token armazenado em `schedule_confirmation_tokens`
- Usado no link de cancelamento

---

## 📅 3. HORÁRIO SUGERIDO

### 📝 Quando é enviada:
- **Evento:** Quando o operador solicita novos horários (horários originais indisponíveis)
- **Método:** `SolicitacoesController::solicitarNovosHorarios()`
- **Linha:** `app/Controllers/SolicitacoesController.php:1280`
- **Momento:** Quando nenhum dos horários propostos pelo cliente está disponível

### 🔍 Contexto:
```php
// Quando horários não estão disponíveis
$this->enviarNotificacaoWhatsApp($id, 'Horário Sugerido', [
    'data_agendamento' => 'A definir',
    'horario_agendamento' => 'Aguardando novas opções'
]);
```

### 📋 Variáveis disponíveis:
- `{{protocol}}` - Número do protocolo
- `{{contrato_numero}}` - Número do contrato
- `{{cliente_nome}}` - Nome do cliente
- `{{endereco_completo}}` - Endereço formatado
- `{{data_agendamento}}` - Data sugerida (pode ser "A definir")
- `{{horario_agendamento}}` - Horário sugerido (pode ser "Aguardando novas opções")
- `{{descricao_problema}}` - Descrição do problema
- `{{link_confirmacao}}` - Link para confirmar/reagendar (com token de 48h)

### 🎯 Objetivo:
Solicitar que o cliente escolha novos horários ou confirme uma sugestão.

### 🔑 Token de Confirmação:
- **Gera token único** válido por 48 horas
- Token armazenado em `schedule_confirmation_tokens`
- Usado no link de confirmação/reagendamento

---

## ✅ 4. CONFIRMAÇÃO DE SERVIÇO

### 📝 Quando é enviada:
- **Evento:** Quando o operador registra a confirmação do serviço realizado
- **Método:** `SolicitacoesController::confirmarServico()`
- **Linha:** `app/Controllers/SolicitacoesController.php:1354`
- **Momento:** Após o operador marcar se o serviço foi realizado, prestador compareceu, etc.

### 🔍 Contexto:
```php
// Após confirmar serviço
$this->enviarNotificacaoWhatsApp($id, 'Confirmação de Serviço', [
    'horario_servico' => date('d/m/Y H:i', strtotime($solicitacao['data_agendamento']))
]);
```

### 📋 Variáveis disponíveis:
- `{{protocol}}` - Número do protocolo
- `{{contrato_numero}}` - Número do contrato
- `{{cliente_nome}}` - Nome do cliente
- `{{horario_servico}}` - Horário do serviço (formato: dd/mm/yyyy HH:mm)
- `{{descricao_problema}}` - Descrição do problema
- `{{link_status}}` - Link para informar status do serviço (com token de 48h)

### 🎯 Objetivo:
Solicitar feedback do cliente sobre o serviço realizado.

### 🔑 Token de Confirmação:
- **Gera token único** válido por 48 horas
- Token armazenado em `schedule_confirmation_tokens`
- Usado no link de status do serviço

---

## 🔄 5. ATUALIZAÇÃO DE STATUS

### 📝 Quando é enviada:
- **Evento:** Quando o status da solicitação é alterado no Kanban
- **Método:** `SolicitacoesController::updateStatus()`
- **Linha:** `app/Controllers/SolicitacoesController.php:268`
- **Momento:** Sempre que o operador arrasta um card para outra coluna no Kanban

### 🔍 Contexto:
```php
// Após atualizar status
$this->enviarNotificacaoWhatsApp($id, 'Atualização de Status', [
    'status_atual' => $status['nome'] ?? 'Atualizado'
]);
```

### 📋 Variáveis disponíveis:
- `{{protocol}}` - Número do protocolo
- `{{contrato_numero}}` - Número do contrato
- `{{protocolo_seguradora}}` - Protocolo da seguradora
- `{{cliente_nome}}` - Nome do cliente
- `{{status_atual}}` - **Status atual** (ex: "Serviço Agendado", "Buscando Prestador")
- `{{descricao_problema}}` - Descrição do problema
- `{{prestador_nome}}` - Nome do prestador (se disponível)
- `{{data_agendamento}}` - Data do agendamento (se disponível)
- `{{horario_agendamento}}` - Horário do agendamento (se disponível)
- `{{link_rastreamento}}` - Link para rastrear solicitação

### 🎯 Objetivo:
Informar o cliente sobre mudanças no status da sua solicitação.

---

## 🚨 6. MENSAGEM "AGENDADO" (Tipo Especial)

### 📝 Quando é enviada:
- **Evento:** Quando datas são confirmadas via Mawdy
- **Método:** `SolicitacoesController::confirmarDatas()`
- **Linha:** `app/Controllers/SolicitacoesController.php:425`
- **Momento:** Quando o operador confirma datas retornadas pela API Mawdy

### 🔍 Contexto:
```php
// Após confirmar datas do Mawdy
$this->enviarNotificacaoWhatsApp($solicitacaoId, 'agendado');
```

### ⚠️ Observação:
Este tipo de mensagem (`'agendado'`) **não tem template padrão** no banco de dados. O sistema tentará buscar um template, mas se não encontrar, não enviará a mensagem.

**Recomendação:** Criar um template do tipo "agendado" ou usar "Horário Confirmado" para este caso.

---

## 🔔 7. MENSAGEM "CONCLUÍDO" (Tipo Especial)

### 📝 Quando é enviada:
- **Evento:** Quando o cliente confirma atendimento via token
- **Método:** `SolicitacoesController::confirmarAtendimento()`
- **Linha:** `app/Controllers/SolicitacoesController.php:492`
- **Momento:** Após o cliente confirmar o atendimento pelo link do WhatsApp

### 🔍 Contexto:
```php
// Após confirmar atendimento via token
$this->enviarNotificacaoWhatsApp($solicitacao['id'], 'concluido');
```

### ⚠️ Observação:
Este tipo de mensagem (`'concluido'`) **não tem template padrão** no banco de dados. O sistema tentará buscar um template, mas se não encontrar, não enviará a mensagem.

**Recomendação:** Criar um template do tipo "concluido" ou usar "Confirmação de Serviço" para este caso.

---

## 📢 8. LEMBRETE DE PEÇA

### 📝 Quando é enviada:
- **Evento:** Lembrete automático para solicitações aguardando peça
- **Método:** `SolicitacoesController::enviarLembretes()`
- **Linha:** `app/Controllers/SolicitacoesController.php:540`
- **Momento:** A cada 2 dias para solicitações com status "Aguardando Peça"

### 🔍 Contexto:
```php
// Para cada solicitação que precisa de lembrete
foreach ($solicitacoes as $solicitacao) {
    $this->enviarNotificacaoWhatsApp($solicitacao['id'], 'lembrete_peca');
    $this->solicitacaoModel->atualizarLembrete($solicitacao['id']);
}
```

### ⚠️ Observação:
Este tipo de mensagem (`'lembrete_peca'`) **não tem template padrão** no banco de dados. O sistema tentará buscar um template, mas se não encontrar, não enviará a mensagem.

**Recomendação:** Criar um template do tipo "lembrete_peca" para este caso.

---

## 📊 Resumo dos Templates

| Tipo de Mensagem | Template Existe? | Quando é Enviada | Token Gerado? |
|-----------------|------------------|------------------|---------------|
| **Nova Solicitação** | ✅ Sim | Ao criar solicitação | ❌ Não |
| **Horário Confirmado** | ✅ Sim | Ao confirmar horário | ✅ Sim (48h) |
| **Horário Sugerido** | ✅ Sim | Ao solicitar novos horários | ✅ Sim (48h) |
| **Confirmação de Serviço** | ✅ Sim | Ao confirmar serviço | ✅ Sim (48h) |
| **Atualização de Status** | ✅ Sim | Ao mudar status no Kanban | ❌ Não |
| **agendado** | ❌ Não | Ao confirmar datas Mawdy | ❌ Não |
| **concluido** | ❌ Não | Ao confirmar atendimento | ❌ Não |
| **lembrete_peca** | ❌ Não | Lembrete a cada 2 dias | ❌ Não |

---

## 🔧 Como o Sistema Funciona

### 1. Busca do Template:
```php
// WhatsAppService busca template ativo por tipo
$template = $this->getTemplate($messageType);

// SQL: SELECT * FROM whatsapp_templates 
// WHERE tipo = ? AND ativo = 1 
// ORDER BY padrao DESC, created_at DESC LIMIT 1
```

### 2. Substituição de Variáveis:
```php
// Todas as variáveis {{variavel}} são substituídas pelos valores reais
$message = $this->replaceVariables($template['corpo'], $variables);
```

### 3. Geração de Token (quando necessário):
```php
// Para tipos específicos, gera token de confirmação
$tokenTypes = ['Horário Confirmado', 'Horário Sugerido', 'Confirmação de Serviço'];
if (in_array($messageType, $tokenTypes)) {
    $token = $this->createTokenIfNeeded(...);
}
```

### 4. Envio para Evolution API:
```php
// Envia mensagem formatada para Evolution API
$result = $this->sendToEvolutionAPI($whatsappNumber, $message);
```

---

## 📝 Recomendações

### Templates Faltando:
1. **Criar template "agendado"** - Para quando datas são confirmadas via Mawdy
2. **Criar template "concluido"** - Para quando cliente confirma atendimento
3. **Criar template "lembrete_peca"** - Para lembretes de peça a cada 2 dias

### Melhorias Sugeridas:
1. Adicionar logs detalhados de cada envio
2. Implementar retry automático em caso de falha
3. Adicionar métricas de envio bem-sucedido/falha
4. Permitir agendamento de mensagens futuras

---

## 🎯 Fluxo Completo de Uma Solicitação

```
1. Cliente cria solicitação
   └─> Envia: "Nova Solicitação"

2. Operador muda status no Kanban
   └─> Envia: "Atualização de Status"

3. Operador confirma horário
   └─> Envia: "Horário Confirmado" (com token de cancelamento)

4. Se horários indisponíveis
   └─> Envia: "Horário Sugerido" (com token de confirmação)

5. Operador confirma serviço
   └─> Envia: "Confirmação de Serviço" (com token de status)

6. Se aguardando peça (a cada 2 dias)
   └─> Envia: "lembrete_peca" (se template existir)
```

---

**Última atualização:** 2024
**Arquivo:** `MAPA_MENSAGENS_WHATSAPP.md`

