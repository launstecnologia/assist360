# 🔍 Análise de Erros - Show.php do Kanban e Processamento de Dados

## 📋 Resumo Executivo

Este documento analisa os erros e problemas encontrados no código que exibe os detalhes da solicitação (`show.php`) e no processamento de dados relacionado ao kanban. Foram identificados vários problemas críticos que podem estar impedindo implementações de funcionarem corretamente.

---

## 🎯 Lógica Atual do Sistema

### 1. Fluxo de Dados

```
Kanban (index.php)
    ↓
abrirDetalhes(solicitacaoId)
    ↓
GET /admin/solicitacoes/{id}/api
    ↓
SolicitacoesController@api
    ↓
Solicitacao::getDetalhes()
    ↓
renderizarDetalhes(data.solicitacao) [JavaScript]
```

### 2. Estrutura de Dados de Horários

O sistema usa múltiplos campos para armazenar horários:

- **`horarios_opcoes`**: JSON array com horários informados pelo locatário (quando `horarios_indisponiveis = 0`)
- **`datas_opcoes`**: JSON array com horários originais do locatário (quando `horarios_indisponiveis = 1`)
- **`horarios_indisponiveis`**: Boolean (0 ou 1) - indica se nenhum horário está disponível
- **`confirmed_schedules`**: JSON array com horários confirmados
- **`horario_confirmado_raw`**: String com horário confirmado no formato "dd/mm/yyyy - HH:00-HH:00"
- **`data_agendamento`** + **`horario_agendamento`**: Data e horário do agendamento

---

## ❌ ERROS CRÍTICOS ENCONTRADOS

### **ERRO #1: Inconsistência na Lógica de Busca de Horários**

**Localização:** 
- `app/Views/solicitacoes/show.php` (linhas 179-190)
- `app/Views/kanban/index.php` (linhas 928-976)

**Problema:**
A lógica de onde buscar os horários do locatário é diferente entre `show.php` e `kanban/index.php`:

**show.php:**
```php
if (!empty($solicitacao['horarios_indisponiveis'])) {
    // Horários originais do locatário estão em datas_opcoes
    $horariosOpcoes = !empty($solicitacao['datas_opcoes']) 
        ? json_decode($solicitacao['datas_opcoes'], true) : [];
} else {
    // Horários do locatário estão em horarios_opcoes
    $horariosOpcoes = !empty($solicitacao['horarios_opcoes']) 
        ? json_decode($solicitacao['horarios_opcoes'], true) : [];
}
```

**kanban/index.php (renderizarDetalhes):**
```javascript
if (solicitacao.horarios_indisponiveis) {
    if (solicitacao.datas_opcoes) {
        horariosLocatario = JSON.parse(solicitacao.datas_opcoes);
    } else {
        // FALLBACK: tenta buscar de horarios_opcoes (caso antigo)
        if (solicitacao.horarios_opcoes) {
            horariosLocatario = JSON.parse(solicitacao.horarios_opcoes);
        }
    }
} else {
    if (solicitacao.horarios_opcoes) {
        horariosLocatario = JSON.parse(solicitacao.horarios_opcoes);
    }
}
```

**Impacto:** 
- O kanban tem um fallback que o `show.php` não tem
- Isso pode causar horários diferentes sendo exibidos em cada lugar
- Quando `horarios_indisponiveis = 1` e `datas_opcoes` está vazio, o kanban tenta `horarios_opcoes`, mas o `show.php` não

---

### **ERRO #2: Processamento Complexo e Propenso a Erros na Comparação de Horários**

**Localização:** `app/Views/solicitacoes/show.php` (linhas 203-360)

**Problema:**
A lógica para verificar se um horário está confirmado é extremamente complexa e tem múltiplos pontos de falha:

1. **Múltiplas tentativas de parsing de formato** (linhas 217-251):
   - Tenta vários formatos diferentes
   - Se falhar, usa o original sem validação
   - Pode gerar horários mal formatados

2. **Comparação em 3 lugares diferentes** (linhas 256-359):
   - `confirmed_schedules` (JSON)
   - `horario_confirmado_raw` (string)
   - `data_agendamento` + `horario_agendamento`

3. **Regex complexa e propensa a erros** (linhas 280-291):
   ```php
   $regex = '/(\d{2}\/\d{2}\/\d{4})\s*-\s*(\d{2}:\d{2})\s*(?:às|-)\s*(\d{2}:\d{2})/';
   ```
   - Aceita tanto "às" quanto "-" como separador
   - Mas a normalização pode falhar se houver espaços extras

4. **Comparação de hora final não funciona corretamente** (linhas 304-313):
   ```php
   $horaFimAtual = date('H:i', strtotime('+3 hours', $dt->getTimestamp()));
   ```
   - Assume sempre +3 horas, mas isso pode não ser verdade
   - Não valida se o horário realmente tem 3 horas de duração

**Impacto:**
- Horários podem ser marcados como confirmados incorretamente
- Horários confirmados podem não ser detectados
- Dificulta debugging e manutenção

---

### **ERRO #3: Falta de Validação de Tipos no Controller**

**Localização:** `app/Controllers/SolicitacoesController.php` (linhas 411-420)

**Problema:**
O controller faz parse de `confirmed_schedules`, mas não valida se os dados estão no formato esperado:

```php
if (!empty($solicitacao['confirmed_schedules'])) {
    if (is_string($solicitacao['confirmed_schedules'])) {
        $parsed = json_decode($solicitacao['confirmed_schedules'], true);
        $solicitacao['confirmed_schedules'] = is_array($parsed) ? $parsed : null;
    }
} else {
    $solicitacao['confirmed_schedules'] = null;
}
```

**Problemas:**
1. Não valida se `$parsed` é realmente um array válido
2. Não valida a estrutura dos objetos dentro do array
3. Se `json_decode` retornar `false` (erro), não trata adequadamente
4. Não verifica se os objetos têm as propriedades esperadas (`raw`, `date`, `time`, `source`)

**Impacto:**
- Dados malformados podem passar sem validação
- Erros silenciosos podem ocorrer
- Dificulta identificar problemas na origem

---

### **ERRO #4: Inconsistência no Parse de JSON entre PHP e JavaScript**

**Localização:** 
- PHP: `app/Views/solicitacoes/show.php` (linhas 184-189)
- JavaScript: `app/Views/kanban/index.php` (linhas 930-976)

**Problema:**
O PHP faz `json_decode()` uma vez, mas o JavaScript pode receber string ou array:

**PHP (show.php):**
```php
$horariosOpcoes = !empty($solicitacao['datas_opcoes']) 
    ? json_decode($solicitacao['datas_opcoes'], true) : [];
```

**JavaScript (kanban):**
```javascript
horariosLocatario = typeof solicitacao.datas_opcoes === 'string' 
    ? JSON.parse(solicitacao.datas_opcoes) 
    : solicitacao.datas_opcoes;
```

**Problema:**
- Se o controller já parseou o JSON, o JavaScript recebe um array
- Se o controller não parseou, o JavaScript recebe uma string
- Isso causa inconsistências

**Solução Necessária:**
O controller deve SEMPRE parsear JSON antes de enviar para a view/API.

---

### **ERRO #5: Formatação de Horários Inconsistente**

**Localização:** `app/Views/solicitacoes/show.php` (linhas 209-251)

**Problema:**
O código tenta formatar horários de múltiplas formas, mas:

1. **Formato esperado vs formato real:**
   - Esperado: `"dd/mm/yyyy - HH:00-HH:00"`
   - Mas pode receber: ISO datetime, timestamp, string formatada, etc.

2. **Lógica de formatação complexa:**
   ```php
   if (is_string($horario) && is_numeric(strtotime($horario))) {
       // Tenta criar DateTime
   } elseif (preg_match('/(\d{4}-\d{2}-\d{2})[T ](\d{2}):(\d{2})/', $horario, $matches)) {
       // Formato ISO
   } elseif (preg_match('/(\d{2})\/(\d{2})\/(\d{4})[ -](\d{2}):(\d{2})/', $horario, $matches)) {
       // Formato dd/mm/yyyy
   }
   ```

3. **Assumir sempre +3 horas:**
   ```php
   $horaFim = str_pad((int)$hora + 3, 2, '0', STR_PAD_LEFT);
   ```
   - Não valida se o horário realmente tem 3 horas
   - Pode gerar horários inválidos (ex: 23:00 + 3 = 26:00)

**Impacto:**
- Horários podem ser formatados incorretamente
- Comparações podem falhar
- Interface pode mostrar horários errados

---

### **ERRO #6: Debug Logs Excessivos em Produção**

**Localização:** 
- `app/Views/solicitacoes/show.php` (linhas 212-214, 254)
- `app/Controllers/SolicitacoesController.php` (linhas 442-462)

**Problema:**
Há muitos `error_log()` e `console.log()` que devem ser removidos ou condicionados:

```php
error_log("DEBUG show.php [ID:{$solicitacao['id']}] - Horário original do array: " . var_export($horario, true));
error_log("DEBUG show.php [ID:{$solicitacao['id']}] - horario_confirmado_raw do banco: " . var_export($solicitacao['horario_confirmado_raw'] ?? null, true));
```

**Impacto:**
- Logs excessivos podem degradar performance
- Pode expor informações sensíveis
- Dificulta encontrar logs importantes

---

## 🔧 RECOMENDAÇÕES DE CORREÇÃO

### **1. Padronizar Lógica de Busca de Horários**

Criar uma função helper que centralize a lógica:

```php
// app/Helpers/SolicitacaoHelper.php
function getHorariosLocatario(array $solicitacao): array {
    if (!empty($solicitacao['horarios_indisponiveis'])) {
        // Quando horarios_indisponiveis = 1, horários originais em datas_opcoes
        if (!empty($solicitacao['datas_opcoes'])) {
            $horarios = is_string($solicitacao['datas_opcoes']) 
                ? json_decode($solicitacao['datas_opcoes'], true) 
                : $solicitacao['datas_opcoes'];
            return is_array($horarios) ? $horarios : [];
        }
        // Fallback: tentar horarios_opcoes (caso antigo)
        if (!empty($solicitacao['horarios_opcoes'])) {
            $horarios = is_string($solicitacao['horarios_opcoes']) 
                ? json_decode($solicitacao['horarios_opcoes'], true) 
                : $solicitacao['horarios_opcoes'];
            return is_array($horarios) ? $horarios : [];
        }
    } else {
        // Quando horarios_indisponiveis = 0, horários em horarios_opcoes
        if (!empty($solicitacao['horarios_opcoes'])) {
            $horarios = is_string($solicitacao['horarios_opcoes']) 
                ? json_decode($solicitacao['horarios_opcoes'], true) 
                : $solicitacao['horarios_opcoes'];
            return is_array($horarios) ? $horarios : [];
        }
    }
    return [];
}
```

### **2. Simplificar Comparação de Horários**

Criar função de normalização e comparação:

```php
function normalizarHorario(string $horario): ?string {
    // Normalizar para formato padrão: "dd/mm/yyyy - HH:00-HH:00"
    // ... lógica de normalização
}

function compararHorarios(string $horario1, string $horario2): bool {
    $norm1 = normalizarHorario($horario1);
    $norm2 = normalizarHorario($horario2);
    return $norm1 === $norm2;
}
```

### **3. Validar Dados no Controller**

```php
// No controller, antes de enviar:
if (!empty($solicitacao['confirmed_schedules'])) {
    if (is_string($solicitacao['confirmed_schedules'])) {
        $parsed = json_decode($solicitacao['confirmed_schedules'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Erro ao parsear confirmed_schedules: " . json_last_error_msg());
            $solicitacao['confirmed_schedules'] = null;
        } else {
            // Validar estrutura
            if (is_array($parsed)) {
                $validated = [];
                foreach ($parsed as $schedule) {
                    if (is_array($schedule) && !empty($schedule['raw'])) {
                        $validated[] = $schedule;
                    }
                }
                $solicitacao['confirmed_schedules'] = $validated;
            } else {
                $solicitacao['confirmed_schedules'] = null;
            }
        }
    } elseif (!is_array($solicitacao['confirmed_schedules'])) {
        $solicitacao['confirmed_schedules'] = null;
    }
} else {
    $solicitacao['confirmed_schedules'] = null;
}
```

### **4. Remover Debug Logs ou Condicionar**

```php
if (defined('DEBUG') && DEBUG === true) {
    error_log("DEBUG: ...");
}
```

---

## 📊 Resumo dos Problemas

| Erro | Severidade | Impacto | Prioridade |
|------|-----------|---------|------------|
| #1: Inconsistência na busca de horários | 🔴 Alta | Horários diferentes em cada tela | 🔥 Crítica |
| #2: Comparação complexa de horários | 🔴 Alta | Horários confirmados não detectados | 🔥 Crítica |
| #3: Falta de validação no controller | 🟡 Média | Dados malformados passam | ⚠️ Alta |
| #4: Inconsistência no parse JSON | 🟡 Média | Erros em runtime | ⚠️ Alta |
| #5: Formatação inconsistente | 🟡 Média | Horários exibidos incorretamente | ⚠️ Alta |
| #6: Logs excessivos | 🟢 Baixa | Performance degradada | 📝 Baixa |

---

## 🎯 Próximos Passos

1. ✅ **Imediato:** Padronizar lógica de busca de horários
2. ✅ **Imediato:** Simplificar comparação de horários
3. ⚠️ **Curto prazo:** Adicionar validação no controller
4. ⚠️ **Curto prazo:** Remover/condicionar debug logs
5. 📝 **Médio prazo:** Criar testes unitários para validação

---

## 📝 Notas Finais

A complexidade atual do código torna difícil adicionar novas funcionalidades e debugar problemas. Recomenda-se uma refatoração gradual, começando pelos erros críticos (#1 e #2).

