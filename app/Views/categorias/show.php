<?php
/**
 * View: Detalhes da Categoria
 */
$title = $categoria['nome'];
$currentPage = 'categorias';
$pageTitle = $categoria['nome'];
ob_start();
?>

<!-- Breadcrumb -->
<nav class="flex mb-6" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-4">
        <li>
            <div>
                <a href="<?= url('admin/categorias') ?>" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-tags"></i>
                    <span class="sr-only">Categorias</span>
                </a>
            </div>
        </li>
        <li>
            <div class="flex items-center">
                <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                <span class="text-sm font-medium text-gray-500"><?= htmlspecialchars($categoria['nome']) ?></span>
            </div>
        </li>
    </ol>
</nav>

<!-- Header -->
<div class="flex items-center justify-between mb-6">
    <div class="flex items-center space-x-4">
        <div class="w-16 h-16 rounded-lg flex items-center justify-center text-white text-2xl"
             style="background-color: <?= $categoria['cor'] ?>">
            <i class="<?= $categoria['icone'] ?>"></i>
        </div>
        <div>
            <h2 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($categoria['nome']) ?></h2>
            <div class="flex items-center space-x-2 mt-1">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                    <?= $categoria['status'] === 'ATIVA' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                    <?= $categoria['status'] ?>
                </span>
                <?php if (isset($categoria['tipo_imovel'])): ?>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        <?= $categoria['tipo_imovel'] === 'RESIDENCIAL' ? 'bg-blue-100 text-blue-800' : 
                            ($categoria['tipo_imovel'] === 'COMERCIAL' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800') ?>">
                        <i class="fas fa-<?= $categoria['tipo_imovel'] === 'RESIDENCIAL' ? 'home' : 
                            ($categoria['tipo_imovel'] === 'COMERCIAL' ? 'building' : 'th') ?> mr-1"></i>
                        <?= $categoria['tipo_imovel'] === 'RESIDENCIAL' ? 'Residencial' : 
                            ($categoria['tipo_imovel'] === 'COMERCIAL' ? 'Comercial' : 'Ambos') ?>
                    </span>
                <?php endif; ?>
                <span class="text-sm text-gray-500">
                    Ordem: <?= $categoria['ordem'] ?>
                </span>
            </div>
        </div>
    </div>
    <div class="flex items-center space-x-3">
        <a href="<?= url('admin/categorias/' . $categoria['id'] . '/edit') ?>" 
           class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <i class="fas fa-edit mr-2"></i>
            Editar
        </a>
        <button onclick="toggleStatus(<?= $categoria['id'] ?>, '<?= $categoria['status'] ?>')" 
                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <i class="fas fa-toggle-<?= $categoria['status'] === 'ATIVA' ? 'on' : 'off' ?> mr-2"></i>
            <?= $categoria['status'] === 'ATIVA' ? 'Desativar' : 'Ativar' ?>
        </button>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Informações Principais -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Descrição -->
        <?php if ($categoria['descricao']): ?>
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Descrição</h3>
            <p class="text-sm text-gray-900"><?= htmlspecialchars($categoria['descricao'] ?? '') ?></p>
        </div>
        <?php endif; ?>
        
        <!-- Condições Gerais -->
        <?php if (!empty($categoria['condicoes_gerais'])): ?>
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">
                <i class="fas fa-file-contract mr-2 text-gray-600"></i>
                Condições Gerais
            </h3>
            <div class="text-sm text-gray-900 prose max-w-none">
                <?php
                $condicoes = $categoria['condicoes_gerais'];
                // Verificar se o conteúdo é HTML (começa com tag)
                if (preg_match('/^<[a-z][\s\S]*>$/i', trim($condicoes)) || strpos($condicoes, '<p>') !== false || strpos($condicoes, '<ul>') !== false || strpos($condicoes, '<ol>') !== false) {
                    // É HTML, renderizar diretamente
                    echo $condicoes;
                } else {
                    // É texto simples, converter quebras de linha para <br>
                    echo nl2br(htmlspecialchars($condicoes));
                }
                ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Subcategorias -->
        <div class="bg-white shadow rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Subcategorias</h3>
                <a href="<?= url('admin/categorias/' . $categoria['id'] . '/subcategorias/create') ?>" 
                   class="inline-flex items-center px-3 py-1 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-plus mr-1"></i>
                    Nova Subcategoria
                </a>
            </div>
            
            <?php if (empty($subcategorias)): ?>
                <div class="text-center py-8">
                    <i class="fas fa-list text-3xl text-gray-400 mb-2"></i>
                    <h4 class="text-sm font-medium text-gray-900 mb-1">Nenhuma subcategoria</h4>
                    <p class="text-xs text-gray-500 mb-4">Esta categoria ainda não possui subcategorias.</p>
                    <a href="<?= url('admin/categorias/' . $categoria['id'] . '/subcategorias/create') ?>" 
                       class="inline-flex items-center px-3 py-1 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                        <i class="fas fa-plus mr-1"></i>
                        Criar Primeira Subcategoria
                    </a>
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($subcategorias as $subcategoria): ?>
                        <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2">
                                    <h4 class="text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($subcategoria['nome']) ?>
                                    </h4>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                        <?= $subcategoria['status'] === 'ATIVA' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= $subcategoria['status'] ?>
                                    </span>
                                    <?php if (!empty($subcategoria['is_emergencial'])): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800" title="Subcategoria emergencial">
                                            <i class="fas fa-exclamation-triangle mr-1"></i>
                                            Emergencial
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($subcategoria['descricao']): ?>
                                    <p class="text-xs text-gray-500 mt-1">
                                        <?= htmlspecialchars($subcategoria['descricao']) ?>
                                    </p>
                                <?php endif; ?>
                                
                                <div class="flex items-center space-x-4 mt-2 text-xs text-gray-500">
                                    <?php if (isset($subcategoria['prazo_minimo'])): ?>
                                        <span>
                                            <i class="fas fa-calendar-day mr-1"></i>
                                            Prazo mínimo: <?= $subcategoria['prazo_minimo'] ?> dia(s)
                                        </span>
                                    <?php endif; ?>
                                    <span>
                                        <i class="fas fa-sort-numeric-up mr-1"></i>
                                        Ordem: <?= $subcategoria['ordem'] ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="flex items-center space-x-2">
                                <a href="<?= url('admin/categorias/' . $categoria['id'] . '/subcategorias/' . $subcategoria['id'] . '/edit') ?>" 
                                   class="inline-flex items-center px-2 py-1 border border-gray-300 rounded text-xs font-medium text-gray-700 bg-white hover:bg-gray-50">
                                    <i class="fas fa-edit mr-1"></i>
                                    Editar
                                </a>
                                
                                <button onclick="toggleSubcategoriaStatus(<?= $categoria['id'] ?>, <?= $subcategoria['id'] ?>, '<?= $subcategoria['status'] ?>')" 
                                        class="inline-flex items-center px-2 py-1 border border-gray-300 rounded text-xs font-medium text-gray-700 bg-white hover:bg-gray-50">
                                    <i class="fas fa-toggle-<?= $subcategoria['status'] === 'ATIVA' ? 'on' : 'off' ?> mr-1"></i>
                                    <?= $subcategoria['status'] === 'ATIVA' ? 'Desativar' : 'Ativar' ?>
                                </button>
                                
                                <button onclick="deleteSubcategoria(<?= $categoria['id'] ?>, <?= $subcategoria['id'] ?>, '<?= htmlspecialchars($subcategoria['nome']) ?>')" 
                                        class="inline-flex items-center px-2 py-1 border border-red-300 rounded text-xs font-medium text-red-700 bg-white hover:bg-red-50">
                                    <i class="fas fa-trash mr-1"></i>
                                    Excluir
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Informações do Sistema -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Informações do Sistema</h3>
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700">ID</label>
                    <p class="mt-1 text-sm text-gray-900"><?= $categoria['id'] ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Data de Criação</label>
                    <p class="mt-1 text-sm text-gray-900">
                        <?= date('d/m/Y H:i', strtotime($categoria['created_at'])) ?>
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Última Atualização</label>
                    <p class="mt-1 text-sm text-gray-900">
                        <?= date('d/m/Y H:i', strtotime($categoria['updated_at'])) ?>
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Total de Subcategorias</label>
                    <p class="mt-1 text-sm text-gray-900"><?= count($subcategorias) ?></p>
                </div>
            </div>
        </div>
        
        <!-- Ações Rápidas -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Ações Rápidas</h3>
            <div class="space-y-3">
                <a href="<?= url('admin/categorias/' . $categoria['id'] . '/edit') ?>" 
                   class="w-full inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-edit mr-2"></i>
                    Editar Categoria
                </a>
                
                <a href="<?= url('admin/categorias/' . $categoria['id'] . '/subcategorias/create') ?>" 
                   class="w-full inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-plus mr-2"></i>
                    Nova Subcategoria
                </a>
                
                <button onclick="deleteCategoria(<?= $categoria['id'] ?>, '<?= htmlspecialchars($categoria['nome']) ?>')" 
                        class="w-full inline-flex items-center justify-center px-4 py-2 border border-red-300 rounded-md shadow-sm text-sm font-medium text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    <i class="fas fa-trash mr-2"></i>
                    Excluir Categoria
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmação -->
<div id="confirm-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <i class="fas fa-exclamation-triangle text-red-600"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mt-2" id="modal-title">Confirmar Exclusão</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500" id="modal-message">
                    Tem certeza que deseja excluir esta categoria?
                </p>
                <div id="modal-confirm-wrapper" class="mt-4 hidden text-left">
                    <label for="modal-confirm-input" class="block text-sm font-medium text-gray-700 mb-2">
                        Digite <span class="font-semibold text-red-600">EXCLUIR</span> para confirmar
                    </label>
                    <input type="text" id="modal-confirm-input"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500"
                           placeholder="EXCLUIR" autocomplete="off">
                    <p class="mt-2 text-xs text-gray-500">
                        Esta ação removerá a categoria e todas as subcategorias vinculadas.
                    </p>
                </div>
            </div>
            <div class="items-center px-4 py-3">
                <button id="confirm-button" 
                        class="px-4 py-2 bg-red-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-300">
                    Confirmar
                </button>
                <button onclick="closeModal()" 
                        class="mt-3 px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const confirmWrapper = document.getElementById('modal-confirm-wrapper');
const confirmInput = document.getElementById('modal-confirm-input');

function showConfirmationInput() {
    confirmWrapper.classList.remove('hidden');
    confirmInput.value = '';
    setTimeout(() => confirmInput.focus(), 50);
}

function hideConfirmationInput() {
    confirmWrapper.classList.add('hidden');
    confirmInput.value = '';
}

function toggleStatus(id, currentStatus) {
    const newStatus = currentStatus === 'ATIVA' ? 'INATIVA' : 'ATIVA';
    const action = newStatus === 'ATIVA' ? 'ativar' : 'desativar';
    
    if (confirm(`Tem certeza que deseja ${action} esta categoria?`)) {
        fetch(`<?= url('admin/categorias') ?>/${id}/toggle-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erro: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao atualizar status');
        });
    }
}

function toggleSubcategoriaStatus(categoriaId, subcategoriaId, currentStatus) {
    const newStatus = currentStatus === 'ATIVA' ? 'INATIVA' : 'ATIVA';
    const action = newStatus === 'ATIVA' ? 'ativar' : 'desativar';
    
    if (confirm(`Tem certeza que deseja ${action} esta subcategoria?`)) {
        fetch(`<?= url('admin/categorias') ?>/${categoriaId}/subcategorias/${subcategoriaId}/toggle-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erro: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao atualizar status');
        });
    }
}

function deleteCategoria(id, nome) {
    document.getElementById('modal-title').textContent = 'Confirmar Exclusão';
    document.getElementById('modal-message').textContent = `Tem certeza que deseja excluir a categoria "${nome}"? Esta ação não pode ser desfeita.`;
    showConfirmationInput();
    
    document.getElementById('confirm-button').onclick = function() {
        const typed = confirmInput.value.trim().toUpperCase();
        if (typed !== 'EXCLUIR') {
            alert('Digite "EXCLUIR" para confirmar a exclusão.');
            confirmInput.focus();
            return;
        }

        fetch(`<?= url('admin/categorias') ?>/${id}/delete`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ confirmacao: typed })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeModal();
                window.location.href = '<?= url('admin/categorias') ?>';
            } else {
                alert('Erro: ' + data.error);
                closeModal();
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao excluir categoria');
            closeModal();
        });
    };
    
    document.getElementById('confirm-modal').classList.remove('hidden');
}

function deleteSubcategoria(categoriaId, subcategoriaId, nome) {
    document.getElementById('modal-title').textContent = 'Confirmar Exclusão';
    document.getElementById('modal-message').textContent = `Tem certeza que deseja excluir a subcategoria "${nome}"? Esta ação não pode ser desfeita.`;
    hideConfirmationInput();
    
    document.getElementById('confirm-button').onclick = function() {
        fetch(`<?= url('admin/categorias') ?>/${categoriaId}/subcategorias/${subcategoriaId}/delete`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erro: ' + data.error);
                closeModal();
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao excluir subcategoria');
            closeModal();
        });
    };
    
    document.getElementById('confirm-modal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('confirm-modal').classList.add('hidden');
    hideConfirmationInput();
}
</script>

<?php
$content = ob_get_clean();
include 'app/Views/layouts/admin.php';
?>
