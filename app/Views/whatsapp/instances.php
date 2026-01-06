<?php
ob_start();
?>

<div class="bg-white rounded-lg shadow p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-xl font-semibold text-gray-800">Instâncias WhatsApp (Evolution API)</h3>
        <a href="<?= url('admin/whatsapp-instances/create') ?>" class="inline-flex items-center px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
            <i class="fas fa-plus mr-2"></i>Nova Instância
        </a>
    </div>

    <div class="mb-4">
        <p class="text-sm text-gray-600">Gerencie as instâncias da Evolution API para envio de notificações WhatsApp.</p>
        <div class="mt-2 p-3 bg-blue-50 border border-blue-200 rounded-lg">
            <p class="text-sm text-blue-800">
                <i class="fas fa-info-circle mr-2"></i>
                <strong>Instância Padrão:</strong> A instância marcada como "Padrão" será usada automaticamente para enviar todas as notificações WhatsApp do sistema.
            </p>
        </div>
    </div>

    <?php if (!empty($instances)): ?>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Instância</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Token Bearer</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Número</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Padrão</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($instances as $instance): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($instance['nome']) ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-500"><?= htmlspecialchars($instance['instance_name']) ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if (!empty($instance['token'])): ?>
                            <div class="flex items-center space-x-2">
                                <code class="text-xs bg-gray-100 px-2 py-1 rounded font-mono"><?= htmlspecialchars($instance['token']) ?></code>
                                <button onclick="copiarToken('<?= htmlspecialchars($instance['token']) ?>')" 
                                        class="text-blue-600 hover:text-blue-800" 
                                        title="Copiar token">
                                    <i class="fas fa-copy text-xs"></i>
                                </button>
                            </div>
                        <?php else: ?>
                            <span class="text-gray-400 text-xs">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-500"><?= htmlspecialchars($instance['numero_whatsapp'] ?? '-') ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php
                        $statusColors = [
                            'CONECTADO' => 'bg-green-100 text-green-800',
                            'CONECTANDO' => 'bg-yellow-100 text-yellow-800',
                            'DESCONECTADO' => 'bg-red-100 text-red-800',
                            'DESCONECTANDO' => 'bg-gray-100 text-gray-800'
                        ];
                        $statusColor = $statusColors[$instance['status']] ?? 'bg-gray-100 text-gray-800';
                        ?>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $statusColor ?>">
                            <?= htmlspecialchars($instance['status']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if ($instance['is_padrao']): ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                <i class="fas fa-star mr-1"></i>Padrão
                            </span>
                        <?php else: ?>
                            <span class="text-gray-400">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex items-center space-x-2">
                            <?php if ($instance['status'] !== 'CONECTADO'): ?>
                                <a href="<?= url('admin/whatsapp-instances/' . $instance['id'] . '/qrcode') ?>" 
                                   class="text-blue-600 hover:text-blue-900" title="Conectar">
                                    <i class="fas fa-qrcode"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($instance['status'] === 'CONECTADO' && !$instance['is_padrao']): ?>
                                <button onclick="setPadrao(<?= $instance['id'] ?>)" 
                                        class="text-yellow-600 hover:text-yellow-900" title="Definir como padrão">
                                    <i class="fas fa-star"></i>
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($instance['status'] === 'CONECTADO'): ?>
                                <button onclick="reiniciar(<?= $instance['id'] ?>)" 
                                        class="text-purple-600 hover:text-purple-900" title="Reiniciar instância">
                                    <i class="fas fa-redo"></i>
                                </button>
                                <button onclick="logout(<?= $instance['id'] ?>)" 
                                        class="text-orange-600 hover:text-orange-900" title="Logout (desconectar)">
                                    <i class="fas fa-sign-out-alt"></i>
                                </button>
                                <button onclick="desconectar(<?= $instance['id'] ?>)" 
                                        class="text-orange-600 hover:text-orange-900" title="Desconectar">
                                    <i class="fas fa-unlink"></i>
                                </button>
                            <?php endif; ?>
                            
                            <button onclick="deletar(<?= $instance['id'] ?>)" 
                                    class="text-red-600 hover:text-red-900" title="Deletar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="border rounded-lg p-6 text-gray-500 text-sm text-center">
        Nenhuma instância cadastrada. <a href="<?= url('admin/whatsapp-instances/create') ?>" class="text-blue-600 hover:underline">Criar primeira instância</a>
    </div>
    <?php endif; ?>
</div>

<script>
function setPadrao(id) {
    if (!confirm('Definir esta instância como padrão para envio de notificações?')) return;
    
    fetch('<?= url('admin/whatsapp-instances') ?>/' + id + '/set-padrao', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erro: ' + (data.error || 'Erro desconhecido'));
        }
    })
    .catch(err => {
        alert('Erro ao definir instância padrão');
        console.error(err);
    });
}

function reiniciar(id) {
    if (!confirm('Reiniciar esta instância?\n\nA instância será reiniciada e pode levar alguns segundos para reconectar.')) return;
    
    fetch('<?= url('admin/whatsapp-instances') ?>/' + id + '/reiniciar', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Instância reiniciada com sucesso!');
            location.reload();
        } else {
            alert('Erro: ' + (data.error || 'Erro desconhecido'));
        }
    })
    .catch(err => {
        alert('Erro ao reiniciar instância');
        console.error(err);
    });
}

function logout(id) {
    if (!confirm('Fazer logout desta instância?\n\nA instância será desconectada do WhatsApp.')) return;
    
    fetch('<?= url('admin/whatsapp-instances') ?>/' + id + '/logout', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Logout realizado com sucesso!');
            location.reload();
        } else {
            alert('Erro: ' + (data.error || 'Erro desconhecido'));
        }
    })
    .catch(err => {
        alert('Erro ao fazer logout');
        console.error(err);
    });
}

function desconectar(id) {
    if (!confirm('Desconectar esta instância?')) return;
    
    fetch('<?= url('admin/whatsapp-instances') ?>/' + id + '/desconectar', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erro: ' + (data.error || 'Erro desconhecido'));
        }
    })
    .catch(err => {
        alert('Erro ao desconectar instância');
        console.error(err);
    });
}

function deletar(id) {
    if (!confirm('Tem certeza que deseja deletar esta instância?\n\nEsta ação não pode ser desfeita e a instância será removida da Evolution API.')) return;
    
    fetch('<?= url('admin/whatsapp-instances') ?>/' + id + '/delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(r => {
        if (!r.ok) {
            return r.json().then(data => {
                throw new Error(data.error || 'Erro ao deletar instância');
            });
        }
        return r.json();
    })
    .then(data => {
        if (data.success || data.message) {
            alert('Instância deletada com sucesso!');
            location.reload();
        } else {
            alert('Erro: ' + (data.error || 'Erro desconhecido'));
        }
    })
    .catch(err => {
        alert('Erro ao deletar instância: ' + err.message);
        console.error(err);
    });
}

function copiarToken(token) {
    navigator.clipboard.writeText(token).then(function() {
        alert('Token copiado para a área de transferência!');
    }, function(err) {
        // Fallback para navegadores mais antigos
        const textarea = document.createElement('textarea');
        textarea.value = token;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        try {
            document.execCommand('copy');
            alert('Token copiado para a área de transferência!');
        } catch (err) {
            alert('Erro ao copiar token. Token: ' + token);
        }
        document.body.removeChild(textarea);
    });
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/admin.php';
?>

