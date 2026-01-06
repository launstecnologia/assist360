<?php
$title = 'Configurações';
$currentPage = 'configuracoes';
$pageTitle = 'Configurações';
ob_start();
?>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Configurações</h1>
        <p class="text-gray-600 mt-1">Gerencie as configurações do sistema</p>
    </div>
    <div class="flex items-center space-x-3">
        <a href="<?= url('admin/configuracoes/emergencia') ?>" 
           class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            Config. Emergência
        </a>
        <a href="<?= url('admin/configuracoes/whatsapp') ?>" 
           class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
            <i class="fab fa-whatsapp mr-2"></i>
            Config. WhatsApp
        </a>
        <a href="<?= url('admin/configuracoes/create') ?>" 
           class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
            <i class="fas fa-plus mr-2"></i>
            Nova Configuração
        </a>
    </div>
</div>

<?php if (isset($_SESSION['flash_message'])): ?>
    <div class="mb-4 p-4 rounded-lg <?= $_SESSION['flash_type'] === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200' ?>">
        <?= htmlspecialchars($_SESSION['flash_message']) ?>
    </div>
    <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
<?php endif; ?>

<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Chave</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (empty($configuracoes)): ?>
            <tr>
                <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                    <i class="fas fa-inbox text-4xl mb-3 block text-gray-300"></i>
                    Nenhuma configuração cadastrada
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($configuracoes as $config): ?>
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">
                        <code class="text-xs bg-gray-100 px-2 py-1 rounded"><?= htmlspecialchars($config['chave']) ?></code>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm text-gray-900 max-w-md truncate">
                        <?php
                        $valor = $config['valor'];
                        if ($config['tipo'] === 'json') {
                            $valorDecodificado = json_decode($valor, true);
                            if (is_array($valorDecodificado)) {
                                $valor = implode(', ', $valorDecodificado);
                            }
                        }
                        echo htmlspecialchars($valor);
                        ?>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        <?= htmlspecialchars($config['tipo']) ?>
                    </span>
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm text-gray-500 max-w-md">
                        <?= htmlspecialchars($config['descricao'] ?: 'Sem descrição') ?>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <a href="<?= url("admin/configuracoes/{$config['id']}/edit") ?>" 
                       class="text-blue-600 hover:text-blue-900 mr-4" 
                       title="Editar">
                        <i class="fas fa-edit"></i>
                    </a>
                    <button onclick="deletarConfiguracao(<?= $config['id'] ?>, '<?= htmlspecialchars($config['chave']) ?>')" 
                            class="text-red-600 hover:text-red-900" 
                            title="Excluir">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function deletarConfiguracao(id, chave) {
    if (!confirm('Tem certeza que deseja excluir a configuração "' + chave + '"? Esta ação não pode ser desfeita.')) {
        return;
    }

    fetch('<?= url('admin/configuracoes') ?>/' + id + '/delete', {
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
            alert('Erro ao excluir: ' + (data.error || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao excluir configuração');
    });
}
</script>

<?php
$content = ob_get_clean();
include 'app/Views/layouts/admin.php';
?>

