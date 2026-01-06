<?php
$title = 'Gerenciar Condições';
$currentPage = 'condicoes';
$pageTitle = 'Configurações - Condições';
ob_start();
?>

<!-- Token CSRF (oculto) -->
<?= \App\Core\View::csrfField() ?>

<!-- Barra de Ações -->
<div class="mb-6 flex justify-between items-center">
    <p class="text-gray-600">Configure as condições das solicitações</p>
    <button onclick="abrirOffcanvasNovaCondicao()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
        <i class="fas fa-plus mr-2"></i>
        Nova Condição
    </button>
</div>

<!-- Lista de Condições -->
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Gerenciar Condições</h3>
        
        <div class="space-y-3" id="condicoesList">
            <?php if (empty($condicoes)): ?>
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-inbox text-4xl mb-3 block text-gray-300"></i>
                Nenhuma condição cadastrada
            </div>
            <?php else: ?>
            <?php foreach ($condicoes as $item): ?>
            <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50" data-id="<?= $item['id'] ?>">
                <div class="flex items-center">
                    <div class="mr-3 cursor-move">
                        <i class="fas fa-grip-vertical text-gray-400"></i>
                    </div>
                    <span class="condicao-badge px-3 py-1 rounded-full text-sm font-medium" style="background-color: <?= $item['cor'] ?>20; color: <?= $item['cor'] ?>">
                        <?= htmlspecialchars($item['nome']) ?>
                    </span>
                    <?php if (isset($item['status']) && $item['status'] === 'ATIVO'): ?>
                    <span class="ml-2 text-xs text-gray-500">
                        <i class="fas fa-check-circle"></i> Ativo
                    </span>
                    <?php endif; ?>
                </div>
                
                <div class="flex gap-2">
                    <button onclick="abrirOffcanvasEditarCondicao(<?= $item['id'] ?>)" 
                            class="text-blue-600 hover:text-blue-900" 
                            title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button 
                        onclick="excluirCondicao(<?= $item['id'] ?>)" 
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

<!-- Offcanvas para Criar/Editar Condição -->
<div id="offcanvasCondicao" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 transition-opacity" onclick="fecharOffcanvasCondicao()"></div>
    <div id="offcanvasCondicaoPanel" class="fixed right-0 top-0 h-full w-full md:w-[90%] lg:w-[600px] bg-gray-50 shadow-xl transform translate-x-full transition-transform duration-300 ease-in-out overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 z-10">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class="fas fa-tag text-gray-600"></i>
                    <h2 id="offcanvasCondicaoTitle" class="text-xl font-bold text-gray-900">Condição</h2>
                </div>
                <button onclick="fecharOffcanvasCondicao()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        <div id="offcanvasCondicaoContent" class="p-6">
            <div id="loadingCondicao" class="flex items-center justify-center py-12">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin text-4xl text-blue-600 mb-4"></i>
                    <p class="text-gray-600">Carregando...</p>
                </div>
            </div>
            <div id="formCondicao" class="hidden"></div>
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
const condicoesList = document.getElementById('condicoesList');
if (condicoesList && condicoesList.children.length > 0) {
    new Sortable(condicoesList, {
        animation: 150,
        handle: '.fa-grip-vertical',
        onEnd: function(evt) {
            const ordens = [];
            condicoesList.querySelectorAll('[data-id]').forEach((item, index) => {
                ordens.push({
                    id: item.getAttribute('data-id'),
                    ordem: index + 1
                });
            });
            
            fetch('<?= url('admin/condicoes/reordenar') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ ordens: ordens })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert(data.error || 'Erro ao atualizar ordem');
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

function excluirCondicao(condicaoId) {
    if (confirm('Deseja realmente excluir esta condição? Esta ação não pode ser desfeita.')) {
        fetch(`<?= url('admin/condicoes/') ?>${condicaoId}/delete`, {
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
                alert(data.error || 'Erro ao excluir condição');
            }
        })
        .catch(error => {
            alert('Erro ao excluir condição');
            console.error('Error:', error);
        });
    }
}

// ========== FUNÇÕES DO OFFCANVAS ==========
let modoOffcanvasCondicao = null; // 'criar', 'editar'
let condicaoEditandoId = null;

function abrirOffcanvasNovaCondicao() {
    modoOffcanvasCondicao = 'criar';
    condicaoEditandoId = null;
    
    const offcanvas = document.getElementById('offcanvasCondicao');
    const panel = document.getElementById('offcanvasCondicaoPanel');
    const loading = document.getElementById('loadingCondicao');
    const form = document.getElementById('formCondicao');
    const title = document.getElementById('offcanvasCondicaoTitle');
    
    title.textContent = 'Nova Condição';
    
    offcanvas.classList.remove('hidden');
    setTimeout(() => panel.classList.remove('translate-x-full'), 10);
    
    loading.classList.add('hidden');
    form.classList.remove('hidden');
    
    renderizarFormularioCriar();
}

function abrirOffcanvasEditarCondicao(id) {
    modoOffcanvasCondicao = 'editar';
    condicaoEditandoId = id;
    
    const offcanvas = document.getElementById('offcanvasCondicao');
    const panel = document.getElementById('offcanvasCondicaoPanel');
    const loading = document.getElementById('loadingCondicao');
    const form = document.getElementById('formCondicao');
    const title = document.getElementById('offcanvasCondicaoTitle');
    
    title.textContent = 'Editar Condição';
    
    offcanvas.classList.remove('hidden');
    setTimeout(() => panel.classList.remove('translate-x-full'), 10);
    
    loading.classList.remove('hidden');
    form.classList.add('hidden');
    
    fetch(`<?= url('admin/condicoes/') ?>${id}/api`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderizarFormularioEditar(data.condicao);
            } else {
                form.innerHTML = `
                    <div class="text-center py-12">
                        <i class="fas fa-exclamation-triangle text-4xl text-red-600 mb-4"></i>
                        <p class="text-gray-600">${data.message || 'Erro ao carregar dados da condição'}</p>
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
                    <p class="text-gray-600">Erro ao carregar dados da condição</p>
                </div>
            `;
            loading.classList.add('hidden');
            form.classList.remove('hidden');
        });
}

function fecharOffcanvasCondicao() {
    const offcanvas = document.getElementById('offcanvasCondicao');
    const panel = document.getElementById('offcanvasCondicaoPanel');
    
    panel.classList.add('translate-x-full');
    setTimeout(() => {
        offcanvas.classList.add('hidden');
        modoOffcanvasCondicao = null;
        condicaoEditandoId = null;
        document.getElementById('formCondicao').innerHTML = '';
    }, 300);
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function renderizarFormularioCriar() {
    const form = document.getElementById('formCondicao');
    const csrfToken = document.querySelector('input[name="_token"]')?.value || '';
    
    form.innerHTML = `
        <form id="formCriarCondicao" onsubmit="salvarCriacao(event)">
            <input type="hidden" name="_token" value="${csrfToken}">
            
            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Nome da Condição <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="nome" 
                        id="nome-create"
                        required
                        placeholder="Ex: Em Análise"
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
                    <p class="text-xs text-gray-500 mt-1">Selecione a cor que representará esta condição no sistema</p>
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
                    <p class="text-xs text-gray-500 mt-1">A ordem em que a condição aparecerá</p>
                </div>

                <!-- Preview -->
                <div id="condicaoPreview" class="p-4 bg-gray-50 rounded-lg">
                    <p class="text-sm text-gray-600 mb-2">Preview:</p>
                    <span id="previewBadge" class="condicao-badge inline-flex items-center px-3 py-1 rounded-full text-sm font-medium">
                        Condição de Exemplo
                    </span>
                </div>
            </div>

            <!-- Botões -->
            <div class="flex justify-end gap-3 pt-6 mt-6 border-t border-gray-200">
                <button type="button" onclick="fecharOffcanvasCondicao()" 
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                    Cancelar
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>
                    Criar Condição
                </button>
            </div>
        </form>
    `;
    
    // Inicializar preview
    atualizarPreviewCor('#3B82F6');
}

function renderizarFormularioEditar(condicao) {
    const form = document.getElementById('formCondicao');
    const csrfToken = document.querySelector('input[name="_token"]')?.value || '';
    
    form.innerHTML = `
        <form id="formEditarCondicao" onsubmit="salvarEdicao(event)">
            <input type="hidden" name="_token" value="${csrfToken}">
            <input type="hidden" name="_method" value="PUT">
            
            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Nome da Condição <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="nome" 
                        id="nome-edit"
                        value="${escapeHtml(condicao.nome || '')}"
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
                            value="${escapeHtml(condicao.cor || '#3B82F6')}"
                            required
                            class="h-12 w-20 border-2 border-gray-300 rounded-md cursor-pointer"
                            onchange="atualizarPreviewCor(this.value)"
                        >
                        <input 
                            type="text" 
                            id="corHex-edit"
                            value="${escapeHtml(condicao.cor || '#3B82F6')}"
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
                        value="${escapeHtml(condicao.ordem || '')}"
                        required
                        min="1"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
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
                        <option value="ATIVO" ${(condicao.status || 'ATIVO') === 'ATIVO' ? 'selected' : ''}>Ativo</option>
                        <option value="INATIVO" ${condicao.status === 'INATIVO' ? 'selected' : ''}>Inativo</option>
                    </select>
                </div>

                <!-- Preview -->
                <div id="condicaoPreview" class="p-4 bg-gray-50 rounded-lg">
                    <p class="text-sm text-gray-600 mb-2">Preview:</p>
                    <span id="previewBadge" class="condicao-badge inline-flex items-center px-3 py-1 rounded-full text-sm font-medium">
                        ${escapeHtml(condicao.nome || 'Condição de Exemplo')}
                    </span>
                </div>
            </div>

            <!-- Botões -->
            <div class="flex justify-end gap-3 pt-6 mt-6 border-t border-gray-200">
                <button type="button" onclick="fecharOffcanvasCondicao()" 
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
    atualizarPreviewCor(condicao.cor || '#3B82F6');
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
        badge.textContent = nome || 'Condição de Exemplo';
    }
}

function salvarCriacao(event) {
    event.preventDefault();
    
    const form = document.getElementById('formCriarCondicao');
    const formData = new FormData(form);
    
    const button = form.querySelector('button[type="submit"]');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Criando...';
    button.disabled = true;
    
    fetch('<?= url('admin/condicoes') ?>', {
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
            alert('Condição criada com sucesso!');
            location.reload();
        } else {
            let errorMsg = data.message || data.error || 'Erro ao criar condição';
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
        alert('Erro ao criar condição');
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

function salvarEdicao(event) {
    event.preventDefault();
    
    const form = document.getElementById('formEditarCondicao');
    const formData = new FormData(form);
    
    const button = form.querySelector('button[type="submit"]');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Salvando...';
    button.disabled = true;
    
    fetch(`<?= url('admin/condicoes/') ?>${condicaoEditandoId}`, {
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
            alert('Condição atualizada com sucesso!');
            location.reload();
        } else {
            let errorMsg = data.message || data.error || 'Erro ao atualizar condição';
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
    if (e.key === 'Escape' && !document.getElementById('offcanvasCondicao').classList.contains('hidden')) {
        fecharOffcanvasCondicao();
    }
});
</script>

