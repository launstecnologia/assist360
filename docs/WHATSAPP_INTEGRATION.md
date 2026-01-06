# Integração WhatsApp - Documentação Completa

Sistema de notificações WhatsApp com **envio direto/síncrono** via Evolution API para o KSS Assistência 360.

---

## Sumário

1. [Visão Geral](#visão-geral)
2. [Arquitetura](#arquitetura)
3. [Instalação](#instalação)
4. [Configuração](#configuração)
5. [Estrutura do Banco de Dados](#estrutura-do-banco-de-dados)
6. [Templates de Mensagens](#templates-de-mensagens)
7. [Variáveis Disponíveis](#variáveis-disponíveis)
8. [Gatilhos de Notificação](#gatilhos-de-notificação)
9. [Como Usar](#como-usar)
10. [Troubleshooting](#troubleshooting)

---

## Visão Geral

O sistema de WhatsApp permite o envio automatizado de notificações aos clientes em diferentes etapas do ciclo de vida de uma solicitação:

### Funcionalidades:
- Envio síncrono/direto de mensagens (resposta imediata)
- Templates customizáveis armazenados no banco de dados
- Substituição de variáveis dinâmicas (ex: `{{cliente_nome}}`, `{{protocol}}`)
- Tokens de confirmação com expiração de 48h
- Formatação automática de números WhatsApp
- Logs de erros para troubleshooting

### Tipos de Notificações:
1. **Nova Solicitação** - Enviada ao criar uma solicitação
2. **Horário Confirmado** - Quando um horário é confirmado
3. **Horário Sugerido** - Quando o operador solicita novos horários
4. **Confirmação de Serviço** - Para confirmar realização do serviço
5. **Atualização de Status** - Sempre que o status da solicitação muda

---

## Arquitetura

```
┌──────────────────┐
│  Sistema KSS     │
│  (PHP/MySQL)     │
└────────┬─────────┘
         │
         │ 1. Evento ocorre (nova solicitação, status change, etc)
         ▼
┌──────────────────────┐
│ WhatsAppService.php  │
│ - sendMessage()      │
└────────┬─────────────┘
         │
         │ 2. Busca template do banco
         ▼
┌─────────────────────┐
│ whatsapp_templates  │
│ (MySQL)             │
└────────┬────────────┘
         │
         │ 3. Substitui variáveis
         ▼
┌──────────────────────┐
│ Formata número       │
│ Sanitiza texto       │
└────────┬─────────────┘
         │
         │ 4. POST via cURL
         ▼
┌─────────────────────┐
│  Evolution API      │
│  (WhatsApp Gateway) │
└─────────────────────┘
```

**Modo de Operação:** ENVIO DIRETO (síncrono)
- Mensagens são enviadas imediatamente quando o evento ocorre
- Erros são logados mas não bloqueiam a operação principal
- Sem retry automático (se falhar, apenas registra no error_log)

---

## Instalação

### 1. Instalação Automática (Recomendado)

Acesse via navegador:
```
http://localhost:8000/install-whatsapp.php
```

Clique em "Iniciar Instalação" e aguarde a conclusão.

### 2. Instalação Manual

Execute os scripts SQL na ordem:

```bash
# 1. Criar tabelas
mysql -u launs_kss -p -h 186.209.113.149 launs_kss < database_whatsapp_infrastructure.sql

# 2. Popular templates
mysql -u launs_kss -p -h 186.209.113.149 launs_kss < database_whatsapp_templates.sql
```

---

## Configuração

### 1. Variáveis de Ambiente

Edite o arquivo `.env` e adicione:

```bash
# Configurações do WhatsApp (Evolution API)
WHATSAPP_ENABLED=true
WHATSAPP_API_URL=https://evolutionapi.launs.com.br
WHATSAPP_INSTANCE=notification_launs_02
WHATSAPP_TOKEN=seu-token-evolution-aqui
WHATSAPP_API_KEY=seu-api-key-aqui
```

**Importante:**
- `WHATSAPP_API_URL`: URL base da Evolution API (sem `/` no final)
- `WHATSAPP_INSTANCE`: Nome da instância no Evolution
- `WHATSAPP_TOKEN`: Token da instância
- `WHATSAPP_API_KEY`: API key para autenticação (header `apikey`)

### 2. Testar Configuração

Crie uma nova solicitação no sistema e verifique se a mensagem WhatsApp é enviada ao cliente.

---

## Estrutura do Banco de Dados

### Tabela: `whatsapp_templates`

Armazena templates de mensagens customizáveis.

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | INT | ID único do template |
| `nome` | VARCHAR(255) | Nome descritivo do template |
| `tipo` | VARCHAR(100) | Tipo de mensagem (ex: 'Nova Solicitação') |
| `corpo` | TEXT | Corpo do template com variáveis `{{variavel}}` |
| `variaveis` | JSON | Array de variáveis usadas no template |
| `ativo` | TINYINT | 1 = ativo, 0 = inativo |
| `padrao` | TINYINT | 1 = template padrão do tipo |
| `created_at` | TIMESTAMP | Data de criação |
| `updated_at` | TIMESTAMP | Data de atualização |

### Tabela: `schedule_confirmation_tokens`

Tokens únicos para confirmação de horários via link.

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | INT | ID único do token |
| `token` | VARCHAR(64) | Token único (64 caracteres) |
| `solicitacao_id` | INT | ID da solicitação |
| `protocol` | VARCHAR | Protocolo da solicitação |
| `scheduled_date` | DATE | Data sugerida |
| `scheduled_time` | VARCHAR | Horário sugerido (ex: '14:00-17:00') |
| `expires_at` | TIMESTAMP | Expiração do token (48h) |
| `used_at` | TIMESTAMP | Data/hora de uso |
| `action_type` | ENUM | 'confirm', 'cancel' ou 'reschedule' |
| `created_at` | TIMESTAMP | Data de criação |

---

## Templates de Mensagens

### Templates Padrão Instalados

1. **Nova Solicitação - Padrão**
2. **Horário Confirmado - Padrão**
3. **Horário Sugerido - Padrão**
4. **Confirmação de Serviço - Padrão**
5. **Atualização de Status - Padrão**

### Como Criar um Novo Template

```sql
INSERT INTO whatsapp_templates (nome, tipo, corpo, variaveis, ativo, padrao) VALUES (
    'Meu Template Customizado',
    'Nova Solicitação',
    'Olá {{cliente_nome}}, sua solicitação {{protocol}} foi criada!',
    JSON_ARRAY('cliente_nome', 'protocol'),
    1,
    0
);
```

### Regras de Templates

- **Ativo**: Apenas templates com `ativo = 1` são usados
- **Padrão**: Se houver múltiplos templates do mesmo tipo, o com `padrao = 1` tem prioridade
- **Variáveis**: Use `{{nome_da_variavel}}` para substituição dinâmica
- **Fallback**: Se não houver template, o envio falha (registrado no error_log)

---

## Variáveis Disponíveis

### Variáveis de Cliente

| Variável | Descrição | Exemplo |
|----------|-----------|---------|
| `{{cliente_nome}}` | Nome completo | João da Silva |
| `{{cliente_cpf}}` | CPF | 123.456.789-00 |
| `{{cliente_telefone}}` | Telefone | (11) 99999-9999 |
| `{{cliente_email}}` | E-mail | joao@email.com |

### Variáveis de Solicitação

| Variável | Descrição | Exemplo |
|----------|-----------|---------|
| `{{protocol}}` | Protocolo/Nº atendimento | KS2025-001 |
| `{{contrato_numero}}` | Número do contrato | CONT12345 |
| `{{protocolo_seguradora}}` | Protocolo seguradora | SEG2025001 |
| `{{descricao_problema}}` | Descrição do problema | Vazamento na pia |
| `{{servico_tipo}}` | Tipo de serviço | Hidráulica |

### Variáveis de Endereço

| Variável | Descrição | Exemplo |
|----------|-----------|---------|
| `{{endereco_completo}}` | Endereço formatado | Rua A, nº 123, Centro, SP |
| `{{endereco_rua}}` | Logradouro | Rua Augusta |
| `{{endereco_numero}}` | Número | 123 |
| `{{endereco_bairro}}` | Bairro | Centro |
| `{{endereco_cidade}}` | Cidade | São Paulo |
| `{{endereco_estado}}` | Estado (UF) | SP |
| `{{endereco_cep}}` | CEP | 01234-567 |

### Variáveis de Agendamento

| Variável | Descrição | Exemplo |
|----------|-----------|---------|
| `{{data_agendamento}}` | Data formatada | 31/10/2025 |
| `{{horario_agendamento}}` | Horário | 14:00 |
| `{{horario_servico}}` | Data e horário completos | 31/10/2025 14:00 |

### Variáveis de Status

| Variável | Descrição | Exemplo |
|----------|-----------|---------|
| `{{status_atual}}` | Status atual | Serviço Agendado |

### Variáveis de Imobiliária

| Variável | Descrição | Exemplo |
|----------|-----------|---------|
| `{{imobiliaria_nome}}` | Nome da imobiliária | Imobiliária ABC |

### Variáveis de Links

| Variável | Descrição | Exemplo |
|----------|-----------|---------|
| `{{link_rastreamento}}` | Link para ver solicitação | http://kss.com/locatario/solicitacao/123 |
| `{{link_confirmacao}}` | Link de confirmação | http://kss.com/confirmar/abc123 |
| `{{link_cancelamento}}` | Link de cancelamento | http://kss.com/cancelar/abc123 |
| `{{link_status}}` | Link para atualizar status | http://kss.com/status/abc123 |

---

## Gatilhos de Notificação

### Quando as notificações são enviadas:

| Evento | Arquivo | Método | Tipo de Mensagem |
|--------|---------|--------|------------------|
| Nova solicitação criada | SolicitacoesController.php | `criarSolicitacao()` | Nova Solicitação |
| Status atualizado | SolicitacoesController.php | `updateStatus()` | Atualização de Status |
| Horário confirmado | SolicitacoesController.php | `confirmarHorario()` | Horário Confirmado |
| Novos horários solicitados | SolicitacoesController.php | `solicitarNovosHorarios()` | Horário Sugerido |
| Serviço confirmado | SolicitacoesController.php | `confirmarServico()` | Confirmação de Serviço |

### Fluxo de Envio

```php
// 1. Evento ocorre no sistema
$solicitacaoId = $this->solicitacaoModel->create($data);

// 2. Notificação é enviada diretamente
$this->enviarNotificacaoWhatsApp($solicitacaoId, 'Nova Solicitação');

// 3. WhatsAppService processa imediatamente:
//    - Busca template do banco
//    - Substitui variáveis
//    - Envia via Evolution API
//    - Retorna sucesso ou erro
```

---

## Como Usar

### 1. Enviar Mensagem Diretamente

```php
use App\Services\WhatsAppService;

$whatsappService = new WhatsAppService();

$result = $whatsappService->sendMessage(
    $solicitacaoId,      // ID da solicitação
    'Nova Solicitação',  // Tipo de mensagem
    [                    // Dados extras (opcional)
        'data_agendamento' => '31/10/2025',
        'horario_agendamento' => '14:00'
    ]
);

if ($result['success']) {
    echo "Mensagem enviada!";
} else {
    echo "Erro: " . $result['message'];
}
```

### 2. Gerar Token de Confirmação

```php
use App\Services\WhatsAppService;

$whatsappService = new WhatsAppService();

$token = $whatsappService->generateConfirmationToken(
    $solicitacaoId,
    'KS2025-001',        // Protocolo
    '2025-11-01',        // Data sugerida
    '14:00-17:00'        // Horário sugerido
);

// Token válido por 48 horas
$linkConfirmacao = config('app.url') . "/confirmar-horario/$token";
```

### 3. Integrar em Novo Evento

```php
// No seu controller
public function meuMetodo(int $id): void
{
    // ... sua lógica ...
    
    // Enviar WhatsApp
    $this->enviarNotificacaoWhatsApp($id, 'Tipo da Mensagem', [
        'variavel_extra' => 'valor'
    ]);
}
```

---

## Tokens de Confirmação

### Visão Geral

O sistema cria automaticamente **tokens de confirmação** para permitir que clientes confirmem, cancelem ou reagendem horários clicando em links seguros enviados via WhatsApp.

### Características dos Tokens

- **Validade:** 48 horas a partir da criação
- **Single Use:** Cada token pode ser usado apenas uma vez
- **Segurança:** Hash SHA-256 de 64 caracteres hexadecimais
- **Rastreável:** Armazena data de uso e tipo de ação realizada

### Quando São Criados

Tokens são criados automaticamente para estes tipos de mensagem:

| Tipo de Mensagem | Action Type | Uso |
|------------------|-------------|-----|
| **Horário Confirmado** | `confirm` | Cliente confirma/cancela o horário |
| **Horário Sugerido** | `reschedule` | Cliente aceita/rejeita/reagenda |
| **Confirmação de Serviço** | `service_status` | Cliente informa resultado do serviço |

### Links Gerados Automaticamente

O `WhatsAppService` adiciona automaticamente 3 tipos de links quando um token é criado:

```php
// Variáveis disponíveis nos templates
{{link_confirmacao}}  // Para confirmar horário
{{link_cancelamento}} // Para cancelar horário
{{link_status}}       // Para informar status do serviço
```

**Exemplo de links gerados:**
```
https://seu-site.com/confirmacao-horario?token=a1b2c3d4e5f6...
https://seu-site.com/cancelamento-horario?token=a1b2c3d4e5f6...
https://seu-site.com/status-servico?token=a1b2c3d4e5f6...
```

### Fluxo Completo

```
1. Operador confirma horário
   ↓
2. WhatsAppService detecta tipo "Horário Confirmado"
   ↓
3. ScheduleConfirmationToken::createToken() é chamado
   ↓
4. Token salvo com expires_at = NOW() + 48 horas
   ↓
5. Links incluídos nas variáveis do template
   ↓
6. Mensagem enviada com links clicáveis
   ↓
7. Cliente clica no link
   ↓
8. Sistema valida token (não expirado + não usado)
   ↓
9. Ação é executada (confirmar/cancelar/reagendar)
   ↓
10. Token marcado como usado (used_at = NOW())
```

### Validação de Token

**Backend (PHP):**
```php
use App\Models\ScheduleConfirmationToken;

$tokenModel = new ScheduleConfirmationToken();

// Validar token
$tokenData = $tokenModel->validateToken($token);

if (!$tokenData) {
    // Token inválido, expirado ou já usado
    echo "Token inválido ou expirado";
    exit;
}

// Token válido - processar ação
// ... sua lógica aqui ...

// Marcar como usado
$tokenModel->markAsUsed($token, 'confirm');
```

### Métodos Disponíveis

#### `createToken()`
```php
$tokenModel = new ScheduleConfirmationToken();
$token = $tokenModel->createToken(
    $solicitacaoId,      // int
    $protocol,           // string (ex: "KS001")
    $scheduledDate,      // string|null (Y-m-d)
    $scheduledTime,      // string|null (ex: "08:00-11:00")
    $actionType          // string|null ('confirm', 'reschedule', etc)
);
// Retorna: string (64 caracteres)
```

#### `validateToken()`
```php
$tokenData = $tokenModel->validateToken($token);
// Retorna: array|null
// Se válido: ['id' => 1, 'token' => '...', 'solicitacao_id' => 5, ...]
// Se inválido/expirado/usado: null
```

#### `markAsUsed()`
```php
$tokenModel->markAsUsed($token, 'confirm');
// Marca used_at = NOW() e action_type = 'confirm'
```

#### `invalidateTokensBySolicitacao()`
```php
// Útil quando o agendamento é alterado - invalida todos os tokens antigos
$tokenModel->invalidateTokensBySolicitacao($solicitacaoId);
```

### Páginas de Confirmação (TODO)

⚠️ **Importante:** Você precisa criar as páginas frontend para processar os tokens:

1. **`/confirmacao-horario`** - Confirmar/cancelar/reagendar horário
2. **`/cancelamento-horario`** - Cancelar horário confirmado
3. **`/status-servico`** - Informar resultado do serviço

**Exemplo básico:**
```php
// confirmacao-horario.php
$token = $_GET['token'] ?? '';

$tokenModel = new ScheduleConfirmationToken();
$tokenData = $tokenModel->validateToken($token);

if (!$tokenData) {
    echo "Link inválido ou expirado";
    exit;
}

// Mostrar formulário de confirmação/cancelamento
// ...
```

---

## Troubleshooting

### Problema: Mensagens não são enviadas

**Checklist:**
1. Verificar se `WHATSAPP_ENABLED=true` no `.env`
2. Conferir credenciais: `WHATSAPP_API_URL`, `WHATSAPP_API_KEY`, `WHATSAPP_INSTANCE`, `WHATSAPP_TOKEN`
3. Verificar se o template está ativo: `SELECT * FROM whatsapp_templates WHERE ativo = 1`
4. Testar Evolution API diretamente via cURL
5. Verificar logs de erro do PHP (`error_log`)

### Problema: Variáveis não são substituídas

**Solução:**
- Verificar se as variáveis estão corretas no template
- Conferir se os dados existem na solicitação
- Usar `{{variavel}}` (com chaves duplas)

**Exemplo correto:**
```
Olá {{cliente_nome}}, seu protocolo é {{protocol}}.
```

### Problema: "Evolution API retornou código 401"

**Causa:** Credenciais inválidas

**Solução:**
```bash
# Verificar variáveis de ambiente
cat .env | grep WHATSAPP

# Testar API diretamente
curl -H "apikey: SEU_API_KEY" \
     -H "Content-Type: application/json" \
     -X POST \
     -d '{"number":"5511999998888@c.us","text":"Teste"}' \
     https://evolutionapi.launs.com.br/message/sendText/notification_launs_02
```

### Problema: "Number format invalid"

**Causa:** Número de telefone mal formatado

**Solução:** O sistema formata automaticamente para `5511999998888@c.us`, mas certifique-se de que o número no banco está correto:
```sql
-- Verificar formato dos telefones
SELECT id, nome, telefone FROM locatarios WHERE telefone NOT LIKE '(__) _____-____';
```

### Problema: Templates não aparecem

**Solução:**
```sql
-- Verificar templates instalados
SELECT * FROM whatsapp_templates;

-- Ativar template
UPDATE whatsapp_templates SET ativo = 1 WHERE id = ?;
```

---

## Boas Práticas

### Performance
- Envio direto: mensagens enviadas imediatamente (sem atrasos)
- Erros não bloqueiam operação principal (apenas logados)
- Timeout de 30 segundos na Evolution API

### Segurança
- Não exponha credenciais do WhatsApp no código
- Use `.env` para configurações sensíveis
- Tokens expiram em 48h automaticamente
- Valide tokens antes de processar ações

### Manutenção
- Monitore logs de erro regularmente
- Atualize templates conforme necessário
- Teste mudanças antes de aplicar em produção
- Mantenha backup dos templates personalizados

---

## Resumo Rápido

| Aspecto | Descrição |
|---------|-----------|
| Modo | Envio direto/síncrono |
| Retry | Não (apenas loga erro) |
| Templates | Editáveis no banco de dados |
| Tokens | Expiram em 48 horas |
| Formatação | Automática para WhatsApp |
| Logs | error_log do PHP |

---

**Documentação atualizada em:** 31/10/2025  
**Versão:** 1.0.0 (Envio Direto)

