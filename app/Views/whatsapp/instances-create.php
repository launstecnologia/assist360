<?php
ob_start();
?>

<div class="bg-white rounded-lg shadow p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-xl font-semibold text-gray-800">Nova Instância WhatsApp</h3>
        <a href="<?= url('admin/whatsapp-instances') ?>" class="text-gray-600 hover:text-gray-800">
            <i class="fas fa-arrow-left mr-2"></i>Voltar
        </a>
    </div>

    <form method="POST" action="<?= url('admin/whatsapp-instances') ?>" class="space-y-6" id="create-instance-form">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Nome da Instância <span class="text-red-500">*</span>
                </label>
                <input type="text" name="nome" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       placeholder="Ex: Notificações Principal">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Nome da Instância (Evolution API) <span class="text-red-500">*</span>
                </label>
                <input type="text" name="instance_name" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       placeholder="Ex: notificacoes_principal">
                <p class="text-xs text-gray-500 mt-1">Nome único na Evolution API (sem espaços, use _ ou -)</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    URL da Evolution API <span class="text-red-500">*</span>
                </label>
                <input type="url" name="api_url" required
                       value="<?= htmlspecialchars($apiUrl) ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       placeholder="https://evolutionapi.launs.com.br">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    API Key <span class="text-red-500">*</span>
                </label>
                <input type="text" name="api_key" required
                       value="<?= htmlspecialchars($apiKey) ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50"
                       placeholder="Sua API Key" readonly>
                <p class="text-xs text-gray-500 mt-1">API Key fixa registrada no sistema</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Número do WhatsApp
                </label>
                <input type="text" name="numero_whatsapp"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       placeholder="Ex: 5511999998888">
                <p class="text-xs text-gray-500 mt-1">Número para gerar QR code específico (opcional, sem + ou espaços)</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Token (Opcional)
                </label>
                <input type="text" name="token" id="token-field"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                       placeholder="Token será gerado automaticamente se vazio">
                <p class="text-xs text-gray-500 mt-1">Token de autenticação Bearer. Se deixar vazio, será gerado automaticamente (UUID).</p>
            </div>
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <p class="text-sm text-blue-800">
                <i class="fas fa-info-circle mr-2"></i>
                Após criar a instância, você precisará escanear o QR code para conectar o WhatsApp.
            </p>
        </div>

        <div class="flex justify-end space-x-3">
            <a href="<?= url('admin/whatsapp-instances') ?>" 
               class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                Cancelar
            </a>
            <button type="submit" 
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i>Criar Instância
            </button>
        </div>
    </form>
</div>

<!-- Modal de Loading -->
<div id="loading-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl p-8 max-w-md w-full mx-4">
        <div class="text-center">
            <div class="mb-4">
                <i class="fas fa-spinner fa-spin text-blue-600 text-5xl"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-800 mb-2">Criando Instância</h3>
            <p class="text-gray-600 mb-4">Aguarde enquanto a instância está sendo criada na Evolution API...</p>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-blue-600 h-2 rounded-full animate-pulse" style="width: 100%"></div>
            </div>
            <p class="text-sm text-gray-500 mt-4">Isso pode levar alguns segundos</p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('create-instance-form');
    const loadingModal = document.getElementById('loading-modal');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            // Validar campos obrigatórios antes de mostrar o modal
            const nome = form.querySelector('input[name="nome"]').value.trim();
            const instanceName = form.querySelector('input[name="instance_name"]').value.trim();
            const apiUrl = form.querySelector('input[name="api_url"]').value.trim();
            const apiKey = form.querySelector('input[name="api_key"]').value.trim();
            
            if (!nome || !instanceName || !apiUrl || !apiKey) {
                // Se faltar campos obrigatórios, deixar o HTML5 validation funcionar
                return;
            }
            
            // Mostrar modal de loading
            loadingModal.classList.remove('hidden');
            
            // Desabilitar o botão de submit para evitar múltiplos envios
            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Criando...';
            }
            
            // O formulário será enviado normalmente após mostrar o modal
        });
    }
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/admin.php';
?>

