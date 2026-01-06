<?php
/**
 * View: Criar Nova Categoria
 */
$title = 'Nova Categoria';
$currentPage = 'categorias';
$pageTitle = 'Nova Categoria';
$iconOptions = [
    // Ferramentas e Manutenção
    'fas fa-tools',
    'fas fa-wrench',
    'fas fa-screwdriver',
    'fas fa-hammer',
    'fas fa-cog',
    'fas fa-cogs',
    'fas fa-toolbox',
    'fas fa-hard-hat',
    
    // Elétrica
    'fas fa-bolt',
    'fas fa-plug',
    'fas fa-lightbulb',
    'fas fa-power-off',
    'fas fa-microchip',
    
    // Hidráulica e Água
    'fas fa-water',
    'fas fa-shower',
    'fas fa-sink',
    'fas fa-faucet',
    'fas fa-swimming-pool',
    
    // Pintura e Acabamento
    'fas fa-paint-roller',
    'fas fa-paint-brush',
    'fas fa-palette',
    'fas fa-spray-can',
    
    // Construção e Estrutura
    'fas fa-home',
    'fas fa-building',
    'fas fa-warehouse',
    'fas fa-drafting-compass',
    'fas fa-ruler',
    'fas fa-ruler-combined',
    
    // Limpeza
    'fas fa-broom',
    'fas fa-soap',
    'fas fa-spray-can',
    'fas fa-vacuum',
    
    // Segurança e Acesso
    'fas fa-key',
    'fas fa-lock',
    'fas fa-lock-open',
    'fas fa-door-closed',
    'fas fa-door-open',
    'fas fa-shield-alt',
    'fas fa-fire-extinguisher',
    
    // Climatização
    'fas fa-sun',
    'fas fa-snowflake',
    'fas fa-wind',
    'fas fa-fan',
    'fas fa-thermometer-half',
    'fas fa-thermometer-quarter',
    
    // Jardim e Externo
    'fas fa-leaf',
    'fas fa-tree',
    'fas fa-seedling',
    'fas fa-mountain',
    'fas fa-fence',
    
    // Eletrônicos e Tecnologia
    'fas fa-tv',
    'fas fa-laptop',
    'fas fa-phone',
    'fas fa-wifi',
    'fas fa-satellite-dish',
    'fas fa-antenna',
    
    // Móveis e Decoração
    'fas fa-chair',
    'fas fa-couch',
    'fas fa-bed',
    'fas fa-table',
    'fas fa-cabinet-filing',
    
    // Vidros e Janelas
    'fas fa-window-maximize',
    'fas fa-window-restore',
    'fas fa-glass',
    
    // Outros Serviços
    'fas fa-bug',
    'fas fa-spider',
    'fas fa-dumpster',
    'fas fa-truck',
    'fas fa-box',
    'fas fa-cube',
    'fas fa-cubes',
    'fas fa-clipboard-check',
    'fas fa-tasks',
    'fas fa-check-circle',
    'fas fa-star',
    'fas fa-heart',
    'fas fa-thumbs-up',
    'fas fa-handshake',
    'fas fa-user-cog',
    'fas fa-user-tie',
    'fas fa-headset',
    'fas fa-phone-alt',
    'fas fa-envelope',
    'fas fa-calendar-alt',
    'fas fa-clock',
    'fas fa-stopwatch',
    'fas fa-exclamation-triangle',
    'fas fa-info-circle',
    'fas fa-question-circle',
    'fas fa-check-double',
    'fas fa-certificate',
    'fas fa-award',
    'fas fa-medal',
    'fas fa-trophy',
    'fas fa-gem',
    'fas fa-diamond'
];
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
                <span class="text-sm font-medium text-gray-500">Nova Categoria</span>
            </div>
        </li>
    </ol>
</nav>

<!-- Formulário -->
<div class="bg-white shadow rounded-lg">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Criar Nova Categoria</h3>
        <p class="text-sm text-gray-500">Preencha os dados da nova categoria de assistência</p>
    </div>
    
    <form method="POST" action="<?= url('admin/categorias') ?>" class="p-6 space-y-6">
        <?= \App\Core\View::csrfField() ?>
        
        <?php if (isset($error)): ?>
            <div class="bg-red-50 border border-red-200 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-400"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Erro</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <p><?= htmlspecialchars($error) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Nome -->
            <div class="md:col-span-2">
                <label for="nome" class="block text-sm font-medium text-gray-700">
                    Nome da Categoria <span class="text-red-500">*</span>
                </label>
                <div class="mt-1 relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-tag text-gray-400"></i>
                    </div>
                    <input type="text" 
                           name="nome" 
                           id="nome" 
                           value="<?= htmlspecialchars($data['nome'] ?? '') ?>"
                           class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all sm:text-sm <?= isset($errors['nome']) ? 'border-red-300 focus:ring-red-500 focus:border-red-500' : '' ?>"
                           placeholder="Ex: Elétrica, Hidráulica, Estrutural"
                           required>
                </div>
                <?php if (isset($errors['nome'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['nome']) ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Descrição -->
            <div class="md:col-span-2">
                <label for="descricao" class="block text-sm font-medium text-gray-700">
                    Descrição
                </label>
                <div class="mt-1 relative">
                    <div class="absolute top-3 left-3 pointer-events-none">
                        <i class="fas fa-align-left text-gray-400"></i>
                    </div>
                    <textarea name="descricao" 
                              id="descricao" 
                              rows="4"
                              class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all sm:text-sm resize-y <?= isset($errors['descricao']) ? 'border-red-300 focus:ring-red-500 focus:border-red-500' : '' ?>"
                              placeholder="Descreva brevemente o que esta categoria engloba"><?= htmlspecialchars($data['descricao'] ?? '') ?></textarea>
                </div>
                <?php if (isset($errors['descricao'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['descricao']) ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Condições Gerais -->
            <div class="md:col-span-2">
                <label for="condicoes_gerais" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-file-contract mr-2 text-gray-400"></i>
                    Condições Gerais
                </label>
                <div class="mt-1 border border-gray-300 rounded-lg overflow-hidden <?= isset($errors['condicoes_gerais']) ? 'border-red-300' : '' ?>">
                    <!-- Editor de texto rico -->
                    <div id="condicoes_gerais_editor" style="min-height: 200px;"></div>
                    <!-- Textarea hidden para enviar o conteúdo HTML -->
                    <textarea name="condicoes_gerais" 
                              id="condicoes_gerais" 
                              class="hidden"
                              ><?= htmlspecialchars($data['condicoes_gerais'] ?? '') ?></textarea>
                </div>
                <p class="mt-1 text-xs text-gray-500">Informe as condições gerais, termos e limitações específicas desta categoria. Você pode formatar o texto (negrito, itálico, listas) ao colar ou editar.</p>
                <?php if (isset($errors['condicoes_gerais'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['condicoes_gerais']) ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Ícone -->
            <div>
                <label for="icone" class="block text-sm font-medium text-gray-700">
                    Ícone
                </label>
                <input type="hidden" 
                       name="icone" 
                       id="icone" 
                       value="<?= htmlspecialchars($data['icone'] ?? 'fas fa-tools') ?>">
                <div class="mt-1 flex items-center gap-3">
                    <div class="flex items-center justify-center h-12 w-12 rounded-lg border border-gray-200 bg-gray-50">
                        <i id="icon-preview" class="<?= htmlspecialchars($data['icone'] ?? 'fas fa-tools') ?> text-xl text-gray-600"></i>
                    </div>
                    <button type="button"
                            onclick="openIconPicker()"
                            class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-icons mr-2"></i>
                        Escolher ícone
                    </button>
                </div>
                <p class="mt-1 text-xs text-gray-500">
                    Use classes do Font Awesome (ex: fas fa-tools, fas fa-bolt, fas fa-wrench)
                </p>
                <?php if (isset($errors['icone'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['icone']) ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Cor -->
            <div>
                <label for="cor" class="block text-sm font-medium text-gray-700">
                    Cor
                </label>
                <div class="mt-1 flex items-center space-x-2">
                    <input type="color" 
                           name="cor" 
                           id="cor" 
                           value="<?= htmlspecialchars($data['cor'] ?? '#3B82F6') ?>"
                           class="h-10 w-16 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    <input type="text" 
                           id="cor-text" 
                           value="<?= htmlspecialchars($data['cor'] ?? '#3B82F6') ?>"
                           class="flex-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                           placeholder="#3B82F6">
                </div>
                <p class="mt-1 text-xs text-gray-500">
                    Escolha uma cor para identificar visualmente esta categoria
                </p>
                <?php if (isset($errors['cor'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['cor']) ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Status -->
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700">
                    Status <span class="text-red-500">*</span>
                </label>
                <div class="mt-1 relative">
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <i class="fas fa-chevron-down text-gray-400"></i>
                    </div>
                    <select name="status" 
                            id="status"
                            class="block w-full pl-3 pr-10 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all sm:text-sm appearance-none bg-white <?= isset($errors['status']) ? 'border-red-300 focus:ring-red-500 focus:border-red-500' : '' ?>"
                            required>
                        <option value="ATIVA" <?= ($data['status'] ?? 'ATIVA') === 'ATIVA' ? 'selected' : '' ?>>Ativa</option>
                        <option value="INATIVA" <?= ($data['status'] ?? '') === 'INATIVA' ? 'selected' : '' ?>>Inativa</option>
                    </select>
                </div>
                <?php if (isset($errors['status'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['status']) ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Tipo de Imóvel -->
            <div>
                <label for="tipo_imovel" class="block text-sm font-medium text-gray-700">
                    Tipo de Imóvel <span class="text-red-500">*</span>
                </label>
                <div class="mt-1 relative">
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <i class="fas fa-chevron-down text-gray-400"></i>
                    </div>
                    <select name="tipo_imovel" 
                            id="tipo_imovel"
                            class="block w-full pl-3 pr-10 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all sm:text-sm appearance-none bg-white <?= isset($errors['tipo_imovel']) ? 'border-red-300 focus:ring-red-500 focus:border-red-500' : '' ?>"
                            required>
                        <option value="AMBOS" <?= ($data['tipo_imovel'] ?? 'AMBOS') === 'AMBOS' ? 'selected' : '' ?>>Ambos (Residencial e Comercial)</option>
                        <option value="RESIDENCIAL" <?= ($data['tipo_imovel'] ?? '') === 'RESIDENCIAL' ? 'selected' : '' ?>>Apenas Residencial</option>
                        <option value="COMERCIAL" <?= ($data['tipo_imovel'] ?? '') === 'COMERCIAL' ? 'selected' : '' ?>>Apenas Comercial</option>
                    </select>
                </div>
                <p class="mt-1 text-xs text-gray-500">
                    Selecione para qual tipo de imóvel esta categoria se aplica
                </p>
                <?php if (isset($errors['tipo_imovel'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['tipo_imovel']) ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Ordem -->
            <div>
                <label for="ordem" class="block text-sm font-medium text-gray-700">
                    Ordem de Exibição
                </label>
                <div class="mt-1 relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-sort-numeric-up text-gray-400"></i>
                    </div>
                    <input type="number" 
                           name="ordem" 
                           id="ordem" 
                           value="<?= htmlspecialchars($data['ordem'] ?? '0') ?>"
                           min="0"
                           class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all sm:text-sm <?= isset($errors['ordem']) ? 'border-red-300 focus:ring-red-500 focus:border-red-500' : '' ?>"
                           placeholder="0">
                </div>
                <p class="mt-1 text-xs text-gray-500">
                    Categorias com menor número aparecem primeiro
                </p>
                <?php if (isset($errors['ordem'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['ordem']) ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Limite de Solicitações (12 meses) -->
            <div>
                <label for="limite_solicitacoes_12_meses" class="block text-sm font-medium text-gray-700">
                    Limite de Solicitações (12 meses)
                </label>
                <div class="mt-1 relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-calendar-check text-gray-400"></i>
                    </div>
                    <input type="number" 
                           name="limite_solicitacoes_12_meses" 
                           id="limite_solicitacoes_12_meses" 
                           value="<?= htmlspecialchars($data['limite_solicitacoes_12_meses'] ?? '') ?>"
                           min="0"
                           class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all sm:text-sm <?= isset($errors['limite_solicitacoes_12_meses']) ? 'border-red-300 focus:ring-red-500 focus:border-red-500' : '' ?>"
                           placeholder="Ex: 5 (deixe vazio para ilimitado)">
                </div>
                <p class="mt-1 text-xs text-gray-500">
                    Número máximo de solicitações permitidas por contrato nesta categoria em um período de 12 meses. Deixe vazio para permitir solicitações ilimitadas.
                </p>
                <?php if (isset($errors['limite_solicitacoes_12_meses'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['limite_solicitacoes_12_meses']) ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Categoria Pai (Hierarquia) -->
            <div class="md:col-span-2">
                <label for="parent_id" class="block text-sm font-medium text-gray-700">
                    Categoria Pai (Separadora)
                </label>
                <div class="mt-1 relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-sitemap text-gray-400"></i>
                    </div>
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <i class="fas fa-chevron-down text-gray-400"></i>
                    </div>
                    <select name="parent_id" 
                            id="parent_id"
                            class="block w-full pl-10 pr-10 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all sm:text-sm appearance-none bg-white <?= isset($errors['parent_id']) ? 'border-red-300 focus:ring-red-500 focus:border-red-500' : '' ?>">
                        <option value="">Nenhuma (Categoria Principal)</option>
                        <?php 
                        // Buscar categorias principais (sem parent_id) usando SQL direto
                        try {
                            $sql = "SELECT * FROM categorias WHERE (parent_id IS NULL OR parent_id = 0) AND status = 'ATIVA' ORDER BY nome ASC";
                            $categoriasPai = \App\Core\Database::fetchAll($sql);
                            foreach ($categoriasPai as $catPai): 
                        ?>
                            <option value="<?= $catPai['id'] ?>" <?= (isset($data['parent_id']) && $data['parent_id'] == $catPai['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($catPai['nome']) ?>
                            </option>
                        <?php 
                            endforeach;
                        } catch (\Exception $e) {
                            // Se houver erro, apenas não mostra as opções
                        }
                        ?>
                    </select>
                </div>
                <p class="mt-1 text-xs text-gray-500">
                    <i class="fas fa-info-circle mr-1"></i>
                    Selecione uma categoria pai para criar uma hierarquia (ex: "Limpeza" dentro de "Manutenção e Prevenção"). Deixe vazio para criar uma categoria principal.
                </p>
                <?php if (isset($errors['parent_id'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['parent_id']) ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Preview -->
        <div class="border-t pt-6">
            <h4 class="text-sm font-medium text-gray-700 mb-3">Preview</h4>
            <div class="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg">
                <div id="preview-icon" class="w-12 h-12 rounded-lg flex items-center justify-center text-white text-xl"
                     style="background-color: <?= htmlspecialchars($data['cor'] ?? '#3B82F6') ?>">
                    <i id="preview-icon-class" class="<?= htmlspecialchars($data['icone'] ?? 'fas fa-tools') ?>"></i>
                </div>
                <div>
                    <h5 id="preview-nome" class="text-lg font-medium text-gray-900">
                        <?= htmlspecialchars($data['nome'] ?? 'Nome da Categoria') ?>
                    </h5>
                    <p id="preview-descricao" class="text-sm text-gray-500">
                        <?= htmlspecialchars($data['descricao'] ?? 'Descrição da categoria') ?>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Botões -->
        <div class="flex justify-end space-x-3 pt-6 border-t">
            <a href="<?= url('admin/categorias') ?>" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-times mr-2"></i>
                Cancelar
            </a>
            <button type="submit" 
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-save mr-2"></i>
                Criar Categoria
            </button>
        </div>
    </form>
</div>

<!-- Modal Seleção de Ícones -->
<div id="icon-picker-overlay" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 z-40"></div>
<div id="icon-picker-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Selecionar Ícone</h3>
            <button type="button" onclick="closeIconPicker()" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6">
            <p class="text-sm text-gray-500 mb-4">Escolha um ícone para representar a categoria. Você pode continuar digitando manualmente se preferir.</p>
            <div id="icon-picker-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 max-h-80 overflow-y-auto">
                <?php foreach ($iconOptions as $iconClass): ?>
                    <button type="button"
                            class="flex items-center justify-center border border-gray-200 rounded-lg py-4 px-4 hover:border-blue-500 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 transition text-2xl"
                            onclick="selectIcon('<?= $iconClass ?>')"
                            title="<?= $iconClass ?>">
                        <i class="<?= $iconClass ?>"></i>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 flex justify-end">
            <button type="button"
                    onclick="closeIconPicker()"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Fechar
            </button>
        </div>
    </div>
</div>

<script>
// Atualizar preview em tempo real
document.getElementById('nome').addEventListener('input', function() {
    document.getElementById('preview-nome').textContent = this.value || 'Nome da Categoria';
});

document.getElementById('descricao').addEventListener('input', function() {
    document.getElementById('preview-descricao').textContent = this.value || 'Descrição da categoria';
});

document.getElementById('cor').addEventListener('input', function() {
    const color = this.value;
    document.getElementById('preview-icon').style.backgroundColor = color;
    document.getElementById('cor-text').value = color;
});

document.getElementById('cor-text').addEventListener('input', function() {
    const color = this.value;
    if (/^#[0-9A-F]{6}$/i.test(color)) {
        document.getElementById('cor').value = color;
        document.getElementById('preview-icon').style.backgroundColor = color;
    }
});

function openIconPicker() {
    document.getElementById('icon-picker-overlay').classList.remove('hidden');
    document.getElementById('icon-picker-modal').classList.remove('hidden');
}

function closeIconPicker() {
    document.getElementById('icon-picker-overlay').classList.add('hidden');
    document.getElementById('icon-picker-modal').classList.add('hidden');
}

function updateIconDisplay(iconClass) {
    const previewIcon = document.getElementById('icon-preview');
    const previewCardIcon = document.getElementById('preview-icon-class');
    previewIcon.className = iconClass + ' text-xl text-gray-600';
    previewCardIcon.className = iconClass;
}

function selectIcon(iconClass) {
    const iconInput = document.getElementById('icone');
    iconInput.value = iconClass;
    updateIconDisplay(iconClass);
    closeIconPicker();
}

// Inicializar preview com o valor atual
updateIconDisplay(document.getElementById('icone').value || 'fas fa-tools');
</script>

<!-- Quill.js Editor para Condições Gerais -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const editorElement = document.getElementById('condicoes_gerais_editor');
    const textareaElement = document.getElementById('condicoes_gerais');
    
    if (!editorElement || !textareaElement) return;
    
    // Configuração do editor Quill
    const quill = new Quill('#condicoes_gerais_editor', {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'align': [] }],
                ['clean']
            ]
        },
        placeholder: 'Informe as condições gerais e termos específicos desta categoria...'
    });
    
    // Carregar conteúdo inicial do textarea
    const initialContent = textareaElement.value;
    if (initialContent) {
        try {
            // Se o conteúdo já é HTML, usar diretamente
            if (initialContent.trim().startsWith('<')) {
                quill.root.innerHTML = initialContent;
            } else {
                quill.root.textContent = initialContent;
            }
        } catch (e) {
            quill.root.textContent = initialContent;
        }
    }
    
    // Sincronizar conteúdo do editor com o textarea antes do submit
    const form = editorElement.closest('form');
    if (form) {
        form.addEventListener('submit', function() {
            const htmlContent = quill.root.innerHTML;
            // Se for apenas parágrafo vazio, enviar vazio
            if (htmlContent === '<p><br></p>' || htmlContent === '<p></p>') {
                textareaElement.value = '';
            } else {
                textareaElement.value = htmlContent;
            }
        });
        
        // Também atualizar ao mudar o conteúdo
        quill.on('text-change', function() {
            const htmlContent = quill.root.innerHTML;
            if (htmlContent === '<p><br></p>' || htmlContent === '<p></p>') {
                textareaElement.value = '';
            } else {
                textareaElement.value = htmlContent;
            }
        });
    }
});
</script>

<?php
$content = ob_get_clean();
include 'app/Views/layouts/admin.php';
?>
