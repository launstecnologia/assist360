<?php
$title = 'Solicitações';
$currentPage = 'solicitacoes';
$pageTitle = 'Todas as Solicitações';
ob_start();
?>

<style>
.request-card {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border-left: 4px solid #3B82F6;
}
.request-card:hover {
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}
</style>

<!-- Header com Link para Solicitações Manuais -->
<div class="mb-6 flex items-center justify-between">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Solicitações do Sistema</h2>
        <p class="text-gray-600 text-sm mt-1">Gerenciadas através do fluxo normal de autenticação</p>
    </div>
    <?php
    // Contador de solicitações manuais não migradas
    try {
        $solicitacaoManualModel = new \App\Models\SolicitacaoManual();
        $naoMigradas = count($solicitacaoManualModel->getNaoMigradas(999));
    ?>
        <a href="<?= url('admin/solicitacoes-manuais') ?>" 
           class="inline-flex items-center px-4 py-2 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-lg hover:bg-yellow-100 transition-colors">
            <i class="fas fa-file-alt mr-2"></i>
            Ver Solicitações Manuais
            <?php if ($naoMigradas > 0): ?>
                <span class="ml-2 inline-flex items-center justify-center px-2.5 py-0.5 text-xs font-bold text-yellow-900 bg-yellow-300 rounded-full">
                    <?= $naoMigradas ?> pendente<?= $naoMigradas > 1 ? 's' : '' ?>
                </span>
            <?php endif; ?>
        </a>
    <?php
    } catch (\Exception $e) {
        // Silencioso se der erro
    }
    ?>
</div>

<!-- Barra de Busca e Filtros -->
<div class="bg-white rounded-lg shadow-sm p-4 mb-6">
    <form method="GET" action="<?= url('admin/solicitacoes') ?>" class="flex gap-3">
        <div class="flex-1">
            <input 
                type="text" 
                name="busca" 
                placeholder="Buscar por protocolo, tipo ou cliente..." 
                value="<?= htmlspecialchars($_GET['busca'] ?? '') ?>"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
        </div>
        <div class="w-64">
            <select name="status_id" onchange="this.form.submit()"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <option value="">Todos os status</option>
                <?php foreach ($status as $s): ?>
                <option value="<?= $s['id'] ?>" <?= ($filtros['status_id'] ?? '') == $s['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($s['nome']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            <i class="fas fa-search"></i>
        </button>
    </form>
</div>

<!-- Título com Contador -->
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">
        Todas as Solicitações (<?= count($solicitacoes) ?>)
    </h2>
</div>

<!-- Lista de Solicitações -->
<div class="space-y-4">
    <?php if (empty($solicitacoes)): ?>
    <div class="bg-white rounded-lg shadow-sm p-12 text-center">
        <i class="fas fa-inbox text-4xl text-gray-300 mb-3 block"></i>
        <p class="text-gray-500">Nenhuma solicitação encontrada</p>
    </div>
    <?php else: ?>
    <?php foreach ($solicitacoes as $sol): ?>
    <div class="request-card">
        <div class="flex items-start justify-between mb-4">
            <!-- ID e Status -->
            <div class="flex items-center gap-3">
                <h3 class="text-lg font-bold text-gray-900">
                    <?= htmlspecialchars($sol['numero_solicitacao'] ?? 'KSS'.$sol['id']) ?>
                </h3>
                <span class="px-3 py-1 rounded-full text-sm font-medium" 
                      style="background-color: <?= $sol['status_cor'] ?? '#3B82F6' ?>20; color: <?= $sol['status_cor'] ?? '#3B82F6' ?>">
                    <?= htmlspecialchars($sol['status_nome'] ?? 'Sem status') ?>
                </span>
                <?php if (!empty($sol['horario_confirmado'])): ?>
                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                    <i class="fas fa-check mr-1"></i> Confirmado
                </span>
                <?php endif; ?>
                <?php if (!empty($sol['horarios_opcoes'])): ?>
                    <?php $qtdHorarios = count(json_decode($sol['horarios_opcoes'], true) ?? []); ?>
                    <?php if ($qtdHorarios > 0): ?>
                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 ml-2">
                            <i class="fas fa-clock mr-1"></i><?= $qtdHorarios ?> horário<?= $qtdHorarios > 1 ? 's' : '' ?>
                        </span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Informações Principais -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div class="space-y-3">
                <!-- Cliente -->
                <div class="flex items-center gap-2 text-sm">
                    <i class="fas fa-user text-gray-400 w-5"></i>
                    <span class="font-medium text-gray-900">
                        <?= htmlspecialchars($sol['locatario_nome'] ?? 'Cliente não informado') ?>
                    </span>
                </div>

                <!-- Serviço -->
                <div class="flex items-start gap-2 text-sm">
                    <i class="fas fa-file-alt text-gray-400 w-5 mt-0.5"></i>
                    <div>
                        <span class="font-medium text-gray-900">
                            <?= htmlspecialchars($sol['categoria_nome'] ?? '') ?>
                        </span>
                        <?php 
                        // Usar helper para buscar todas as subcategorias
                        $subcategorias = getSubcategorias($sol);
                        $temMultiplas = count($subcategorias) > 1;
                        if ($temMultiplas): ?>
                        <div class="text-gray-600 mt-1">
                            <div class="font-medium text-xs mb-1">Serviços:</div>
                            <ul class="list-disc list-inside space-y-0.5 text-xs">
                                <?php foreach ($subcategorias as $nome): ?>
                                    <li><?= htmlspecialchars($nome) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php elseif (!empty($subcategorias)): ?>
                        <span class="text-gray-600">
                            - <?= htmlspecialchars($subcategorias[0]) ?>
                        </span>
                        <?php elseif (!empty($sol['subcategoria_nome'])): ?>
                        <span class="text-gray-600">
                            - <?= htmlspecialchars($sol['subcategoria_nome']) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tipo -->
                <div class="flex items-center gap-2 text-sm">
                    <i class="fas fa-home text-gray-400 w-5"></i>
                    <span class="text-gray-600">Residencial</span>
                </div>

                <!-- Data -->
                <div class="flex items-center gap-2 text-sm">
                    <i class="fas fa-calendar text-gray-400 w-5"></i>
                    <span class="text-gray-600">
                        <?= date('d/m/Y', strtotime($sol['created_at'])) ?>
                    </span>
                </div>
            </div>

            <div class="space-y-3">
                <!-- Endereço -->
                <div class="flex items-start gap-2 text-sm">
                    <i class="fas fa-map-marker-alt text-gray-400 w-5 mt-0.5"></i>
                    <span class="text-gray-600">
                        <?= htmlspecialchars($sol['imovel_endereco'] ?? 'Endereço não informado') ?>, 
                        <?= htmlspecialchars($sol['imovel_numero'] ?? 's/n') ?>
                    </span>
                </div>

                <!-- CPF -->
                <?php if (!empty($sol['locatario_cpf'])): ?>
                <div class="flex items-center gap-2 text-sm">
                    <i class="fas fa-id-card text-gray-400 w-5"></i>
                    <span class="text-gray-600">
                        CPF: <?= htmlspecialchars($sol['locatario_cpf']) ?>
                    </span>
                </div>
                <?php endif; ?>

                <!-- Telefone/WhatsApp -->
                <?php if (!empty($sol['locatario_telefone'])): ?>
                <div class="flex items-center gap-2 text-sm">
                    <i class="fab fa-whatsapp text-green-500 w-5"></i>
                    <a href="https://wa.me/55<?= preg_replace('/[^0-9]/', '', $sol['locatario_telefone']) ?>" 
                       target="_blank" class="text-green-600 hover:text-green-800 font-medium">
                        <?= htmlspecialchars($sol['locatario_telefone']) ?>
                    </a>
                </div>
                <?php endif; ?>

                <!-- Imobiliária -->
                <div class="flex items-center gap-2 text-sm">
                    <i class="fas fa-building text-gray-400 w-5"></i>
                    <span class="text-gray-600">
                        <?= htmlspecialchars($sol['imobiliaria_nome'] ?? 'Não informado') ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Descrição -->
        <?php $descricaoCard = $sol['descricao_card'] ?? $sol['descricao_problema'] ?? ''; ?>
        <?php if (!empty($descricaoCard)): ?>
        <div class="mb-4 bg-gray-50 p-3 rounded-lg">
            <p class="text-sm text-gray-700">
                <strong>Descrição:</strong> <?= htmlspecialchars(substr($descricaoCard, 0, 150)) ?>
                <?= strlen($descricaoCard) > 150 ? '...' : '' ?>
            </p>
        </div>
        <?php endif; ?>

        <!-- Ações -->
        <div class="flex items-center justify-between pt-4 border-t border-gray-200">
            <div class="flex items-center gap-3">
                <!-- Dropdown Status -->
                <select onchange="mudarStatus(<?= $sol['id'] ?>, this.value)" 
                        class="px-4 py-2 border border-gray-300 rounded-lg text-sm bg-white hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 cursor-pointer">
                    <option value="">Alterar Status</option>
                    <?php foreach ($status as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $sol['status_id'] == $s['id'] ? 'disabled' : '' ?>>
                        <?= htmlspecialchars($s['nome']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>

                <!-- Botão Atribuir Prestador -->
                <button onclick="atribuirPrestador(<?= $sol['id'] ?>)"
                        class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm">
                    <i class="fas fa-user-plus mr-2"></i>
                    Atribuir Prestador
                </button>
            </div>

            <!-- Ver Detalhes -->
            <a href="<?= url("admin/solicitacoes/{$sol['id']}") ?>" 
               class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">
                <i class="fas fa-eye mr-2"></i>
                Ver Detalhes
            </a>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function mudarStatus(solicitacaoId, novoStatusId) {
    if (!novoStatusId) return;
    
    if (confirm('Deseja realmente alterar o status desta solicitação?')) {
        fetch(`<?= url('admin/solicitacoes/') ?>${solicitacaoId}/status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                status_id: novoStatusId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erro: ' + (data.error || 'Não foi possível atualizar o status'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Erro ao atualizar status');
        });
    } else {
        // Resetar o select
        event.target.selectedIndex = 0;
    }
}

function atribuirPrestador(solicitacaoId) {
    // TODO: Implementar modal de atribuição de prestador
    alert('Funcionalidade de atribuir prestador será implementada em breve!');
}
</script>

<?php
$content = ob_get_clean();
include 'app/Views/layouts/admin.php';
?>
