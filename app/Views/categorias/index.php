<?php
/**
 * View: Lista de Categorias
 */
$title = 'Categorias';
$currentPage = 'categorias';
$pageTitle = 'Categorias';
ob_start();
?>

<!-- Header -->
<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Categorias</h2>
        <p class="text-sm text-gray-600">Gerencie as categorias e subcategorias de assistência</p>
    </div>
    <a href="<?= url('admin/categorias/create') ?>" 
       class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
        <i class="fas fa-plus mr-2"></i>
        Nova Categoria
    </a>
</div>

<!-- Lista de Categorias -->
<div class="bg-white shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Todas as Categorias</h3>
    </div>
    
    <?php if (empty($categorias)): ?>
        <div class="text-center py-12">
            <i class="fas fa-tags text-4xl text-gray-400 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Nenhuma categoria encontrada</h3>
            <p class="text-gray-500 mb-4">Comece criando sua primeira categoria de assistência.</p>
            <a href="<?= url('admin/categorias/create') ?>" 
               class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i>
                Criar Primeira Categoria
            </a>
        </div>
    <?php else: ?>
        <div class="divide-y divide-gray-200">
            <?php foreach ($categorias as $categoria): ?>
                <div class="p-6 hover:bg-gray-50 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <!-- Ícone -->
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 rounded-lg flex items-center justify-center text-white text-xl"
                                     style="background-color: <?= $categoria['cor'] ?>">
                                    <i class="<?= $categoria['icone'] ?>"></i>
                                </div>
                            </div>
                            
                            <!-- Informações -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center space-x-2">
                                    <h4 class="text-lg font-medium text-gray-900 truncate">
                                        <?= htmlspecialchars($categoria['nome']) ?>
                                    </h4>
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
                                </div>
                                
                                <?php if ($categoria['descricao']): ?>
                                    <p class="text-sm text-gray-500 mt-1">
                                        <?= htmlspecialchars($categoria['descricao'] ?? '') ?>
                                    </p>
                                <?php endif; ?>
                                
                                <div class="flex items-center space-x-4 mt-2 text-xs text-gray-500">
                                    <span>
                                        <i class="fas fa-list mr-1"></i>
                                        <?= $categoria['subcategorias_count'] ?> subcategorias
                                    </span>
                                    <span>
                                        <i class="fas fa-sort-numeric-up mr-1"></i>
                                        Ordem: <?= $categoria['ordem'] ?>
                                    </span>
                                    <span>
                                        <i class="fas fa-calendar mr-1"></i>
                                        Criada em <?= date('d/m/Y', strtotime($categoria['created_at'])) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Ações -->
                        <div class="flex items-center space-x-2">
                            <a href="<?= url('admin/categorias/' . $categoria['id']) ?>" 
                               class="inline-flex items-center px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-eye mr-1"></i>
                                Ver
                            </a>
                            
                            <a href="<?= url('admin/categorias/' . $categoria['id'] . '/edit') ?>" 
                               class="inline-flex items-center px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-edit mr-1"></i>
                                Editar
                            </a>
                            
                            <button onclick="toggleStatus(<?= $categoria['id'] ?>, '<?= $categoria['status'] ?>')" 
                                    class="inline-flex items-center px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-toggle-<?= $categoria['status'] === 'ATIVA' ? 'on' : 'off' ?> mr-1"></i>
                                <?= $categoria['status'] === 'ATIVA' ? 'Desativar' : 'Ativar' ?>
                            </button>
                            
                            <button onclick="deleteCategoria(<?= $categoria['id'] ?>, '<?= htmlspecialchars($categoria['nome']) ?>')" 
                                    class="inline-flex items-center px-3 py-1 border border-red-300 rounded-md text-sm font-medium text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                <i class="fas fa-trash mr-1"></i>
                                Excluir
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
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

function deleteCategoria(id, nome) {
    document.getElementById('modal-title').textContent = 'Confirmar Exclusão';
    document.getElementById('modal-message').textContent = `Tem certeza que deseja excluir a categoria "${nome}"? Esta ação não pode ser desfeita.`;
    confirmWrapper.classList.remove('hidden');
    confirmInput.value = '';
    setTimeout(() => confirmInput.focus(), 50);
    
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
                location.reload();
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

function closeModal() {
    document.getElementById('confirm-modal').classList.add('hidden');
    confirmWrapper.classList.add('hidden');
    confirmInput.value = '';
}
</script>

<?php
$content = ob_get_clean();
include 'app/Views/layouts/admin.php';
?>
