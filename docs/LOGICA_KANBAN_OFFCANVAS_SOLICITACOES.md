# 📋 LÓGICA COMPLETA: Kanban, Offcanvas e Solicitações

## 🎯 VISÃO GERAL

O sistema possui **três interfaces principais** para gerenciar solicitações:

1. **Kanban Board** (`/admin/kanban`) - Interface visual com drag-and-drop
2. **Offcanvas** (modal lateral no Kanban) - Edição rápida sem sair do Kanban
3. **Página de Detalhes** (`/admin/solicitacoes/{id}`) - Visualização completa

---

## 1️⃣ KANBAN BOARD

### 📍 Localização
- **Arquivo**: `app/Views/kanban/index.php`
- **Controller**: `DashboardController@kanban`
- **Rota**: `GET /admin/kanban`

### 🎨 Estrutura Visual

```
┌─────────────────────────────────────────────────────────────┐
│ Filtros: [Todas as Imobiliárias ▼]                         │
└─────────────────────────────────────────────────────────────┘

┌───────────┐  ┌───────────┐  ┌───────────┐  ┌───────────┐
│ Pendente  │  │ Agendado   │  │ Em And.   │  │ Concluído │
│    (5)    │  │    (3)     │  │    (2)    │  │    (8)    │
├───────────┤  ├───────────┤  ├───────────┤  ├───────────┤
│ 🟢 Card 1 │  │ 🟡 Card 4  │  │ 🟠 Card 7 │  │ 🔵 Card 10│
│ 🟢 Card 2 │  │ 🟡 Card 5  │  │ 🟠 Card 8 │  │ 🔵 Card 11│
│ 🟢 Card 3 │  │ 🟡 Card 6  │  │           │  │ 🔵 Card 12│
└───────────┘  └───────────┘  └───────────┘  └───────────┘
```

### 🔧 Funcionalidades

#### **A) Drag-and-Drop (SortableJS)**
```javascript
// SortableJS inicializa cada coluna
new Sortable(column, {
    group: 'kanban',          // Permite arrastar entre colunas
    animation: 150,           // Animação suave
    ghostClass: 'bg-blue-100', // Classe visual ao arrastar
    onEnd: function(evt) {
        // Quando solta o card
        const solicitacaoId = evt.item.getAttribute('data-solicitacao-id');
        const novoStatusId = evt.to.getAttribute('data-status-id');
        
        // POST /admin/kanban/mover
        fetch('/admin/kanban/mover', {
            method: 'POST',
            body: JSON.stringify({
                solicitacao_id: solicitacaoId,
                novo_status_id: novoStatusId
            })
        });
    }
});
```

**Fluxo:**
1. Usuário arrasta card de uma coluna para outra
2. `onEnd` captura o evento
3. Faz POST para `/admin/kanban/mover`
4. `DashboardController@moverCard` processa
5. `Solicitacao::updateStatus()` atualiza no banco
6. Card é atualizado visualmente

#### **B) Abrir Detalhes no Offcanvas**
```javascript
function abrirDetalhes(solicitacaoId) {
    // 1. Mostrar offcanvas
    const offcanvas = document.getElementById('detalhesOffcanvas');
    offcanvas.classList.remove('hidden');
    
    // 2. Mostrar loading
    const loadingContent = document.getElementById('loadingContent');
    loadingContent.classList.remove('hidden');
    
    // 3. Buscar dados via API
    fetch(`/admin/solicitacoes/${solicitacaoId}/api`)
        .then(response => response.json())
        .then(data => {
            // 4. Renderizar conteúdo
            renderizarDetalhes(data.solicitacao);
        });
}
```

**Endpoint:** `GET /admin/solicitacoes/{id}/api`  
**Controller:** `SolicitacoesController@api`  
**Retorna:** JSON com todos os dados da solicitação

---

## 2️⃣ OFFCANVAS (Modal Lateral)

### 📍 Localização
- **HTML**: Dentro de `app/Views/kanban/index.php` (linhas 148-178)
- **JavaScript**: Funções `renderizarDetalhes()`, `salvarAlteracoes()`

### 🎨 Estrutura Visual

```
┌─────────────────────────────────────────────────────┐
│ [X] Detalhes da Solicitação  [Copiar] [Fechar]     │
├─────────────────────────────────────────────────────┤
│                                                     │
│ ┌──────────────┐  ┌──────────────┐                │
│ │ Cliente      │  │ Endereço     │                │
│ │ Nome: ...    │  │ Rua: ...     │                │
│ └──────────────┘  └──────────────┘                │
│                                                     │
│ ┌──────────────┐  ┌──────────────┐                │
│ │ Descrição    │  │ Observações  │                │
│ │ ...          │  │ [textarea]   │                │
│ └──────────────┘  └──────────────┘                │
│                                                     │
│ ┌──────────────────────────────────────┐          │
│ │ Disponibilidade                      │          │
│ │ ☐ 26/11/2025 - 08:00-11:00          │          │
│ │ ☐ 26/11/2025 - 11:00-14:00          │          │
│ │ ☐ 26/11/2025 - 14:00-17:00          │          │
│ │ ☐ Nenhum horário disponível         │          │
│ └──────────────────────────────────────┘          │
│                                                     │
│ [Salvar Alterações] [Ver Página Completa] [Fechar]│
└─────────────────────────────────────────────────────┘
```

### 🔧 Funcionalidades

#### **A) Renderização Dinâmica**
```javascript
function renderizarDetalhes(solicitacao) {
    // 1. Parse de horários_opcoes (JSON string)
    let horariosOpcoes = [];
    try {
        horariosOpcoes = solicitacao.horarios_opcoes 
            ? JSON.parse(solicitacao.horarios_opcoes) 
            : [];
    } catch (e) {
        horariosOpcoes = [];
    }
    
    // 2. Renderizar HTML completo
    content.innerHTML = `
        <!-- Cliente -->
        <div class="bg-white rounded-lg p-5">
            <h3>Informações do Cliente</h3>
            <p>${solicitacao.locatario_nome}</p>
        </div>
        
        <!-- Disponibilidade com CHECKBOXES -->
        <div class="bg-white rounded-lg p-5">
            <h3>Disponibilidade Informada pelo Segurado</h3>
            ${horariosOpcoes.map((horario, index) => {
                // Formatar horário para exibição
                const dt = new Date(horario);
                const textoHorario = `${dia}/${mes}/${ano} - ${faixaHora}`;
                
                return `
                <div class="flex items-center gap-3">
                    <input type="checkbox" 
                           class="horario-offcanvas" 
                           data-raw="${textoHorario}" 
                           id="horario-${index}">
                    <label for="horario-${index}">${textoHorario}</label>
                </div>
                `;
            }).join('')}
        </div>
    `;
}
```

**Pontos Importantes:**
- ✅ Checkboxes têm classe `horario-offcanvas`
- ✅ Atributo `data-raw` contém o texto formatado do horário
- ✅ Permite seleção múltipla

#### **B) Salvar Alterações (COM HORÁRIOS)**
```javascript
function salvarAlteracoes(solicitacaoId) {
    // 1. Coletar dados do formulário
    const observacoes = document.querySelector('textarea[...]')?.value || '';
    const precisaReembolso = document.getElementById('checkboxReembolso')?.checked || false;
    const valorReembolso = document.getElementById('valorReembolso')?.value || '';
    const protocoloSeguradora = document.getElementById('protocoloSeguradora')?.value || '';
    
    // 2. Coletar horários selecionados (CHECKBOXES)
    const schedules = coletarSchedulesOffcanvas();
    // Retorna: [{date: '2025-11-26', time: '08:00-11:00', raw: '26/11/2025 - 08:00-11:00'}, ...]
    
    // 3. Montar payload
    const dados = {
        observacoes: observacoes,
        precisa_reembolso: precisaReembolso,
        valor_reembolso: valorReembolso,
        protocolo_seguradora: protocoloSeguradora,
        schedules: schedules  // ← HORÁRIOS SELECIONADOS
    };
    
    // 4. POST para /admin/solicitacoes/{id}/atualizar
    fetch(`/admin/solicitacoes/${solicitacaoId}/atualizar`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(dados)
    });
}
```

**Função auxiliar:**
```javascript
function coletarSchedulesOffcanvas() {
    // 1. Buscar todos os checkboxes marcados
    const checkboxes = document.querySelectorAll('.horario-offcanvas:checked');
    
    // 2. Extrair data-raw de cada um
    return Array.from(checkboxes)
        .map(chk => parseScheduleRawOffcanvas(chk.getAttribute('data-raw')))
        .filter(s => s.date || s.time);
}

function parseScheduleRawOffcanvas(raw) {
    // raw = "26/11/2025 - 08:00-11:00"
    const out = { date: null, time: null, raw };
    
    // Extrair data: "26/11/2025" → "2025-11-26"
    const dBR = raw.match(/(\d{2})\/(\d{2})\/(\d{4})/);
    if (dBR) out.date = `${dBR[3]}-${dBR[2]}-${dBR[1]}`;
    
    // Extrair horário: "08:00-11:00" ou "08:00"
    const range = raw.match(/(\d{2}:\d{2})\s?-\s?(\d{2}:\d{2})/);
    if (range) out.time = `${range[1]}-${range[2]}`;
    else {
        const single = raw.match(/\b(\d{2}:\d{2})\b/);
        if (single) out.time = single[1];
    }
    
    return out;
}
```

**Endpoint:** `POST /admin/solicitacoes/{id}/atualizar`  
**Controller:** `SolicitacoesController@atualizarDetalhes`

**Processamento no Backend:**
```php
public function atualizarDetalhes(int $id): void
{
    // 1. Ler JSON do body
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    
    // 2. Extrair campos
    $observacoes = $json['observacoes'] ?? '';
    $precisaReembolso = $json['precisa_reembolso'] ?? false;
    $valorReembolso = $json['valor_reembolso'] ?? '';
    $protocoloSeguradora = $json['protocolo_seguradora'] ?? '';
    $schedules = $json['schedules'] ?? [];  // ← HORÁRIOS
    
    // 3. Montar dados para atualizar
    $dados = [
        'observacoes' => $observacoes,
        'precisa_reembolso' => $precisaReembolso ? 1 : 0,
        'valor_reembolso' => $valorReembolso ? floatval($valorReembolso) : null,
        'protocolo_seguradora' => $protocoloSeguradora
    ];
    
    // 4. Se tem horários, processar confirmação
    if (!empty($schedules)) {
        $confirmed = [];
        foreach ($schedules as $s) {
            $confirmed[] = [
                'date' => $s['date'] ?? null,
                'time' => $s['time'] ?? null,
                'raw' => $s['raw'] ?? '',
                'source' => 'operator',
                'confirmed_at' => date('c')
            ];
        }
        
        // Último horário vira o agendamento principal
        $last = end($confirmed);
        $dataAg = $last['date'] ? date('Y-m-d', strtotime($last['date'])) : null;
        $horaAg = preg_match('/^\d{2}:\d{2}/', $last['time'] ?? '', $m) 
            ? $m[0] . ':00' 
            : null;
        
        // Adicionar campos de agendamento
        $dados['data_agendamento'] = $dataAg;
        $dados['horario_agendamento'] = $horaAg;
        $dados['horario_confirmado'] = 1;
        $dados['horario_confirmado_raw'] = $last['raw'];
        $dados['confirmed_schedules'] = json_encode($confirmed);
        $dados['status_id'] = $this->getStatusId('Serviço Agendado');  // ← MUDAR STATUS
    }
    
    // 5. Atualizar no banco
    $this->solicitacaoModel->update($id, $dados);
    
    // 6. Retornar sucesso
    $this->json(['success' => true]);
}
```

**O que acontece:**
1. ✅ Salva observações, reembolso, protocolo
2. ✅ Se há `schedules`, confirma os horários selecionados
3. ✅ Atualiza `data_agendamento`, `horario_agendamento`
4. ✅ Marca `horario_confirmado = 1`
5. ✅ Salva todos em `confirmed_schedules` (JSON)
6. ✅ Muda status para **"Serviço Agendado"**

---

## 3️⃣ PÁGINA DE DETALHES (`solicitacoes/show.php`)

### 📍 Localização
- **Arquivo**: `app/Views/solicitacoes/show.php`
- **Controller**: `SolicitacoesController@show`
- **Rota**: `GET /admin/solicitacoes/{id}`

### 🎨 Estrutura Visual

```
┌─────────────────────────────────────────────────────────────┐
│ Detalhes da Solicitação                    [Copiar] [X]    │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ ┌──────────────────────────┐  ┌───────────────────────┐   │
│ │ Cliente                  │  │ Endereço              │   │
│ │ Nome: ...                │  │ Rua: ...              │   │
│ └──────────────────────────┘  └───────────────────────┘   │
│                                                             │
│ ┌──────────────────────────┐  ┌───────────────────────┐   │
│ │ Descrição                │  │ Observações           │   │
│ │ [Texto destacado]        │  │ [Formulário]          │   │
│ └──────────────────────────┘  └───────────────────────┘   │
│                                                             │
│ ┌──────────────────────────────────────────────────────┐  │
│ │ Disponibilidade Informada pelo Locatário            │  │
│ │                                                      │  │
│ │ ┌────────────────────────────────────────────────┐ │  │
│ │ │ ✅ 26/11/2025 - 08:00  [Desconfirmar]         │ │  │
│ │ │ Horário Confirmado                            │ │  │
│ │ └────────────────────────────────────────────────┘ │  │
│ │                                                      │  │
│ │ ┌────────────────────────────────────────────────┐ │  │
│ │ │ 🕐 26/11/2025 - 11:00  [Confirmar horário]    │ │  │
│ │ └────────────────────────────────────────────────┘ │  │
│ │                                                      │  │
│ │ ┌────────────────────────────────────────────────┐ │  │
│ │ │ 🕐 26/11/2025 - 14:00  [Confirmar horário]    │ │  │
│ │ └────────────────────────────────────────────────┘ │  │
│ │                                                      │  │
│ │ ☐ Horários Indisponíveis - Solicitar novos horários│  │
│ └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

### 🔧 Funcionalidades

#### **A) Exibição de Horários com Estado**

```php
<?php
// Parse de horarios_opcoes
$horariosOpcoes = !empty($solicitacao['horarios_opcoes']) 
    ? json_decode($solicitacao['horarios_opcoes'], true) 
    : [];

foreach ($horariosOpcoes as $horario):
    // Verificar se este horário está confirmado
    $horarioConfirmado = false;
    
    // Comparar com horario_confirmado_raw
    if (!empty($solicitacao['horario_confirmado_raw'])) {
        $horarioConfirmado = trim((string)$solicitacao['horario_confirmado_raw']) 
            === trim((string)$horario);
    }
    // OU comparar com data_agendamento + horario_agendamento
    elseif (!empty($solicitacao['data_agendamento']) && !empty($solicitacao['horario_agendamento'])) {
        $dataHoraConfirmada = $solicitacao['data_agendamento'] . ' ' . $solicitacao['horario_agendamento'];
        $dataHoraAtual = date('Y-m-d H:i:s', strtotime($horario));
        $horarioConfirmado = (date('Y-m-d H:i', strtotime($dataHoraConfirmada)) 
            === date('Y-m-d H:i', strtotime($dataHoraAtual)));
    }
?>
    <div class="<?= $horarioConfirmado ? 'bg-green-50 border-2 border-green-500' : 'bg-blue-50' ?>">
        <?php if ($horarioConfirmado): ?>
            <i class="fas fa-check-circle text-green-600"></i>
            <span>Horário Confirmado</span>
            <button onclick="desconfirmarHorario(<?= $solicitacao['id'] ?>)">
                Desconfirmar
            </button>
        <?php else: ?>
            <i class="fas fa-clock text-blue-600"></i>
            <button onclick="confirmarHorario(<?= $solicitacao['id'] ?>, '<?= $horario ?>')">
                Confirmar horário
            </button>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
```

#### **B) Confirmação Individual de Horário**

```javascript
function confirmarHorario(solicitacaoId, horario) {
    // POST /admin/solicitacoes/{id}/confirmar-horario
    fetch(`/admin/solicitacoes/${solicitacaoId}/confirmar-horario`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ horario: horario })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();  // Recarregar página
        }
    });
}
```

**Endpoint:** `POST /admin/solicitacoes/{id}/confirmar-horario`  
**Controller:** `SolicitacoesController@confirmarHorario`

```php
public function confirmarHorario(int $id): void
{
    $horario = $this->input('horario');
    
    // Buscar status "Serviço Agendado"
    $statusAgendado = \App\Core\Database::fetch(
        "SELECT id FROM status WHERE nome = 'Serviço Agendado' LIMIT 1"
    );
    
    // Extrair data e hora
    $dataAg = date('Y-m-d', strtotime($horario));
    $horaAg = date('H:i:s', strtotime($horario));
    
    // Atualizar descrição do card
    $solicitacaoAtual = $this->solicitacaoModel->find($id);
    $descricao = (string)($solicitacaoAtual['descricao_problema'] ?? '');
    $descricao = preg_replace('/\n?Disponibilidade:\s.*$/m', '', $descricao);
    $descricao .= "\nDisponibilidade: " . date('d/m/Y H:i', strtotime($horario));
    
    // Atualizar banco
    $this->solicitacaoModel->update($id, [
        'data_agendamento' => $dataAg,
        'horario_agendamento' => $horaAg,
        'status_id' => $statusAgendado['id'],
        'horario_confirmado' => 1,
        'horario_confirmado_raw' => $horario,
        'descricao_problema' => $descricao,
        'descricao_card' => $descricao
    ]);
    
    // Registrar histórico
    $this->solicitacaoModel->updateStatus($id, $statusAgendado['id'], $user['id'], 
        'Horário confirmado: ' . date('d/m/Y H:i', strtotime($horario)));
    
    // Enviar WhatsApp
    $this->enviarNotificacaoWhatsApp($id, 'Horário Confirmado');
    
    $this->json(['success' => true]);
}
```

#### **C) Desconfirmação de Horário**

```javascript
function desconfirmarHorario(solicitacaoId) {
    // POST /admin/solicitacoes/{id}/desconfirmar-horario
    fetch(`/admin/solicitacoes/${solicitacaoId}/desconfirmar-horario`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'}
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}
```

**Endpoint:** `POST /admin/solicitacoes/{id}/desconfirmar-horario`  
**Controller:** `SolicitacoesController@desconfirmarHorario`

```php
public function desconfirmarHorario(int $id): void
{
    // Buscar status "Pendente"
    $statusPendente = \App\Core\Database::fetch(
        "SELECT id FROM status WHERE nome = 'Pendente' LIMIT 1"
    );
    
    // Limpar agendamento
    $this->solicitacaoModel->update($id, [
        'data_agendamento' => null,
        'horario_agendamento' => null,
        'status_id' => $statusPendente['id'],
        'horario_confirmado' => 0,
        'horario_confirmado_raw' => null
    ]);
    
    // Registrar histórico
    $this->solicitacaoModel->updateStatus($id, $statusPendente['id'], $user['id'], 
        'Horário desconfirmado pelo operador');
    
    $this->json(['success' => true]);
}
```

---

## 🔄 COMPARAÇÃO: OFFCANVAS vs PÁGINA DETALHES

| Aspecto | **Offcanvas (Kanban)** | **Página Detalhes** |
|---------|----------------------|---------------------|
| **Acesso** | Clicar no card → botão `...` | Link direto `/admin/solicitacoes/{id}` |
| **Horários** | ☑️ **Checkboxes múltiplos** | 🔘 **Botões individuais** (um por horário) |
| **Salvamento** | Um botão "Salvar Alterações" salva **tudo** | Botão "Confirmar horário" por horário |
| **Status** | Muda para "Serviço Agendado" automaticamente | Muda para "Serviço Agendado" ao confirmar |
| **Interface** | Modal lateral (overlay) | Página completa |
| **Atualização** | Recarrega a página Kanban | Recarrega a própria página |

### ✅ Funcionalidades Iguais

Ambos atualizam:
- ✅ `data_agendamento`
- ✅ `horario_agendamento`
- ✅ `horario_confirmado = 1`
- ✅ `horario_confirmado_raw`
- ✅ `confirmed_schedules` (JSON)
- ✅ Status para "Serviço Agendado"

---

## 🗄️ ESTRUTURA DO BANCO DE DADOS

### Tabela: `solicitacoes`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | INT | ID único |
| `horarios_opcoes` | JSON/TEXT | Lista de horários disponíveis (array de timestamps) |
| `data_agendamento` | DATE | Data confirmada (última) |
| `horario_agendamento` | TIME | Horário confirmado (último) |
| `horario_confirmado` | TINYINT(1) | Flag: 1 = confirmado, 0 = não confirmado |
| `horario_confirmado_raw` | VARCHAR(255) | Horário original confirmado (texto) |
| `confirmed_schedules` | JSON | Array com todos os horários confirmados |
| `descricao_problema` | TEXT | Descrição completa (pode incluir "Disponibilidade: ...") |
| `descricao_card` | TEXT | Cópia da descrição para card do Kanban |
| `status_id` | INT | FK para tabela `status` |
| `observacoes` | TEXT | Observações da seguradora |
| `precisa_reembolso` | TINYINT(1) | Flag: 1 = precisa, 0 = não precisa |
| `valor_reembolso` | DECIMAL(10,2) | Valor do reembolso |
| `protocolo_seguradora` | VARCHAR(255) | Protocolo da seguradora |

### Exemplo de `horarios_opcoes`:
```json
[
  "2025-11-26T08:00:00",
  "2025-11-26T11:00:00",
  "2025-11-26T14:00:00"
]
```

### Exemplo de `confirmed_schedules`:
```json
[
  {
    "date": "2025-11-26",
    "time": "08:00-11:00",
    "raw": "26/11/2025 - 08:00-11:00",
    "source": "operator",
    "confirmed_at": "2025-11-03T14:30:00Z"
  }
]
```

---

## 🔗 FLUXO COMPLETO: Confirmação de Horários

### **Cenário 1: Via Offcanvas (Kanban)**

```
1. Usuário clica no card → Offcanvas abre
2. Usuário marca checkbox em "26/11/2025 - 08:00-11:00"
3. Usuário marca checkbox em "26/11/2025 - 11:00-14:00"
4. Usuário clica "Salvar Alterações"
5. JavaScript coleta:
   - observacoes
   - precisa_reembolso
   - valor_reembolso
   - protocolo_seguradora
   - schedules: [
       {date: "2025-11-26", time: "08:00-11:00", raw: "26/11/2025 - 08:00-11:00"},
       {date: "2025-11-26", time: "11:00-14:00", raw: "26/11/2025 - 11:00-14:00"}
     ]
6. POST /admin/solicitacoes/{id}/atualizar
7. Controller processa:
   - Salva observações, reembolso, protocolo
   - Processa schedules → confirmed_schedules
   - Último schedule vira agendamento principal
   - Atualiza data_agendamento, horario_agendamento
   - Marca horario_confirmado = 1
   - Muda status para "Serviço Agendado"
8. Retorna sucesso
9. Página recarrega → Card atualiza no Kanban
```

### **Cenário 2: Via Página Detalhes**

```
1. Usuário acessa /admin/solicitacoes/{id}
2. Usuário vê lista de horários
3. Usuário clica "Confirmar horário" em "26/11/2025 - 08:00"
4. JavaScript faz:
   confirmarHorario(id, "2025-11-26T08:00:00")
5. POST /admin/solicitacoes/{id}/confirmar-horario
6. Controller processa:
   - Extrai data e hora do timestamp
   - Atualiza data_agendamento, horario_agendamento
   - Marca horario_confirmado = 1
   - Salva horario_confirmado_raw
   - Atualiza descricao_problema com "Disponibilidade: ..."
   - Muda status para "Serviço Agendado"
   - Registra histórico
   - Envia WhatsApp
7. Retorna sucesso
8. Página recarrega → Horário aparece como confirmado
```

---

## 📝 RESUMO DOS ENDPOINTS

| Método | Rota | Controller | Descrição |
|--------|------|-----------|-----------|
| `GET` | `/admin/kanban` | `DashboardController@kanban` | Exibe Kanban Board |
| `POST` | `/admin/kanban/mover` | `DashboardController@moverCard` | Move card entre colunas |
| `GET` | `/admin/solicitacoes/{id}/api` | `SolicitacoesController@api` | Retorna dados JSON da solicitação |
| `POST` | `/admin/solicitacoes/{id}/atualizar` | `SolicitacoesController@atualizarDetalhes` | Salva alterações (incluindo schedules) |
| `GET` | `/admin/solicitacoes/{id}` | `SolicitacoesController@show` | Exibe página de detalhes |
| `POST` | `/admin/solicitacoes/{id}/confirmar-horario` | `SolicitacoesController@confirmarHorario` | Confirma um horário individual |
| `POST` | `/admin/solicitacoes/{id}/desconfirmar-horario` | `SolicitacoesController@desconfirmarHorario` | Desconfirma o horário atual |
| `POST` | `/admin/solicitacoes/{id}/horarios/bulk` | `SolicitacoesController@confirmarHorariosBulk` | Confirma múltiplos horários (não usado atualmente) |

---

## 🎯 DIFERENÇAS PRINCIPAIS

### **Offcanvas:**
- ✅ **Checkboxes múltiplos** - Pode marcar vários horários de uma vez
- ✅ **Salvamento em lote** - Um botão salva tudo (observações + horários + reembolso + protocolo)
- ✅ **Integração no Kanban** - Não precisa sair do Kanban

### **Página Detalhes:**
- ✅ **Botões individuais** - Um botão por horário
- ✅ **Confirmação granular** - Confirma um horário por vez
- ✅ **Visualização completa** - Página dedicada com todas as informações

---

## 🔍 OBSERVAÇÕES IMPORTANTES

1. **Ambos funcionam igualmente** - Ambos atualizam os mesmos campos no banco de dados
2. **Offcanvas permite seleção múltipla** - Mais eficiente para confirmar vários horários
3. **Página Detalhes é mais granular** - Melhor para revisar e confirmar um horário específico
4. **Status sempre muda para "Serviço Agendado"** quando um horário é confirmado
5. **`confirmed_schedules`** armazena histórico de todas as confirmações
6. **`horario_confirmado_raw`** guarda o texto original do horário confirmado para comparação

---

## ✅ CONCLUSÃO

O sistema oferece **duas formas complementares** de gerenciar solicitações:

- **Offcanvas** = **Rapidez** (checkboxes múltiplos, salvamento em lote)
- **Página Detalhes** = **Precisão** (confirmação individual, visualização completa)

Ambos atualizam os mesmos campos e mantêm a consistência dos dados.

---

## 🚀 MELHORIAS FUTURAS

Para melhorias sugeridas baseadas em boas práticas de sistemas similares, consulte o documento:
**`PLANO_MELHORIAS_SISTEMA.md`**

**Principais melhorias sugeridas:**
1. ✅ **Optimistic UI** - Feedback visual imediato no drag-and-drop
2. ✅ **Confirmação antes de fechar** - Prevenir perda de dados não salvos
3. ✅ **Loading states granulares** - Feedback específico por operação
4. ✅ **RPC/Stored Procedures** - Validações seguras no banco
5. ✅ **Debounce em updates** - Reduzir requisições múltiplas
6. ✅ **Logs estruturados** - Facilitar debugging
7. ✅ **Retry automático** - Resiliência em falhas de rede
8. ✅ **Cache de dados** - Melhorar performance

---

**📄 Documentação relacionada:**
- `PLANO_MELHORIAS_SISTEMA.md` - Plano detalhado de melhorias
- `WHATSAPP_INTEGRATION.md` - Documentação de integração WhatsApp

