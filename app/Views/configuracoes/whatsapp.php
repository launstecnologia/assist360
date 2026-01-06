<?php
$title = 'Configurações WhatsApp';
$currentPage = 'configuracoes';
$pageTitle = 'Configurações WhatsApp';
ob_start();
?>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Configurações WhatsApp</h1>
        <p class="text-gray-600 mt-1">Configure a URL base para os links enviados nas mensagens WhatsApp</p>
    </div>
    <a href="<?= url('admin/configuracoes') ?>" 
       class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
        <i class="fas fa-arrow-left mr-2"></i>
        Voltar
    </a>
</div>

<?php if (isset($_SESSION['flash_message'])): ?>
    <div class="mb-4 p-4 rounded-lg <?= $_SESSION['flash_type'] === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200' ?>">
        <?= htmlspecialchars($_SESSION['flash_message']) ?>
    </div>
    <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
<?php endif; ?>

<div class="bg-white rounded-lg shadow-sm">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">URL Base dos Links WhatsApp</h3>
        <p class="text-sm text-gray-500 mt-1">Configure a URL base que será usada para gerar todos os links enviados nas mensagens WhatsApp</p>
    </div>
    
    <form method="POST" action="<?= url('admin/configuracoes/whatsapp') ?>" class="p-6 space-y-6">
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
        
        <div>
            <label for="whatsapp_links_base_url" class="block text-sm font-medium text-gray-700 mb-2">
                URL Base <span class="text-red-500">*</span>
            </label>
            <input type="url" 
                   name="whatsapp_links_base_url" 
                   id="whatsapp_links_base_url" 
                   value="<?= htmlspecialchars($urlBase['valor'] ?? 'https://kss.launs.com.br') ?>"
                   class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                   placeholder="https://seu-dominio.com.br"
                   required>
            <p class="mt-2 text-sm text-gray-500">
                <i class="fas fa-info-circle mr-1"></i>
                Esta URL será usada como base para todos os links enviados nas mensagens WhatsApp, como:
            </p>
            <ul class="mt-2 ml-6 list-disc text-sm text-gray-600 space-y-1">
                <li>Links de rastreamento de solicitação</li>
                <li>Links de confirmação de horário</li>
                <li>Links de cancelamento</li>
                <li>Links de reagendamento</li>
                <li>Links de compra de peça</li>
                <li>Links de ações pós-serviço</li>
            </ul>
            <div class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                <p class="text-xs text-yellow-800">
                    <strong>Importante:</strong> Não inclua barra final (/) na URL. Exemplo correto: <code class="bg-yellow-100 px-1 rounded">https://kss.launs.com.br</code>
                </p>
            </div>
        </div>
        
        <!-- Preview dos Links -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h4 class="text-sm font-medium text-blue-900 mb-3">
                <i class="fas fa-eye mr-2"></i>
                Preview dos Links Gerados
            </h4>
            <div class="text-sm text-blue-800 space-y-2">
                <div>
                    <strong>Rastreamento:</strong>
                    <code class="block mt-1 bg-blue-100 px-2 py-1 rounded text-xs break-all" id="preview-rastreamento">
                        <?= htmlspecialchars($urlBase['valor'] ?? 'https://kss.launs.com.br') ?>/locatario/solicitacao/123
                    </code>
                </div>
                <div>
                    <strong>Confirmação:</strong>
                    <code class="block mt-1 bg-blue-100 px-2 py-1 rounded text-xs break-all" id="preview-confirmacao">
                        <?= htmlspecialchars($urlBase['valor'] ?? 'https://kss.launs.com.br') ?>/confirmacao-horario?token=abc123...
                    </code>
                </div>
                <div>
                    <strong>Cancelamento:</strong>
                    <code class="block mt-1 bg-blue-100 px-2 py-1 rounded text-xs break-all" id="preview-cancelamento">
                        <?= htmlspecialchars($urlBase['valor'] ?? 'https://kss.launs.com.br') ?>/cancelamento-horario?token=abc123...
                    </code>
                </div>
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
                Salvar Configuração
            </button>
        </div>
    </form>
</div>

<script>
// Atualizar preview em tempo real
const urlInput = document.getElementById('whatsapp_links_base_url');
const baseUrl = urlInput.value || 'https://kss.launs.com.br';

function updatePreview() {
    const url = urlInput.value.trim() || baseUrl;
    const cleanUrl = url.replace(/\/$/, ''); // Remove barra final
    
    document.getElementById('preview-rastreamento').textContent = cleanUrl + '/locatario/solicitacao/123';
    document.getElementById('preview-confirmacao').textContent = cleanUrl + '/confirmacao-horario?token=abc123...';
    document.getElementById('preview-cancelamento').textContent = cleanUrl + '/cancelamento-horario?token=abc123...';
}

urlInput.addEventListener('input', updatePreview);
urlInput.addEventListener('blur', function() {
    // Remover barra final automaticamente
    this.value = this.value.trim().replace(/\/$/, '');
    updatePreview();
});
</script>

<?php
$content = ob_get_clean();
include 'app/Views/layouts/admin.php';
?>

