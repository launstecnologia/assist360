<?php
$title = 'Telefones de Emergência';
$currentPage = 'telefones-emergencia';
$pageTitle = 'Telefones de Emergência';
ob_start();
?>

<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Telefones de Emergência</h1>
        <p class="text-gray-600 mt-1">Gerencie os telefones 0800 para emergências</p>
    </div>
    <a href="<?= url('admin/telefones-emergencia/create') ?>" 
       class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
        <i class="fas fa-plus mr-2"></i>
        Novo Telefone
    </a>
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
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Número</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (empty($telefones)): ?>
            <tr>
                <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                    <i class="fas fa-inbox text-4xl mb-3 block text-gray-300"></i>
                    Nenhum telefone cadastrado
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($telefones as $telefone): ?>
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">
                        <i class="fas fa-phone mr-2 text-blue-600"></i>
                        <?= htmlspecialchars($telefone['numero']) ?>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm text-gray-500">
                        <?= htmlspecialchars($telefone['descricao'] ?: 'Sem descrição') ?>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <?php if ($telefone['is_ativo']): ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <i class="fas fa-check-circle mr-1"></i>
                            Ativo
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            <i class="fas fa-times-circle mr-1"></i>
                            Inativo
                        </span>
                    <?php endif; ?>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <a href="<?= url("admin/telefones-emergencia/{$telefone['id']}/edit") ?>" 
                       class="text-blue-600 hover:text-blue-900 mr-4" 
                       title="Editar">
                        <i class="fas fa-edit"></i>
                    </a>
                    <button onclick="deletarTelefone(<?= $telefone['id'] ?>)" 
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
function deletarTelefone(id) {
    if (!confirm('Tem certeza que deseja excluir este telefone de emergência?')) {
        return;
    }

    fetch('<?= url('admin/telefones-emergencia') ?>/' + id + '/delete', {
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
        alert('Erro ao excluir telefone de emergência');
    });
}
</script>

<?php
$content = ob_get_clean();
include 'app/Views/layouts/admin.php';
?>

