<?php
$title = 'Gerenciar Status';
$currentPage = 'status';
$pageTitle = 'Configurações - Status do Kanban';
ob_start();
?>

<!-- Token CSRF (oculto) -->
<?= \App\Core\View::csrfField() ?>

<!-- Barra de Ações -->
<div class="mb-6 flex justify-between items-center">
    <p class="text-gray-600">Configure os status do Kanban</p>
    <button onclick="abrirOffcanvasNovoStatus()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
        <i class="fas fa-plus mr-2"></i>
        Novo Status
    </button>
</div>

<!-- Lista de Status -->
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Gerenciar Status</h3>
        
        <div class="space-y-3" id="statusList">
            <?php if (empty($status)): ?>
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-inbox text-4xl mb-3 block text-gray-300"></i>
                Nenhum status cadastrado
            </div>
            <?php else: ?>
            <?php foreach ($status as $item): ?>
            <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50" data-id="<?= $item['id'] ?>">
                <div class="flex items-center">
                    <div class="mr-3 cursor-move">
                        <i class="fas fa-grip-vertical text-gray-400"></i>
                    </div>
                    <span class="status-badge px-3 py-1 rounded-full text-sm font-medium" style="background-color: <?= $item['cor'] ?>20; color: <?= $item['cor'] ?>">
                        <?= htmlspecialchars($item['nome']) ?>
                    </span>
                    <?php if (isset($item['visivel_kanban']) && $item['visivel_kanban']): ?>
                    <span class="ml-2 text-xs text-gray-500">
                        <i class="fas fa-eye"></i> Visível no Kanban
                    </span>
                    <?php endif; ?>
                </div>
                
                <div class="flex gap-2">
                    <button onclick="abrirOffcanvasEditarStatus(<?= $item['id'] ?>)" 
                            class="text-blue-600 hover:text-blue-900" 
                            title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button 
                        onclick="excluirStatus(<?= $item['id'] ?>)" 
                        class="text-red-600 hover:text-red-900" 
                        title="Excluir">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Offcanvas para Criar/Editar Status -->
<div id="offcanvasStatus" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 transition-opacity" onclick="fecharOffcanvasStatus()"></div>
    <div id="offcanvasStatusPanel" class="fixed right-0 top-0 h-full w-full md:w-[90%] lg:w-[600px] bg-gray-50 shadow-xl transform translate-x-full transition-transform duration-300 ease-in-out overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 z-10">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class="fas fa-tag text-gray-600"></i>
                    <h2 id="offcanvasStatusTitle" class="text-xl font-bold text-gray-900">Status</h2>
                </div>
                <button onclick="fecharOffcanvasStatus()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        <div id="offcanvasStatusContent" class="p-6">
            <div id="loadingStatus" class="flex items-center justify-center py-12">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin text-4xl text-blue-600 mb-4"></i>
                    <p class="text-gray-600">Carregando...</p>
                </div>
            </div>
            <div id="formStatus" class="hidden"></div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'app/Views/layouts/admin.php';
?>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
// Inicializar Sortable para reordenação
const statusList = document.getElementById('statusList');
if (statusList && statusList.children.length > 0) {
    new Sortable(statusList, {
        animation: 150,
        handle: '.fa-grip-vertical',
        onEnd: function(evt) {
            const ordens = [];
            statusList.querySelectorAll('[data-id]').forEach((item, index) => {
                ordens.push({
                    id: item.getAttribute('data-id'),
                    ordem: index + 1
                });
            });
            
            console.log('Enviando ordens:', ordens);
            const payload = { ordens: ordens };
            console.log('Payload:', JSON.stringify(payload));
            
            fetch('<?= url('admin/status/reordenar') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(payload)
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.text();
            })
            .then(text => {
                console.log('Response text:', text);
                try {
                    const data = JSON.parse(text);
                    console.log('Response JSON:', data);
                    if (!data.success) {
                        alert(data.error || 'Erro ao atualizar ordem');
                        location.reload();
                    }
                } catch (e) {
                    console.error('Erro ao parsear JSON:', e);
                    console.error('Texto recebido:', text);
                    alert('Erro ao atualizar ordem: resposta inválida do servidor');
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erro ao atualizar ordem');
                location.reload();
            });
        }
    });
}

function excluirStatus(statusId) {
    if (confirm('Deseja realmente excluir este status? Esta ação não pode ser desfeita.')) {
        fetch(`<?= url('admin/status/') ?>${statusId}/delete`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert(data.error || 'Erro ao excluir status');
            }
        })
        .catch(error => {
            alert('Erro ao excluir status');
            console.error('Error:', error);
        });
    }
}

// ========== FUNÇÕES DO OFFCANVAS ==========
let modoOffcanvasStatus = null; // 'criar', 'editar'
let statusEditandoId = null;

function abrirOffcanvasNovoStatus() {
    modoOffcanvasStatus = 'criar';
    statusEditandoId = null;
    
    const offcanvas = document.getElementById('offcanvasStatus');
    const panel = document.getElementById('offcanvasStatusPanel');
    const loading = document.getElementById('loadingStatus');
    const form = document.getElementById('formStatus');
    const title = document.getElementById('offcanvasStatusTitle');
    
    title.textContent = 'Novo Status';
    
    offcanvas.classList.remove('hidden');
    setTimeout(() => panel.classList.remove('translate-x-full'), 10);
    
    loading.classList.add('hidden');
    form.classList.remove('hidden');
    
    renderizarFormularioCriar();
}

function abrirOffcanvasEditarStatus(id) {
    modoOffcanvasStatus = 'editar';
    statusEditandoId = id;
    
    const offcanvas = document.getElementById('offcanvasStatus');
    const panel = document.getElementById('offcanvasStatusPanel');
    const loading = document.getElementById('loadingStatus');
    const form = document.getElementById('formStatus');
    const title = document.getElementById('offcanvasStatusTitle');
    
    title.textContent = 'Editar Status';
    
    offcanvas.classList.remove('hidden');
    setTimeout(() => panel.classList.remove('translate-x-full'), 10);
    
    loading.classList.remove('hidden');
    form.classList.add('hidden');
    
    fetch(`<?= url('admin/status/') ?>${id}/api`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderizarFormularioEditar(data.status);
            } else {
                form.innerHTML = `
                    <div class="text-center py-12">
                        <i class="fas fa-exclamation-triangle text-4xl text-red-600 mb-4"></i>
                        <p class="text-gray-600">${data.message || 'Erro ao carregar dados do status'}</p>
                    </div>
                `;
            }
            loading.classList.add('hidden');
            form.classList.remove('hidden');
        })
        .catch(error => {
            console.error('Erro:', error);
            form.innerHTML = `
                <div class="text-center py-12">
                    <i class="fas fa-exclamation-triangle text-4xl text-red-600 mb-4"></i>
                    <p class="text-gray-600">Erro ao carregar dados do status</p>
                </div>
            `;
            loading.classList.add('hidden');
            form.classList.remove('hidden');
        });
}

function fecharOffcanvasStatus() {
    const offcanvas = document.getElementById('offcanvasStatus');
    const panel = document.getElementById('offcanvasStatusPanel');
    
    panel.classList.add('translate-x-full');
    setTimeout(() => {
        offcanvas.classList.add('hidden');
        modoOffcanvasStatus = null;
        statusEditandoId = null;
        document.getElementById('formStatus').innerHTML = '';
    }, 300);
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function renderizarFormularioCriar() {
    const form = document.getElementById('formStatus');
    const csrfToken = document.querySelector('input[name="_token"]')?.value || '';
    
    form.innerHTML = `
        <form id="formCriarStatus" onsubmit="salvarCriacao(event)">
            <input type="hidden" name="_token" value="${csrfToken}">
            
            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Nome do Status <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="nome" 
                        id="nome-create"
                        required
                        placeholder="Ex: Em Análise"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Cor <span class="text-red-500">*</span>
                    </label>
                    <div class="flex gap-3">
                        <input 
                            type="color" 
                            name="cor" 
                            id="cor-create"
                            value="#3B82F6"
                            required
                            class="h-12 w-20 border-2 border-gray-300 rounded-md cursor-pointer"
                            onchange="atualizarPreviewCor(this.value)"
                        >
                        <input 
                            type="text" 
                            id="corHex-create"
                            value="#3B82F6"
                            readonly
                            class="flex-1 px-3 py-2 border border-gray-300 rounded-md bg-gray-50 font-mono text-sm"
                        >
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Selecione a cor que representará este status no sistema</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Ordem
                    </label>
                    <input 
                        type="number" 
                        name="ordem" 
                        id="ordem-create"
                        min="1"
                        placeholder="A ordem será definida automaticamente se não informada"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                    <p class="text-xs text-gray-500 mt-1">A ordem em que o status aparecerá no Kanban</p>
                </div>

                <div>
                    <label class="flex items-center">
                        <input 
                            type="checkbox" 
                            name="visivel_kanban" 
                            id="visivel_kanban-create"
                            value="1"
                            checked
                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        >
                        <span class="ml-2 text-sm text-gray-700">Visível no Kanban</span>
                    </label>
                    <p class="text-xs text-gray-500 mt-1 ml-6">Se marcado, este status aparecerá como uma coluna no Kanban</p>
                </div>

                <!-- Preview -->
                <div id="statusPreview" class="p-4 bg-gray-50 rounded-lg">
                    <p class="text-sm text-gray-600 mb-2">Preview:</p>
                    <span id="previewBadge" class="status-badge inline-flex items-center px-3 py-1 rounded-full text-sm font-medium">
                        Status de Exemplo
                    </span>
                </div>
            </div>

            <!-- Botões -->
            <div class="flex justify-end gap-3 pt-6 mt-6 border-t border-gray-200">
                <button type="button" onclick="fecharOffcanvasStatus()" 
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancelar
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>
                    Criar Status
                </button>
            </div>
        </form>
    `;
    
    // Inicializar preview
    atualizarPreviewCor('#3B82F6');
    
    // Atualizar preview ao mudar nome
    document.getElementById('nome-create').addEventListener('input', function(e) {
        const nome = e.target.value || 'Status de Exemplo';
        document.getElementById('previewBadge').textContent = nome;
    });
}

function renderizarFormularioEditar(status) {
    const form = document.getElementById('formStatus');
    const csrfToken = document.querySelector('input[name="_token"]')?.value || '';
    
    form.innerHTML = `
        <form id="formEditarStatus" onsubmit="salvarEdicao(event)">
            <input type="hidden" name="_token" value="${csrfToken}">
            <input type="hidden" name="_method" value="PUT">
            
            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Nome do Status <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="nome" 
                        id="nome-edit"
                        value="${escapeHtml(status.nome || '')}"
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        oninput="atualizarPreviewNome(this.value)"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Cor <span class="text-red-500">*</span>
                    </label>
                    <div class="flex gap-3">
                        <input 
                            type="color" 
                            name="cor" 
                            id="cor-edit"
                            value="${escapeHtml(status.cor || '#3B82F6')}"
                            required
                            class="h-12 w-20 border-2 border-gray-300 rounded-md cursor-pointer"
                            onchange="atualizarPreviewCor(this.value)"
                        >
                        <input 
                            type="text" 
                            id="corHex-edit"
                            value="${escapeHtml(status.cor || '#3B82F6')}"
                            readonly
                            class="flex-1 px-3 py-2 border border-gray-300 rounded-md bg-gray-50 font-mono text-sm"
                        >
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Ordem <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="number" 
                        name="ordem" 
                        id="ordem-edit"
                        value="${escapeHtml(status.ordem || '')}"
                        required
                        min="1"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                </div>

                <div>
                    <label class="flex items-center">
                        <input 
                            type="checkbox" 
                            name="visivel_kanban" 
                            id="visivel_kanban-edit"
                            value="1"
                            ${(status.visivel_kanban == 1 || status.visivel_kanban === true) ? 'checked' : ''}
                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        >
                        <span class="ml-2 text-sm text-gray-700">Visível no Kanban</span>
                    </label>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Status <span class="text-red-500">*</span>
                    </label>
                    <select 
                        name="status" 
                        id="status-edit"
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                        <option value="ATIVO" ${(status.status || 'ATIVO') === 'ATIVO' ? 'selected' : ''}>Ativo</option>
                        <option value="INATIVO" ${status.status === 'INATIVO' ? 'selected' : ''}>Inativo</option>
                    </select>
                </div>

                <!-- Preview -->
                <div id="statusPreview" class="p-4 bg-gray-50 rounded-lg">
                    <p class="text-sm text-gray-600 mb-2">Preview:</p>
                    <span id="previewBadge" class="status-badge inline-flex items-center px-3 py-1 rounded-full text-sm font-medium">
                        ${escapeHtml(status.nome || 'Status de Exemplo')}
                    </span>
                </div>
            </div>

            <!-- Botões -->
            <div class="flex justify-end gap-3 pt-6 mt-6 border-t border-gray-200">
                <button type="button" onclick="fecharOffcanvasStatus()" 
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancelar
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>
                    Salvar Alterações
                </button>
            </div>
        </form>
    `;
    
    // Inicializar preview
    atualizarPreviewCor(status.cor || '#3B82F6');
}

function atualizarPreviewCor(cor) {
    const badge = document.getElementById('previewBadge');
    const corHexInput = document.getElementById('corHex-create') || document.getElementById('corHex-edit');
    
    if (badge) {
        badge.style.backgroundColor = cor + '20';
        badge.style.color = cor;
    }
    
    if (corHexInput) {
        corHexInput.value = cor;
    }
}

function atualizarPreviewNome(nome) {
    const badge = document.getElementById('previewBadge');
    if (badge) {
        badge.textContent = nome || 'Status de Exemplo';
    }
}

function salvarCriacao(event) {
    event.preventDefault();
    
    const form = document.getElementById('formCriarStatus');
    const formData = new FormData(form);
    
    const button = form.querySelector('button[type="submit"]');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Criando...';
    button.disabled = true;
    
    fetch('<?= url('admin/status') ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => {
        if (response.headers.get('content-type')?.includes('application/json')) {
            return response.json();
        }
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch {
                return { success: false, message: 'Resposta inválida do servidor' };
            }
        });
    })
    .then(data => {
        if (data.success) {
            alert('Status criado com sucesso!');
            location.reload();
        } else {
            let errorMsg = data.message || data.error || 'Erro ao criar status';
            if (data.errors) {
                errorMsg += '\n\n' + Object.values(data.errors).join('\n');
            }
            alert('Erro: ' + errorMsg);
            button.innerHTML = originalText;
            button.disabled = false;
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao criar status');
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

function salvarEdicao(event) {
    event.preventDefault();
    
    const form = document.getElementById('formEditarStatus');
    const formData = new FormData(form);
    
    const button = form.querySelector('button[type="submit"]');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Salvando...';
    button.disabled = true;
    
    fetch(`<?= url('admin/status/') ?>${statusEditandoId}`, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => {
        if (response.headers.get('content-type')?.includes('application/json')) {
            return response.json();
        }
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch {
                return { success: false, message: 'Resposta inválida do servidor' };
            }
        });
    })
    .then(data => {
        if (data.success) {
            alert('Status atualizado com sucesso!');
            location.reload();
        } else {
            let errorMsg = data.message || data.error || 'Erro ao atualizar status';
            if (data.errors) {
                errorMsg += '\n\n' + Object.values(data.errors).join('\n');
            }
            alert('Erro: ' + errorMsg);
            button.innerHTML = originalText;
            button.disabled = false;
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao salvar alterações');
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// Fechar offcanvas com ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && !document.getElementById('offcanvasStatus').classList.contains('hidden')) {
        fecharOffcanvasStatus();
    }
});
</script>

