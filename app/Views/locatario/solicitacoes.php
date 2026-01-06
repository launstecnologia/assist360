<?php
/**
 * View: Lista de Solicitações do Locatário
 */
$title = 'Minhas Solicitações - Assistência 360°';
$currentPage = 'locatario-solicitacoes';
ob_start();
?>

<!-- Header -->
<div class="mb-8">
    <!-- Botão Voltar -->
    <a href="<?= url($locatario['instancia'] . '/dashboard') ?>" 
       class="inline-flex items-center text-gray-600 hover:text-gray-900 mb-4 transition-colors">
        <i class="fas fa-arrow-left mr-2"></i>
        <span class="text-sm font-medium">Voltar para Dashboard</span>
    </a>
    
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-list mr-2"></i>
                Suas Solicitações
            </h1>
            <p class="text-gray-600 mt-1">
                Acompanhe o status das suas solicitações de assistência
            </p>
        </div>
        <?php 
        $acessoPermitido = $locatario['acesso_permitido'] ?? true; // Por padrão, permite acesso (compatibilidade)
        if ($acessoPermitido): ?>
            <a href="<?= url($locatario['instancia'] . '/nova-solicitacao') ?>" 
               class="inline-flex items-center px-4 py-2 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors">
                <i class="fas fa-plus mr-2"></i>
                Nova Solicitação
            </a>
        <?php else: ?>
            <div class="inline-flex items-center px-4 py-2 bg-gray-300 text-gray-500 font-medium rounded-lg cursor-not-allowed" 
                 title="<?= htmlspecialchars($locatario['motivo_bloqueio'] ?? 'Acesso bloqueado') ?>">
                <i class="fas fa-lock mr-2"></i>
                Nova Solicitação Indisponível
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Stats Summary -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-lg shadow-sm p-4">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-plus-circle text-blue-600"></i>
                </div>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-gray-500">Nova Solicitação</p>
                <p class="text-lg font-bold text-gray-900">
                    <?= count(array_filter($solicitacoes, fn($s) => $s['status_nome'] === 'Nova Solicitação')) ?>
                </p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-4">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-search text-yellow-600"></i>
                </div>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-gray-500">Buscando Prestador</p>
                <p class="text-lg font-bold text-gray-900">
                    <?= count(array_filter($solicitacoes, fn($s) => $s['status_nome'] === 'Buscando Prestador')) ?>
                </p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-4">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-calendar-check text-green-600"></i>
                </div>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-gray-500">Serviço Agendado</p>
                <p class="text-lg font-bold text-gray-900">
                    <?= count(array_filter($solicitacoes, fn($s) => $s['status_nome'] === 'Serviço Agendado')) ?>
                </p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-4">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-clock text-red-600"></i>
                </div>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-gray-500">Pendências</p>
                <p class="text-lg font-bold text-gray-900">
                    <?= count(array_filter($solicitacoes, fn($s) => in_array($s['status_nome'], ['Pendências', 'Aguardando Peça', 'Aguardando Confirmação Mawdy', 'Aguardando Confirmação Locatário']))) ?>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Solicitations List -->
<div class="bg-white rounded-lg shadow-sm">
    <?php if (empty($solicitacoes)): ?>
        <div class="text-center py-12">
            <i class="fas fa-inbox text-4xl text-gray-300 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Nenhuma solicitação encontrada</h3>
            <p class="text-gray-500 mb-6">Você ainda não possui solicitações de assistência.</p>
                <a href="<?= url($locatario['instancia'] . '/nova-solicitacao') ?>" 
                   class="inline-flex items-center px-4 py-2 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-plus mr-2"></i>
                    Criar Primeira Solicitação
                </a>
        </div>
    <?php else: ?>
        <div class="divide-y divide-gray-200">
            <?php foreach ($solicitacoes as $solicitacao): ?>
                <div class="p-6 hover:bg-gray-50 transition-colors">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <!-- Header -->
                            <div class="flex items-center space-x-3 mb-3">
                                <h3 class="text-lg font-medium text-gray-900">
                                    <?= htmlspecialchars($solicitacao['categoria_nome']) ?>
                                </h3>
                                <span class="status-badge status-<?= strtolower(str_replace([' ', '(', ')'], ['-', '', ''], $solicitacao['status_nome'])) ?>">
                                    <?= htmlspecialchars($solicitacao['status_nome']) ?>
                                </span>
                            </div>
                            
                            <!-- Protocol and Description -->
                            <div class="mb-3">
                                <p class="text-sm text-gray-500 mb-1">
                                    <strong>Protocolo:</strong> <?= htmlspecialchars($solicitacao['protocolo_seguradora'] ?? '-') ?>
                                </p>
                                <?php if (!empty($solicitacao['subcategoria_nome'])): ?>
                                    <p class="text-sm text-gray-600 mb-1">
                                        <strong>Tipo:</strong> <?= htmlspecialchars($solicitacao['subcategoria_nome']) ?>
                                    </p>
                                <?php endif; ?>
                                <?php if (!empty($solicitacao['descricao_problema'])): ?>
                                    <p class="text-sm text-gray-600">
                                        <strong>Descrição:</strong> <?= htmlspecialchars($solicitacao['descricao_problema']) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Address -->
                            <?php if (!empty($solicitacao['imovel_endereco'])): ?>
                                <div class="mb-3">
                                    <p class="text-sm text-gray-600">
                                        <i class="fas fa-map-marker-alt mr-1 text-gray-400"></i>
                                        <?= htmlspecialchars($solicitacao['imovel_endereco']) ?>
                                        <?php if (!empty($solicitacao['imovel_numero'])): ?>
                                            , <?= htmlspecialchars($solicitacao['imovel_numero']) ?>
                                        <?php endif; ?>
                                        <?php if (!empty($solicitacao['imovel_complemento'])): ?>
                                            - <?= htmlspecialchars($solicitacao['imovel_complemento']) ?>
                                        <?php endif; ?>
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        <?= htmlspecialchars($solicitacao['imovel_bairro']) ?> - 
                                        <?= htmlspecialchars($solicitacao['imovel_cidade']) ?>/<?= htmlspecialchars($solicitacao['imovel_estado']) ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Dates and Times -->
                            <div class="flex items-center space-x-6 text-sm text-gray-500">
                                <div class="flex items-center">
                                    <i class="fas fa-calendar mr-1"></i>
                                    <span>Criado em: <?= date('d/m/Y', strtotime($solicitacao['created_at'])) ?></span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-clock mr-1"></i>
                                    <span><?= date('H:i', strtotime($solicitacao['created_at'])) ?></span>
                                </div>
                                <?php if (!empty($solicitacao['data_agendamento'])): ?>
                                    <div class="flex items-center">
                                        <i class="fas fa-calendar-check mr-1"></i>
                                        <span>Agendado: <?= date('d/m/Y', strtotime($solicitacao['data_agendamento'])) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Priority -->
                            <?php if (!empty($solicitacao['prioridade']) && $solicitacao['prioridade'] !== 'NORMAL'): ?>
                                <div class="mt-2">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                        <?= $solicitacao['prioridade'] === 'ALTA' ? 'bg-red-100 text-red-800' : 
                                           ($solicitacao['prioridade'] === 'MEDIA' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') ?>">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>
                                        Prioridade <?= $solicitacao['prioridade'] ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Actions -->
                        <div class="flex-shrink-0 ml-6">
                            <div class="flex flex-col space-y-2">
                                <a href="<?= url($locatario['instancia'] . '/solicitacoes/' . $solicitacao['id']) ?>" 
                                   class="inline-flex items-center px-3 py-1 text-xs font-medium text-blue-600 bg-blue-50 rounded-full hover:bg-blue-100 transition-colors">
                                    <i class="fas fa-eye mr-1"></i>
                                    Ver Detalhes
                                </a>
                                
                                <?php if (in_array($solicitacao['status_nome'], ['Nova Solicitação', 'Buscando Prestador'])): ?>
                                    <button onclick="cancelarSolicitacao(<?= $solicitacao['id'] ?>)" 
                                            class="inline-flex items-center px-3 py-1 text-xs font-medium text-red-600 bg-red-50 rounded-full hover:bg-red-100 transition-colors">
                                        <i class="fas fa-times mr-1"></i>
                                        Cancelar
                                    </button>
                                <?php endif; ?>
                                
                                <?php if ($solicitacao['status_nome'] === 'Serviço Agendado' && !empty($solicitacao['data_agendamento'])): ?>
                                    <?php 
                                    $dataAgendamento = strtotime($solicitacao['data_agendamento']);
                                    $hoje = time();
                                    $diferencaDias = ($dataAgendamento - $hoje) / (24 * 60 * 60);
                                    ?>
                                    <?php if ($diferencaDias >= 1): ?>
                                        <button onclick="cancelarSolicitacao(<?= $solicitacao['id'] ?>)" 
                                                class="inline-flex items-center px-3 py-1 text-xs font-medium text-red-600 bg-red-50 rounded-full hover:bg-red-100 transition-colors">
                                            <i class="fas fa-times mr-1"></i>
                                            Cancelar
                                        </button>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-3 py-1 text-xs font-medium text-gray-500 bg-gray-50 rounded-full">
                                            <i class="fas fa-lock mr-1"></i>
                                            Não pode cancelar
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function cancelarSolicitacao(id) {
    if (confirm('Tem certeza que deseja cancelar esta solicitação?')) {
        // Implementar cancelamento via AJAX
        fetch('<?= url('solicitacoes/cancelar') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                id: id,
                motivo: 'Cancelado pelo locatário'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erro ao cancelar solicitação: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao cancelar solicitação');
        });
    }
}
</script>

<?php
$content = ob_get_clean();
include 'app/Views/layouts/locatario.php';
?>
