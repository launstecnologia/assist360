<?php
$title = 'Nova Configuração';
$currentPage = 'configuracoes';
$pageTitle = 'Nova Configuração';
ob_start();
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Nova Configuração</h1>
    <p class="text-gray-600 mt-1">Crie uma nova configuração do sistema</p>
</div>

<div class="bg-white rounded-lg shadow-sm">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Criar Configuração</h3>
    </div>
    
    <form method="POST" action="<?= url('admin/configuracoes') ?>" class="p-6 space-y-6">
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
            <!-- Chave -->
            <div class="md:col-span-2">
                <label for="chave" class="block text-sm font-medium text-gray-700">
                    Chave <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                       name="chave" 
                       id="chave" 
                       value="<?= htmlspecialchars($data['chave'] ?? '') ?>"
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?= isset($errors['chave']) ? 'border-red-300' : '' ?>"
                       placeholder="Ex: telefone_emergencia"
                       required>
                <p class="mt-1 text-xs text-gray-500">
                    Chave única para identificar a configuração (sem espaços, use underscore)
                </p>
                <?php if (isset($errors['chave'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['chave']) ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Valor -->
            <div class="md:col-span-2">
                <label for="valor" class="block text-sm font-medium text-gray-700">
                    Valor <span class="text-red-500">*</span>
                </label>
                <textarea name="valor" 
                          id="valor" 
                          rows="3"
                          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?= isset($errors['valor']) ? 'border-red-300' : '' ?>"
                          placeholder="Valor da configuração"
                          required><?= htmlspecialchars($data['valor'] ?? '') ?></textarea>
                <p class="mt-1 text-xs text-gray-500">
                    Valor da configuração. Para JSON, use formato válido: ["item1", "item2"]
                </p>
                <?php if (isset($errors['valor'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['valor']) ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Tipo -->
            <div>
                <label for="tipo" class="block text-sm font-medium text-gray-700">
                    Tipo <span class="text-red-500">*</span>
                </label>
                <select name="tipo" 
                        id="tipo"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?= isset($errors['tipo']) ? 'border-red-300' : '' ?>"
                        required>
                    <option value="string" <?= ($data['tipo'] ?? 'string') === 'string' ? 'selected' : '' ?>>String</option>
                    <option value="number" <?= ($data['tipo'] ?? '') === 'number' ? 'selected' : '' ?>>Number</option>
                    <option value="boolean" <?= ($data['tipo'] ?? '') === 'boolean' ? 'selected' : '' ?>>Boolean</option>
                    <option value="json" <?= ($data['tipo'] ?? '') === 'json' ? 'selected' : '' ?>>JSON</option>
                    <option value="time" <?= ($data['tipo'] ?? '') === 'time' ? 'selected' : '' ?>>Time</option>
                </select>
                <?php if (isset($errors['tipo'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['tipo']) ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Descrição -->
            <div>
                <label for="descricao" class="block text-sm font-medium text-gray-700">
                    Descrição
                </label>
                <input type="text" 
                       name="descricao" 
                       id="descricao" 
                       value="<?= htmlspecialchars($data['descricao'] ?? '') ?>"
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                       placeholder="Descrição da configuração">
            </div>
        </div>
        
        <!-- Botões -->
        <div class="flex justify-end space-x-3 pt-6 border-t">
            <a href="<?= url('admin/configuracoes') ?>" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-times mr-2"></i>
                Cancelar
            </a>
            <button type="submit" 
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-save mr-2"></i>
                Salvar
            </button>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
include 'app/Views/layouts/admin.php';
?>

