<?php
$title = 'Novo Telefone de Emergência';
$currentPage = 'telefones-emergencia';
$pageTitle = 'Novo Telefone de Emergência';
ob_start();
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Novo Telefone de Emergência</h1>
    <p class="text-gray-600 mt-1">Cadastre um novo telefone 0800 para emergências</p>
</div>

<div class="bg-white rounded-lg shadow-sm">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Cadastrar Telefone</h3>
    </div>
    
    <form method="POST" action="<?= url('admin/telefones-emergencia') ?>" class="p-6 space-y-6">
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
            <!-- Número -->
            <div class="md:col-span-2">
                <label for="numero" class="block text-sm font-medium text-gray-700">
                    Número do Telefone <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                       name="numero" 
                       id="numero" 
                       value="<?= htmlspecialchars($data['numero'] ?? '') ?>"
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?= isset($errors['numero']) ? 'border-red-300' : '' ?>"
                       placeholder="Ex: 0800 123 4567"
                       required>
                <p class="mt-1 text-xs text-gray-500">
                    Digite o número do telefone 0800 (com ou sem formatação)
                </p>
                <?php if (isset($errors['numero'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?= htmlspecialchars($errors['numero']) ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Descrição -->
            <div class="md:col-span-2">
                <label for="descricao" class="block text-sm font-medium text-gray-700">
                    Descrição
                </label>
                <textarea name="descricao" 
                          id="descricao" 
                          rows="3"
                          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?= isset($errors['descricao']) ? 'border-red-300' : '' ?>"
                          placeholder="Ex: Telefone de emergência 24 horas"><?= htmlspecialchars($data['descricao'] ?? '') ?></textarea>
                <p class="mt-1 text-xs text-gray-500">
                    Descrição opcional sobre quando usar este telefone
                </p>
            </div>
            
            <!-- Status -->
            <div class="md:col-span-2">
                <div class="flex items-center">
                    <input type="checkbox" 
                           name="is_ativo" 
                           id="is_ativo" 
                           value="1"
                           <?= ($data['is_ativo'] ?? 1) ? 'checked' : '' ?>
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="is_ativo" class="ml-2 block text-sm font-medium text-gray-700">
                        Ativo
                    </label>
                </div>
                <p class="mt-1 text-xs text-gray-500 ml-6">
                    Apenas telefones ativos serão exibidos para os usuários
                </p>
            </div>
        </div>
        
        <!-- Botões -->
        <div class="flex justify-end space-x-3 pt-6 border-t">
            <a href="<?= url('admin/telefones-emergencia') ?>" 
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

