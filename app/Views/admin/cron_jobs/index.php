<?php
$title = $title ?? 'Gerenciar Cron Jobs';
ob_start();
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Gerenciar Cron Jobs</h1>
            <p class="text-gray-600 mt-2">Configure e monitore a execução automática de tarefas</p>
        </div>
        <button onclick="executarTodosPendentes()" 
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
            <i class="fas fa-play mr-2"></i>
            Executar Pendentes
        </button>
    </div>

    <!-- Lista de Cron Jobs -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Frequência</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Última Execução</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estatísticas</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($cronJobs as $cron): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($cron['nome']) ?></div>
                                <div class="text-sm text-gray-500"><?= htmlspecialchars($cron['descricao'] ?? '') ?></div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <input type="number" 
                                       id="frequencia_<?= $cron['id'] ?>"
                                       value="<?= $cron['frequencia_minutos'] ?>"
                                       min="1"
                                       class="w-20 px-2 py-1 border border-gray-300 rounded text-sm"
                                       onchange="atualizarFrequencia(<?= $cron['id'] ?>)">
                                <span class="text-sm text-gray-600">minutos</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" 
                                       <?= $cron['ativo'] ? 'checked' : '' ?>
                                       onchange="toggleAtivo(<?= $cron['id'] ?>, this.checked)"
                                       class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                <span class="ml-3 text-sm font-medium text-gray-700">
                                    <?= $cron['ativo'] ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </label>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php if ($cron['ultima_execucao']): ?>
                                <div><?= date('d/m/Y H:i:s', strtotime($cron['ultima_execucao'])) ?></div>
                                <?php if ($cron['proxima_execucao']): ?>
                                    <div class="text-xs text-gray-400">
                                        Próxima: <?= date('H:i', strtotime($cron['proxima_execucao'])) ?>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-gray-400">Nunca executado</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <div class="flex gap-4">
                                <div>
                                    <span class="text-gray-600">Execuções:</span>
                                    <span class="font-semibold text-green-600"><?= $cron['total_execucoes'] ?></span>
                                </div>
                                <?php if ($cron['total_erros'] > 0): ?>
                                <div>
                                    <span class="text-gray-600">Erros:</span>
                                    <span class="font-semibold text-red-600"><?= $cron['total_erros'] ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php if ($cron['ultimo_erro']): ?>
                                <div class="text-xs text-red-600 mt-1" title="<?= htmlspecialchars($cron['ultimo_erro']) ?>">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <?= htmlspecialchars(substr($cron['ultimo_erro'], 0, 50)) ?>...
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex gap-2">
                                <button onclick="executarCron(<?= $cron['id'] ?>)" 
                                        class="text-blue-600 hover:text-blue-900">
                                    <i class="fas fa-play"></i> Executar
                                </button>
                                <button onclick="verHistorico(<?= $cron['id'] ?>, '<?= htmlspecialchars($cron['nome']) ?>')" 
                                        class="text-gray-600 hover:text-gray-900">
                                    <i class="fas fa-history"></i> Histórico
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal de Histórico -->
<div id="modalHistorico" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col">
        <div class="bg-gray-800 text-white px-6 py-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold" id="modalHistoricoTitulo">Histórico de Execuções</h3>
            <button onclick="fecharModalHistorico()" class="text-white hover:text-gray-200">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6 overflow-y-auto flex-1">
            <div id="historicoContent" class="space-y-2">
                <div class="text-center py-8 text-gray-400">
                    <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                    <p>Carregando histórico...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleAtivo(id, ativo) {
    fetch(`<?= url('admin/cron-jobs/') ?>${id}/toggle-ativo`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ ativo: ativo })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarNotificacao('Status atualizado com sucesso', 'success');
        } else {
            mostrarNotificacao(data.message || 'Erro ao atualizar status', 'error');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        mostrarNotificacao('Erro ao atualizar status', 'error');
    });
}

function atualizarFrequencia(id) {
    const input = document.getElementById(`frequencia_${id}`);
    const frequencia = parseInt(input.value);
    
    if (frequencia < 1) {
        mostrarNotificacao('Frequência deve ser no mínimo 1 minuto', 'error');
        return;
    }
    
    fetch(`<?= url('admin/cron-jobs/') ?>${id}/atualizar-frequencia`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ frequencia_minutos: frequencia })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarNotificacao('Frequência atualizada com sucesso', 'success');
        } else {
            mostrarNotificacao(data.message || 'Erro ao atualizar frequência', 'error');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        mostrarNotificacao('Erro ao atualizar frequência', 'error');
    });
}

function executarCron(id) {
    if (!confirm('Deseja executar este cron job agora?')) {
        return;
    }
    
    mostrarNotificacao('Executando cron job...', 'info');
    
    fetch(`<?= url('admin/cron-jobs/') ?>${id}/executar`, {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarNotificacao(`Cron job executado com sucesso em ${data.tempo_execucao_ms}ms`, 'success');
            setTimeout(() => location.reload(), 2000);
        } else {
            mostrarNotificacao(data.message || 'Erro ao executar cron job', 'error');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        mostrarNotificacao('Erro ao executar cron job', 'error');
    });
}

function executarTodosPendentes() {
    if (!confirm('Deseja executar todos os cron jobs pendentes?')) {
        return;
    }
    
    mostrarNotificacao('Executando cron jobs pendentes...', 'info');
    
    fetch('<?= url('admin/cron-jobs/executar-pendentes') ?>', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarNotificacao(`${data.executados} cron job(s) executado(s) com sucesso`, 'success');
            setTimeout(() => location.reload(), 2000);
        } else {
            mostrarNotificacao(data.message || 'Erro ao executar cron jobs', 'error');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        mostrarNotificacao('Erro ao executar cron jobs', 'error');
    });
}

function verHistorico(id, nome) {
    document.getElementById('modalHistoricoTitulo').textContent = `Histórico: ${nome}`;
    document.getElementById('modalHistorico').classList.remove('hidden');
    
    const content = document.getElementById('historicoContent');
    content.innerHTML = '<div class="text-center py-8 text-gray-400"><i class="fas fa-spinner fa-spin text-2xl mb-2"></i><p>Carregando histórico...</p></div>';
    
    fetch(`<?= url('admin/cron-jobs/') ?>${id}/historico`)
    .then(response => response.json())
    .then(data => {
        if (data.success && data.historico.length > 0) {
            content.innerHTML = data.historico.map(exec => {
                const statusClass = exec.status === 'sucesso' ? 'bg-green-50 border-green-200 text-green-800' :
                                   exec.status === 'erro' ? 'bg-red-50 border-red-200 text-red-800' :
                                   'bg-yellow-50 border-yellow-200 text-yellow-800';
                const statusIcon = exec.status === 'sucesso' ? 'fa-check-circle' :
                                  exec.status === 'erro' ? 'fa-times-circle' :
                                  'fa-exclamation-triangle';
                
                return `
                    <div class="border rounded-lg p-4 ${statusClass}">
                        <div class="flex justify-between items-start mb-2">
                            <div class="flex items-center gap-2">
                                <i class="fas ${statusIcon}"></i>
                                <span class="font-semibold">${exec.status === 'sucesso' ? 'Sucesso' : exec.status === 'erro' ? 'Erro' : 'Aviso'}</span>
                            </div>
                            <div class="text-sm">
                                ${new Date(exec.created_at).toLocaleString('pt-BR')}
                                ${exec.tempo_execucao_ms ? ` • ${exec.tempo_execucao_ms}ms` : ''}
                            </div>
                        </div>
                        <p class="text-sm">${exec.mensagem || 'Sem mensagem'}</p>
                    </div>
                `;
            }).join('');
        } else {
            content.innerHTML = '<div class="text-center py-8 text-gray-400"><p>Nenhuma execução registrada</p></div>';
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        content.innerHTML = '<div class="text-center py-8 text-red-400"><p>Erro ao carregar histórico</p></div>';
    });
}

function fecharModalHistorico() {
    document.getElementById('modalHistorico').classList.add('hidden');
}

function mostrarNotificacao(mensagem, tipo) {
    // Implementar sistema de notificações (pode usar o mesmo do sistema)
    alert(mensagem);
}

// Executar pendentes automaticamente quando a página carregar e periodicamente
let executandoAutomatico = false;

function executarPendentesAutomatico() {
    if (executandoAutomatico) return;
    executandoAutomatico = true;
    
    fetch('<?= url('admin/cron-jobs/executar-pendentes') ?>', { method: 'POST' })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.executados > 0) {
                console.log(`✅ ${data.executados} cron job(s) executado(s) automaticamente`);
                // Recarregar a página para atualizar estatísticas
                setTimeout(() => location.reload(), 2000);
            }
        })
        .catch(error => {
            console.error('Erro ao executar cron jobs automaticamente:', error);
        })
        .finally(() => {
            executandoAutomatico = false;
        });
}

// Executar imediatamente ao carregar
setTimeout(executarPendentesAutomatico, 2000);

// Executar a cada 1 minuto
setInterval(executarPendentesAutomatico, 60000);
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/admin.php';
?>

