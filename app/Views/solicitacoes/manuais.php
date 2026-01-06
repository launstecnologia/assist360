<?php
/**
 * View: Solicitações Manuais (Admin)
 * Listagem e gerenciamento de solicitações criadas por usuários não logados
 */
$title = 'Solicitações Manuais - Portal do Operador';
$currentPage = 'solicitacoes-manuais';
ob_start();
?>

<!-- Token CSRF (oculto) -->
<?= \App\Core\View::csrfField() ?>

<!-- Header -->
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-file-alt mr-2"></i>
                Solicitações Manuais
            </h1>
            <p class="text-gray-600 mt-1">
                Gerencie solicitações criadas por usuários não logados
            </p>
        </div>
        <div class="flex gap-3">
            <button onclick="abrirOffcanvasNovaSolicitacaoManual()" 
                    class="inline-flex items-center px-4 py-2 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors">
                <i class="fas fa-plus mr-2"></i>
                Nova Solicitação Manual
            </button>
            <a href="<?= url('admin/kanban') ?>" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                <i class="fas fa-columns mr-2"></i>
                Ver Kanban
            </a>
            <a href="<?= url('admin/solicitacoes') ?>" 
               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-clipboard-list mr-2"></i>
                Solicitações Normais
            </a>
        </div>
    </div>
</div>

<!-- Breadcrumb -->
<nav class="flex mb-6" aria-label="Breadcrumb">
    <ol class="inline-flex items-center space-x-1 md:space-x-3">
        <li class="inline-flex items-center">
            <a href="<?= url('admin/dashboard') ?>" class="text-gray-700 hover:text-blue-600 inline-flex items-center">
                <i class="fas fa-home mr-2"></i>
                Dashboard
            </a>
        </li>
        <li>
            <div class="flex items-center">
                <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                <span class="text-gray-500">Solicitações Manuais</span>
            </div>
        </li>
    </ol>
</nav>

<!-- Estatísticas -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8" id="stats-container">
    <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-blue-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600 mb-1">Total de Solicitações</p>
                <p class="text-3xl font-bold text-gray-900" id="stat-total"><?= $stats['total'] ?? 0 ?></p>
            </div>
            <div class="bg-blue-100 rounded-full p-3">
                <i class="fas fa-file-alt text-2xl text-blue-600"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-yellow-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600 mb-1">Aguardando Migração</p>
                <p class="text-3xl font-bold text-gray-900" id="stat-nao-migradas"><?= $stats['nao_migradas'] ?? 0 ?></p>
            </div>
            <div class="bg-yellow-100 rounded-full p-3">
                <i class="fas fa-clock text-2xl text-yellow-600"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-green-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600 mb-1">Migradas</p>
                <p class="text-3xl font-bold text-gray-900" id="stat-migradas"><?= $stats['migradas'] ?? 0 ?></p>
            </div>
            <div class="bg-green-100 rounded-full p-3">
                <i class="fas fa-check-circle text-2xl text-green-600"></i>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <form method="GET" action="<?= url('admin/solicitacoes-manuais') ?>" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Busca -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
            <input type="text" name="busca" 
                   class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                   placeholder="Nome, CPF..."
                   value="<?= htmlspecialchars($filtros['busca'] ?? '') ?>">
        </div>
        
        <!-- Imobiliária -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Imobiliária</label>
            <select name="imobiliaria_id" 
                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                <option value="">Todas</option>
                <?php foreach ($imobiliarias as $imob): ?>
                    <option value="<?= $imob['id'] ?>" <?= ($filtros['imobiliaria_id'] ?? '') == $imob['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($imob['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- Status -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
            <select name="status_id" 
                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                <option value="">Todos</option>
                <?php foreach ($statusList as $st): ?>
                    <option value="<?= $st['id'] ?>" <?= ($filtros['status_id'] ?? '') == $st['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($st['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- Situação -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Situação</label>
            <select name="migrada" 
                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                <option value="0" <?= (!isset($filtros['migrada_input']) || $filtros['migrada_input'] === '' || $filtros['migrada_input'] === '0' || $filtros['migrada_input'] === null) ? 'selected' : '' ?>>Aguardando Migração</option>
                <option value="1" <?= isset($filtros['migrada_input']) && $filtros['migrada_input'] === '1' ? 'selected' : '' ?>>Migradas</option>
            </select>
        </div>
        
        <!-- Botões -->
        <div class="md:col-span-4 flex justify-end gap-2">
            <a href="<?= url('admin/solicitacoes-manuais') ?>" 
               class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                Limpar
            </a>
            <button type="submit" 
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                <i class="fas fa-search mr-2"></i>
                Filtrar
            </button>
        </div>
    </form>
</div>

<!-- Lista de Solicitações -->
<div class="bg-white rounded-lg shadow-sm" id="lista-solicitacoes-container">
    <?php if (!empty($solicitacoes)): ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200" id="tabela-solicitacoes">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Imobiliária
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Cliente
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Serviço
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Endereço
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Validações
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Data Criação
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Ações
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="tbody-solicitacoes">
                    <?php foreach ($solicitacoes as $solicitacao): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <?php if (!empty($solicitacao['imobiliaria_logo'])): ?>
                                    <img src="<?= url('Public/uploads/logos/' . $solicitacao['imobiliaria_logo']) ?>" 
                                         alt="<?= htmlspecialchars($solicitacao['imobiliaria_nome'] ?? 'Imobiliária') ?>" 
                                         class="h-8 w-auto object-contain max-w-[100px]"
                                         onerror="this.style.display='none';">
                                <?php else: ?>
                                    <span class="text-xs text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($solicitacao['nome_completo']) ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    CPF: <?= htmlspecialchars($solicitacao['cpf']) ?><br>
                                    <?= htmlspecialchars($solicitacao['whatsapp']) ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <?php 
                                $subcategorias = getSubcategorias($solicitacao);
                                $temMultiplas = count($subcategorias) > 1;
                                ?>
                                <div class="text-sm text-gray-900">
                                    <?php if ($temMultiplas): ?>
                                        <div class="font-medium mb-1">Serviços:</div>
                                        <ul class="list-disc list-inside space-y-0.5 text-xs">
                                            <?php foreach ($subcategorias as $nome): ?>
                                                <li><?= htmlspecialchars($nome) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php elseif (!empty($subcategorias)): ?>
                                        <?= htmlspecialchars($subcategorias[0]) ?>
                                    <?php else: ?>
                                        <?= htmlspecialchars($solicitacao['subcategoria_nome'] ?? 'N/A') ?>
                                    <?php endif; ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <?= htmlspecialchars($solicitacao['categoria_nome']) ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">
                                    <?= htmlspecialchars($solicitacao['endereco']) ?>, <?= htmlspecialchars($solicitacao['numero']) ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <?= htmlspecialchars($solicitacao['cidade']) ?> - <?= htmlspecialchars($solicitacao['estado']) ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" 
                                      style="background-color: <?= htmlspecialchars($solicitacao['status_cor']) ?>20; color: <?= htmlspecialchars($solicitacao['status_cor']) ?>;">
                                    <?= htmlspecialchars($solicitacao['status_nome']) ?>
                                </span>
                                <?php if ($solicitacao['migrada']): ?>
                                    <span class="block mt-1 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <i class="fas fa-check mr-1"></i> Migrada
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="space-y-1">
                                    <?php
                                    // Exibir apenas validações aprovadas ou recusadas
                                    $validacaoBolsao = $solicitacao['validacao_bolsao'] ?? 0;
                                    $tipoQualificacao = $solicitacao['tipo_qualificacao'] ?? null;
                                    $validacaoUtilizacao = $solicitacao['validacao_utilizacao'] ?? null;
                                    
                                    // Validação Bolsão
                                    if ($validacaoBolsao == 1):
                                    ?>
                                        <div class="flex items-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-check-circle mr-1"></i> Bolsão: Aprovado
                                        </span>
                                        </div>
                                    <?php elseif ($validacaoBolsao == 0): ?>
                                        <div class="flex items-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            <i class="fas fa-times-circle mr-1"></i> Bolsão: Recusado
                                        </span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php
                                    // Validação Dt Assinatura (REGRA_2)
                                    // Verificar se imobiliária tem API configurada (api_id não nulo ou integracao_ativa = 1)
                                    $imobiliariaTemApi = !empty($solicitacao['imobiliaria_api_id']) || 
                                                        (!empty($solicitacao['imobiliaria_integracao_ativa']) && $solicitacao['imobiliaria_integracao_ativa'] == 1);
                                    if ($tipoQualificacao === 'REGRA_2'):
                                    ?>
                                        <div class="flex items-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-check-circle mr-1"></i> Dt Assinatura: Aprovado
                                        </span>
                                        </div>
                                    <?php else: ?>
                                        <div class="flex items-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                <i class="fas fa-times-circle mr-1"></i> Dt Assinatura: Recusado <?= $imobiliariaTemApi ? 'COM API' : 'SEM API' ?>
                                        </span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php
                                    // Validação Limite Utilização
                                    if ($validacaoUtilizacao === 1 || $validacaoUtilizacao === true):
                                    ?>
                                        <div class="flex items-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <i class="fas fa-check-circle mr-1"></i> Limite Utilização: Aprovado
                                        </span>
                                        </div>
                                    <?php else: ?>
                                        <div class="flex items-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                <i class="fas fa-times-circle mr-1"></i> Limite Utilização: Recusado
                                        </span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php
                                    // Badge de Tipo de Qualificação (apenas se for Recusado ou Cortesia)
                                    if ($tipoQualificacao === 'NAO_QUALIFICADA'): 
                                        $obsQualificacao = $solicitacao['observacao_qualificacao'] ?? '';
                                    ?>
                                        <div class="mt-1">
                                            <?php if (!empty($obsQualificacao)): ?>
                                                <div class="mb-1 text-xs text-gray-700 bg-red-50 border-l-3 border-red-400 p-2 rounded-r shadow-sm">
                                                    <strong class="text-red-900">OBS:</strong> <?= htmlspecialchars($obsQualificacao) ?>
                                                </div>
                                            <?php endif; ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 shadow-sm">
                                                <i class="fas fa-exclamation-triangle mr-1"></i> Recusado
                                            </span>
                                        </div>
                                    <?php elseif ($tipoQualificacao === 'CORTESIA'): 
                                        $obsQualificacao = $solicitacao['observacao_qualificacao'] ?? '';
                                    ?>
                                        <div class="mt-1">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 shadow-sm">
                                                <i class="fas fa-check-circle mr-1"></i> Cortesia
                                            </span>
                                            <?php if (!empty($obsQualificacao)): ?>
                                                <div class="mt-1 text-xs text-gray-700 bg-green-50 border-l-3 border-green-400 p-2 rounded-r shadow-sm">
                                                    <strong class="text-green-900">OBS:</strong> <?= htmlspecialchars($obsQualificacao) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <?= date('d/m/Y H:i', strtotime($solicitacao['created_at'])) ?>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <button type="button" onclick="verDetalhes(<?= $solicitacao['id'] ?>)" 
                                        class="text-blue-600 hover:text-blue-900 mr-3">
                                    <i class="fas fa-eye mr-1"></i> Ver
                                </button>
                                <?php if (!$solicitacao['migrada']): ?>
                                    <button onclick="abrirOffcanvasEditarSolicitacaoManual(<?= $solicitacao['id'] ?>)" 
                                            class="text-yellow-600 hover:text-yellow-900 mr-3">
                                        <i class="fas fa-edit mr-1"></i> Editar
                                    </button>
                                    <button onclick="confirmarMigracao(<?= $solicitacao['id'] ?>)" 
                                            class="text-green-600 hover:text-green-900">
                                        <i class="fas fa-arrow-right mr-1"></i> Migrar
                                    </button>
                                <?php else: ?>
                                    <a href="<?= url('admin/solicitacoes/show/' . $solicitacao['migrada_para_solicitacao_id']) ?>" 
                                       class="text-purple-600 hover:text-purple-900">
                                        <i class="fas fa-external-link-alt mr-1"></i> Ver Original
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="text-center py-12">
            <i class="fas fa-inbox text-5xl text-gray-300 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Nenhuma solicitação encontrada</h3>
            <p class="text-gray-500">Não há solicitações manuais no momento.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Offcanvas para Criar/Editar Solicitação Manual -->
<div id="offcanvasSolicitacaoManual" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 transition-opacity" onclick="fecharOffcanvasSolicitacaoManual()"></div>
    <div id="offcanvasSolicitacaoManualPanel" class="fixed right-0 top-0 h-full w-full md:w-[95%] lg:w-[1000px] bg-gray-50 shadow-xl transform translate-x-full transition-transform duration-300 ease-in-out overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 z-10">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class="fas fa-file-alt text-gray-600"></i>
                    <h2 id="offcanvasSolicitacaoManualTitle" class="text-xl font-bold text-gray-900">Solicitação Manual</h2>
                </div>
                <button onclick="fecharOffcanvasSolicitacaoManual()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        <div id="offcanvasSolicitacaoManualContent" class="p-6">
            <div id="loadingSolicitacaoManual" class="flex items-center justify-center py-12">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin text-4xl text-blue-600 mb-4"></i>
                    <p class="text-gray-600">Carregando...</p>
                </div>
            </div>
            <div id="formSolicitacaoManual" class="hidden"></div>
        </div>
    </div>
</div>

<!-- Offcanvas de Detalhes -->
<div id="offcanvasDetalhes" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 transition-opacity" onclick="fecharOffcanvasDetalhes()"></div>
    <div id="offcanvasDetalhesPanel" class="fixed right-0 top-0 h-full w-full md:w-[95%] lg:w-[1000px] bg-gray-50 shadow-xl transform translate-x-full transition-transform duration-300 ease-in-out overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 z-10">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class="fas fa-file-alt text-gray-600"></i>
                    <h2 class="text-xl font-bold text-gray-900">Detalhes da Solicitação Manual</h2>
                </div>
                <button onclick="fecharOffcanvasDetalhes()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        </div>
        <div id="offcanvasDetalhesContent" class="p-6">
            <!-- Conteúdo será preenchido via JavaScript -->
            <div class="flex items-center justify-center py-12">
                <i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i>
            </div>
        </div>
    </div>
</div>

<script>
// Ver detalhes da solicitação
async function verDetalhes(id) {
    const offcanvas = document.getElementById('offcanvasDetalhes');
    const panel = document.getElementById('offcanvasDetalhesPanel');
    const content = document.getElementById('offcanvasDetalhesContent');
    
    if (!offcanvas || !panel || !content) {
        console.error('Offcanvas não encontrado!');
        alert('Erro: Offcanvas não encontrado. Recarregue a página.');
        return;
    }
    
    offcanvas.classList.remove('hidden');
    setTimeout(() => panel.classList.remove('translate-x-full'), 10);
    content.innerHTML = '<div class="flex items-center justify-center py-12"><i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i></div>';
    
    try {
        const response = await fetch(`<?= url('admin/solicitacoes-manuais') ?>/${id}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!response.ok) {
            throw new Error('Erro ao buscar dados: ' + response.status);
        }
        
        const data = await response.json();
        
        if (!data || !data.success) {
            throw new Error(data?.message || 'Erro ao buscar detalhes da solicitação');
        }
        
        const s = data.solicitacao;
        const horarios = s.horarios_preferenciais || [];
        const fotos = s.fotos || [];
        const tipoQualificacao = s.tipo_qualificacao || null;
        // Bolsão aprovado se tipo_qualificacao === 'BOLSAO' OU validacao_bolsao == 1
        const bolsaoAprovado = tipoQualificacao === 'BOLSAO' || s.validacao_bolsao == 1 || s.validacao_bolsao === true;
        
        content.innerHTML = `
            <div class="space-y-6">
                <!-- Dados Pessoais -->
                <div class="bg-gray-50 rounded-lg p-6 mb-6 border border-gray-200">
                    <h4 class="text-base font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-user mr-2 text-blue-600"></i>
                        Dados Pessoais
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-sm">
                        <div class="space-y-1">
                            <span class="font-medium text-gray-600 block mb-1">Nome:</span>
                            <p class="text-gray-900">${s.nome_completo}</p>
                        </div>
                        <div class="space-y-1">
                            <span class="font-medium text-gray-600 block mb-1">CPF:</span>
                            <p class="text-gray-900">${s.cpf}</p>
                        </div>
                        <div class="space-y-1">
                            <span class="font-medium text-gray-600 block mb-1">WhatsApp:</span>
                            <p class="text-gray-900">
                                <a href="https://wa.me/55${s.whatsapp.replace(/\D/g, '')}" target="_blank" class="text-green-600 hover:text-green-700">
                                    <i class="fab fa-whatsapp mr-1"></i>${s.whatsapp}
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Endereço -->
                <div class="bg-gray-50 rounded-lg p-6 mb-6 border border-gray-200">
                    <h4 class="text-base font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-map-marker-alt mr-2 text-red-600"></i>
                        Endereço do Imóvel
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                        <div class="space-y-1">
                            <span class="font-medium text-gray-600 block mb-1">Tipo:</span>
                            <p class="text-gray-900">${s.tipo_imovel}${s.subtipo_imovel ? ' - ' + s.subtipo_imovel : ''}</p>
                        </div>
                        <div class="space-y-1">
                            <span class="font-medium text-gray-600 block mb-1">CEP:</span>
                            <p class="text-gray-900">${s.cep}</p>
                        </div>
                        <div class="md:col-span-2 space-y-1">
                            <span class="font-medium text-gray-600 block mb-1">Endereço Completo:</span>
                            <p class="text-gray-900">
                                ${s.endereco}, ${s.numero}${s.complemento ? ' - ' + s.complemento : ''}<br>
                                ${s.bairro}, ${s.cidade} - ${s.estado}
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Serviço -->
                <div class="bg-gray-50 rounded-lg p-6 mb-6 border border-gray-200">
                    <h4 class="text-base font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-cog mr-2 text-purple-600"></i>
                        Serviço Solicitado
                    </h4>
                    <div class="space-y-4 text-sm">
                        <div class="space-y-1">
                            <span class="font-medium text-gray-600 block mb-1">Categoria:</span>
                            <p class="text-gray-900">${s.categoria_nome}</p>
                        </div>
                        <div class="space-y-1">
                            <span class="font-medium text-gray-600">${s.observacoes && s.observacoes.includes('[SUBCATEGORIAS_IDS:') ? 'Serviços:' : 'Subcategoria:'}</span>
                            ${s.observacoes && s.observacoes.includes('[SUBCATEGORIAS_IDS:') ? `
                                <div class="mt-1">
                                    ${(() => {
                                        const match = s.observacoes.match(/\[SUBCATEGORIAS_IDS:\s*(\[[^\]]+\])\]/);
                                        if (match) {
                                            try {
                                                const ids = JSON.parse(match[1]);
                                                if (Array.isArray(ids) && ids.length > 1) {
                                                    // Buscar nomes das subcategorias via AJAX ou usar a primeira como fallback
                                                    return '<ul class="list-disc list-inside space-y-1 text-gray-900">' +
                                                        ids.map((id, index) => {
                                                            // Por enquanto, mostrar ID. Em produção, buscar nome via API
                                                            return '<li>Serviço ' + (index + 1) + ' (ID: ' + id + ')</li>';
                                                        }).join('') +
                                                        '</ul>';
                                                }
                                            } catch(e) {}
                                        }
                                        // Fallback: extrair da lista formatada
                                        const servicosMatch = s.observacoes.match(/Serviços solicitados \(\d+\):\s*\n((?:\d+\.\s*[^\n]+\n?)+)/);
                                        if (servicosMatch) {
                                            const linhas = servicosMatch[1].split('\\n').filter(l => l.trim());
                                            return '<ul class="list-disc list-inside space-y-1 text-gray-900">' +
                                                linhas.map(linha => {
                                                    const match = linha.match(/^\d+\.\s*(.+)$/);
                                                    return '<li>' + (match ? match[1].trim() : linha.trim()) + '</li>';
                                                }).join('') +
                                                '</ul>';
                                        }
                                        return '<p class="text-gray-900">' + (s.subcategoria_nome || 'N/A') + '</p>';
                                    })()}
                                </div>
                            ` : `
                                <p class="text-gray-900">${s.subcategoria_nome || 'N/A'}</p>
                            `}
                        </div>
                        <div class="space-y-1">
                            <span class="font-medium text-gray-600 block mb-1">Descrição do Problema:</span>
                            <p class="text-gray-900 whitespace-pre-wrap">${s.descricao_problema}</p>
                        </div>
                    </div>
                </div>
                
                <!-- Horários -->
                <div class="bg-gray-50 rounded-lg p-6 mb-6 border border-gray-200">
                    <h4 class="text-base font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-clock mr-2 text-orange-600"></i>
                        Horários Preferenciais
                    </h4>
                    ${horarios.length > 0 ? `
                        <ul class="text-sm text-gray-900 space-y-1">
                            ${horarios.map(h => `<li><i class="fas fa-calendar-alt mr-2 text-gray-400"></i>${h}</li>`).join('')}
                        </ul>
                    ` : '<p class="text-sm text-gray-500">Nenhum horário informado</p>'}
                </div>
                
                <!-- Fotos -->
                ${fotos.length > 0 ? `
                    <div class="bg-gray-50 rounded-lg p-6 mb-6 border border-gray-200">
                        <h4 class="text-base font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-images mr-2 text-green-600"></i>
                            Fotos Anexadas
                        </h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            ${fotos.map(foto => {
                                // Algumas solicitações manuais armazenam apenas o nome do arquivo,
                                // outras podem armazenar a URL completa. Normalizar aqui.
                                const baseUrl = '<?= url('Public/uploads/solicitacoes/') ?>';
                                let urlFoto = foto || '';
                                
                                // Se não começar com http ou /, considerar que é apenas o nome do arquivo
                                if (urlFoto && !urlFoto.startsWith('http') && !urlFoto.startsWith('/')) {
                                    urlFoto = baseUrl + encodeURIComponent(urlFoto);
                                }
                                
                                return `
                                    <a href="${urlFoto}" target="_blank" class="block">
                                        <img src="${urlFoto}" class="w-full h-24 object-cover rounded-lg border border-gray-200 hover:opacity-75 transition-opacity" onerror="this.parentElement.style.display='none';">
                                </a>
                                `;
                            }).join('')}
                        </div>
                    </div>
                ` : ''}
                
                <!-- 1. Histórico de Validação -->
                <div class="bg-gray-50 rounded-lg p-6 mb-6 border border-gray-200">
                    <h4 class="text-base font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-check-circle mr-2 text-green-600"></i>
                        HISTÓRICO DE VALIDAÇÃO
                    </h4>
                    <div class="space-y-3 text-sm">
                        <div class="flex items-center justify-between py-3 border-b border-gray-300">
                            <span class="font-medium text-gray-700">BOLSÃO:</span>
                            <span class="px-3 py-1 rounded-full text-xs font-medium ` + (bolsaoAprovado ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') + `">
                                ` + (bolsaoAprovado ? 'APROVADO' : 'RECUSADO') + `
                            </span>
                        </div>
                        ` + (!bolsaoAprovado ? `
                        <div class="flex items-center justify-between py-3 border-b border-gray-300">
                            <span class="font-medium text-gray-700">DT ASSINATURA:</span>
                            <span class="px-3 py-1 rounded-full text-xs font-medium ` + (tipoQualificacao === 'REGRA_2' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') + `">
                                ` + (tipoQualificacao === 'REGRA_2' ? 'APROVADO' : 'RECUSADO ' + ((s.imobiliaria_api_id || s.imobiliaria_integracao_ativa == 1) ? 'COM API' : 'SEM API') + '') + `
                            </span>
                        </div>
                        ` : '') + `
                        <div class="flex items-center justify-between py-3">
                            <span class="font-medium text-gray-700">LIMITE UTILIZAÇÃO:</span>
                            <span class="px-3 py-1 rounded-full text-xs font-medium ` + (() => {
                                // Limite Utilização: Verificar tanto validacao_utilizacao quanto limite de categoria por contrato
                                // 1. Verificar se validacao_utilizacao está marcado como 0 (limite de CPF excedido)
                                const limiteCpfRecusado = s.validacao_utilizacao === 0 || s.validacao_utilizacao === '0' || s.validacao_utilizacao === false;
                                
                                // 2. Verificar se há mensagem de limite de categoria excedido nas observações
                                const observacoes = s.observacoes || '';
                                const limiteCategoriaExcedido = observacoes.includes('EXCEDEU_LIMITE_QUANTIDADE') || observacoes.includes('Limite de solicitações da categoria excedido');
                                
                                // Limite está recusado se qualquer uma das condições for verdadeira
                                const limiteRecusado = limiteCpfRecusado || limiteCategoriaExcedido;
                                
                                return limiteRecusado ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800';
                            })() + `">
                                ` + (() => {
                                const limiteCpfRecusado = s.validacao_utilizacao === 0 || s.validacao_utilizacao === '0' || s.validacao_utilizacao === false;
                                const observacoes = s.observacoes || '';
                                const limiteCategoriaExcedido = observacoes.includes('EXCEDEU_LIMITE_QUANTIDADE') || observacoes.includes('Limite de solicitações da categoria excedido');
                                const limiteRecusado = limiteCpfRecusado || limiteCategoriaExcedido;
                                
                                return limiteRecusado ? 'RECUSADO' : 'APROVADO';
                            })() + `
                            </span>
                        </div>
                        ` + (tipoQualificacao === 'NAO_QUALIFICADA' && s.observacao_qualificacao ? `
                        <div class="mt-3 pt-3 border-t border-gray-200">
                            <div class="flex items-start gap-2 text-sm text-red-800 bg-red-50 border-l-4 border-red-400 p-3 rounded-r">
                                <i class="fas fa-exclamation-triangle text-red-600 mt-0.5"></i>
                                <div>
                                    <strong class="text-red-900">Observação da Validação:</strong>
                                    <p class="mt-1 text-red-700">${s.observacao_qualificacao}</p>
                                </div>
                            </div>
                        </div>
                        ` : '') + `
                    </div>
                </div>
                
                <!-- 2. Condição atual (mostrar todas as validações recusadas) -->
                <div class="bg-gray-50 rounded-lg p-6 mb-6 border border-gray-200">
                    <h4 class="text-base font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-info-circle mr-2 text-orange-600"></i>
                        Condição atual
                    </h4>
                    <div class="space-y-2">
                        ` + (
                            // Listar todas as validações recusadas
                            (() => {
                                const recusas = [];
                                if (!bolsaoAprovado) {
                                    recusas.push('<div class="flex items-center gap-2 text-sm text-red-800 bg-red-50 border-l-4 border-red-400 p-2 rounded-r"><i class="fas fa-times-circle text-red-600"></i><span><strong>Bolsão:</strong> CPF não encontrado na listagem da imobiliária</span></div>');
                                }
                                if (s.validacao_utilizacao === 0 || s.validacao_utilizacao === false) {
                                    recusas.push('<div class="flex items-center gap-2 text-sm text-red-800 bg-red-50 border-l-4 border-red-400 p-2 rounded-r"><i class="fas fa-times-circle text-red-600"></i><span><strong>Limite Utilização:</strong> Contrato sem utilização disponível</span></div>');
                                }
                                if (tipoQualificacao === 'NAO_QUALIFICADA' && s.observacao_qualificacao) {
                                    recusas.push('<div class="flex items-start gap-2 text-sm text-red-800 bg-red-50 border-l-4 border-red-400 p-3 rounded-r"><i class="fas fa-exclamation-triangle text-red-600 mt-0.5"></i><div><strong>Recusado:</strong> ' + s.observacao_qualificacao + '</div></div>');
                                }
                                if (recusas.length === 0) {
                                    return '<div class="text-sm text-gray-600 bg-gray-50 border-l-4 border-gray-300 p-2 rounded-r"><i class="fas fa-check-circle text-gray-500 mr-2"></i>Nenhuma recusa registrada</div>';
                                }
                                return recusas.join('');
                            })()
                        ) + `
                    </div>
                </div>
                
                <!-- 3. Atribuição final -->
                <div class="bg-gray-50 rounded-lg p-6 mb-6 border border-gray-200">
                    <h4 class="text-base font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-tag mr-2 text-indigo-600"></i>
                        Atribuição final
                    </h4>
                    
                    <!-- Campos de edição - Dropdown e Observação lado a lado -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
                        <div class="lg:col-span-1">
                            <label class="block text-xs font-medium text-gray-700 mb-2">Atribuição final:</label>
                            <select id="tipo-qualificacao-${s.id}" onchange="toggleObservacaoQualificacao(${s.id}, this.value)" 
                                    class="w-full text-sm border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                                <option value="">-- Selecione --</option>
                                <option value="CORTESIA" ${tipoQualificacao === 'CORTESIA' ? 'selected' : ''}>Cortesia</option>
                                <option value="NAO_QUALIFICADA" ${tipoQualificacao === 'NAO_QUALIFICADA' ? 'selected' : ''}>Recusado</option>
                            </select>
                        </div>
                        
                        <div id="obs-qualificacao-${s.id}" class="lg:col-span-2 ${(tipoQualificacao === 'CORTESIA' || tipoQualificacao === 'NAO_QUALIFICADA') ? '' : 'hidden'}">
                            <label class="block text-xs font-medium text-gray-700 mb-2">
                                <i class="fas fa-sticky-note mr-1 text-indigo-600"></i>
                                Observação${tipoQualificacao === 'NAO_QUALIFICADA' ? ' <span class="text-red-500">*</span>' : ''}:
                            </label>
                            <textarea id="textarea-obs-${s.id}" 
                                      rows="4" 
                                      class="w-full text-sm border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all resize-y ${tipoQualificacao === 'NAO_QUALIFICADA' ? 'border-red-300' : ''}"
                                      placeholder="${tipoQualificacao === 'NAO_QUALIFICADA' ? 'Digite uma observação (obrigatório)...' : 'Digite uma observação sobre esta qualificação...'}"
                                      ${tipoQualificacao === 'NAO_QUALIFICADA' ? 'required' : ''}>${(s.observacao_qualificacao || '').trim()}</textarea>
                        </div>
                    </div>
                    
                </div>
                
                <!-- Status e Ações -->
                <div class="border-t border-gray-200 pt-4">
                    <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status Atual</label>
                        <select id="status-select-${s.id}" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                                ${data.statusList.map(st => `
                                    <option value="${st.id}" ${st.id == s.status_id ? 'selected' : ''}>${st.nome}</option>
                                `).join('')}
                            </select>
                    </div>
                    
                    <!-- Botão Salvar Tudo -->
                    <div class="mt-6 pt-4 border-t border-gray-200">
                        <div class="flex justify-end">
                            <button onclick="salvarTudoDetalhes(${s.id})" 
                                    class="px-6 py-2.5 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all shadow-sm">
                                <i class="fas fa-save mr-2"></i>Salvar Tudo
                                </button>
                            </div>
                        </div>
                </div>
                
                <!-- Informações Técnicas -->
                <div class="text-xs text-gray-500 border-t border-gray-200 pt-4">
                    <p><strong>ID:</strong> ${s.id}</p>
                    <p><strong>Criada em:</strong> ${new Date(s.created_at).toLocaleString('pt-BR')}</p>
                    <p><strong>Imobiliária:</strong> ${s.imobiliaria_nome}</p>
                    <p><strong>Termos Aceitos:</strong> ${s.termos_aceitos ? 'Sim' : 'Não'}</p>
                </div>
            </div>
        `;
    } catch (error) {
        console.error('Erro ao carregar detalhes:', error);
        content.innerHTML = `
            <div class="text-center py-12">
                <i class="fas fa-exclamation-triangle text-4xl text-red-400 mb-4"></i>
                <h4 class="text-lg font-medium text-gray-900 mb-2">Erro ao carregar detalhes</h4>
                <p class="text-gray-600">${error.message || 'Erro desconhecido'}</p>
                <button onclick="fecharOffcanvasDetalhes()" class="mt-4 px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                    Fechar
                </button>
            </div>
        `;
    }
}

// Atualizar status
async function atualizarStatus(id) {
    const select = document.getElementById(`status-select-${id}`);
    const statusId = select.value;
    
    if (!confirm('Deseja realmente alterar o status desta solicitação?')) {
        return;
    }
    
    try {
        const response = await fetch(`<?= url('admin/solicitacoes-manuais') ?>/${id}/status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ status_id: statusId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Status atualizado com sucesso!');
            location.reload();
        } else {
            alert('Erro: ' + data.message);
        }
    } catch (error) {
        alert('Erro ao atualizar status: ' + error.message);
    }
}

// Migrar solicitação
async function migrarSolicitacao(id) {
    if (!confirm('Deseja migrar esta solicitação para o sistema principal? Esta ação não pode ser desfeita.')) {
        return;
    }
    
    try {
        const response = await fetch(`<?= url('admin/solicitacoes-manuais') ?>/${id}/migrar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Solicitação migrada com sucesso! ID da nova solicitação: #' + data.solicitacao_id);
            location.reload();
        } else {
            alert('Erro: ' + data.message);
        }
    } catch (error) {
        alert('Erro ao migrar solicitação: ' + error.message);
    }
}

// Confirmar migração direto da lista
function confirmarMigracao(id) {
    migrarSolicitacao(id);
}

// Fechar offcanvas de detalhes
function fecharOffcanvasDetalhes() {
    const offcanvas = document.getElementById('offcanvasDetalhes');
    const panel = document.getElementById('offcanvasDetalhesPanel');
    
    if (panel) {
        panel.classList.add('translate-x-full');
    }
    setTimeout(() => {
        if (offcanvas) {
            offcanvas.classList.add('hidden');
        }
    }, 300);
}

// Mostrar/ocultar campo de observação baseado no tipo de qualificação
function toggleObservacaoQualificacao(id, tipoQualificacao) {
    const obsDiv = document.getElementById(`obs-qualificacao-${id}`);
    if (obsDiv) {
        if (tipoQualificacao === 'CORTESIA' || tipoQualificacao === 'NAO_QUALIFICADA') {
            obsDiv.classList.remove('hidden');
            // Garantir que o textarea existe
            let textarea = document.getElementById(`textarea-obs-${id}`);
            if (!textarea) {
                const label = document.createElement('label');
                label.className = 'block text-xs font-medium text-gray-700 mb-2';
                // Marcar como obrigatório se for NAO_QUALIFICADA
                const isObrigatorio = tipoQualificacao === 'NAO_QUALIFICADA';
                label.innerHTML = '<i class="fas fa-sticky-note mr-1 text-indigo-600"></i> Observação' + (isObrigatorio ? ' <span class="text-red-500">*</span>' : '') + ':';
                
                textarea = document.createElement('textarea');
                textarea.id = `textarea-obs-${id}`;
                textarea.rows = 4;
                textarea.className = 'w-full text-sm border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all resize-y' + (isObrigatorio ? ' border-red-300' : '');
                textarea.placeholder = isObrigatorio ? 'Digite uma observação (obrigatório)...' : 'Digite uma observação sobre esta qualificação...';
                textarea.required = isObrigatorio;
                
                obsDiv.innerHTML = '';
                obsDiv.appendChild(label);
                obsDiv.appendChild(textarea);
            } else {
                // Atualizar placeholder e estilo se já existe
                if (tipoQualificacao === 'NAO_QUALIFICADA') {
                    textarea.placeholder = 'Digite uma observação (obrigatório)...';
                    textarea.required = true;
                    textarea.classList.add('border-red-300');
                    // Atualizar label para mostrar asterisco
                    const label = obsDiv.querySelector('label');
                    if (label && !label.innerHTML.includes('<span class="text-red-500">*</span>')) {
                        label.innerHTML = label.innerHTML.replace('Observação:', 'Observação <span class="text-red-500">*</span>:');
                    }
                } else {
                    textarea.placeholder = 'Digite uma observação sobre esta qualificação...';
                    textarea.required = false;
                    textarea.classList.remove('border-red-300');
                    // Remover asterisco do label
                    const label = obsDiv.querySelector('label');
                    if (label) {
                        label.innerHTML = label.innerHTML.replace(' <span class="text-red-500">*</span>', '');
                    }
                }
            }
        } else {
            obsDiv.classList.add('hidden');
        }
    }
}

// Salvar tudo (qualificação, observação e status)
async function salvarTudoDetalhes(id) {
    const csrfToken = document.querySelector('input[name="_token"]')?.value || 
                      document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    const selectQualificacao = document.getElementById(`tipo-qualificacao-${id}`);
    const tipoQualificacao = selectQualificacao ? selectQualificacao.value : '';
    const textareaObs = document.getElementById(`textarea-obs-${id}`);
    const observacoesQualificacao = textareaObs ? textareaObs.value.trim() : '';
    const selectStatus = document.getElementById(`status-select-${id}`);
    const statusId = selectStatus ? selectStatus.value : '';
    
    // Validação: se for NAO_QUALIFICADA, a observação é obrigatória
    if (tipoQualificacao === 'NAO_QUALIFICADA' && !observacoesQualificacao) {
        alert('A observação é obrigatória para solicitações recusadas.');
        if (textareaObs) {
            textareaObs.focus();
        }
        return;
    }
    
    try {
        // Sempre salvar qualificação (mesmo que seja vazia para limpar o campo)
        const responseQualificacao = await fetch(`<?= url("admin/solicitacoes") ?>/manual/${id}/atualizar-qualificacao`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                _token: csrfToken || '',
                tipo_qualificacao: tipoQualificacao || '',
                observacoes_qualificacao: observacoesQualificacao
            })
        });
        
        const dataQualificacao = await responseQualificacao.json();
        if (!dataQualificacao.success) {
            throw new Error(dataQualificacao.error || 'Erro ao salvar qualificação');
        }
        
        // Salvar status apenas se não for NAO_QUALIFICADA
        // Quando é NAO_QUALIFICADA, o backend já atualiza o status automaticamente para "Não qualificado"
        // Então não devemos sobrescrever nesse caso
        let novoStatusId = statusId;
        if (tipoQualificacao !== 'NAO_QUALIFICADA' && statusId) {
            const responseStatus = await fetch(`<?= url("admin/solicitacoes-manuais") ?>/${id}/status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status_id: statusId })
            });
            
            const dataStatus = await responseStatus.json();
            if (!dataStatus.success) {
                throw new Error(dataStatus.error || 'Erro ao salvar status');
            }
        } else if (tipoQualificacao === 'NAO_QUALIFICADA') {
            // Quando é NAO_QUALIFICADA, buscar o ID do status "Não qualificado" para atualizar o select
            const responseStatus = await fetch(`<?= url("admin/solicitacoes-manuais") ?>/${id}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const dataAtualizado = await responseStatus.json();
            if (dataAtualizado && dataAtualizado.success && dataAtualizado.solicitacao) {
                novoStatusId = dataAtualizado.solicitacao.status_id;
            }
        }
        
        // Atualizar o select de status imediatamente no HTML
        const selectStatus = document.getElementById(`status-select-${id}`);
        if (selectStatus && novoStatusId) {
            selectStatus.value = novoStatusId;
        }
        
        alert('Alterações salvas com sucesso!');
        // Fechar o offcanvas
        fecharOffcanvasDetalhes();
        // Recarregar a página para atualizar a lista/tabela
        location.reload();
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao salvar: ' + error.message);
    }
}

// Salvar qualificação com observação (mantida para compatibilidade)
async function salvarQualificacaoComObservacao(tipo, id) {
    const csrfToken = document.querySelector('input[name="_token"]')?.value || 
                      document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    const select = document.getElementById(`tipo-qualificacao-${id}`);
    const tipoQualificacao = select ? select.value : '';
    const textarea = document.getElementById(`textarea-obs-${id}`);
    const observacoesQualificacao = textarea ? textarea.value.trim() : '';
    
    if (!tipoQualificacao) {
        alert('Por favor, selecione uma atribuição final.');
        return;
    }
    
    // Validar observação obrigatória para NAO_QUALIFICADA
    if (tipoQualificacao === 'NAO_QUALIFICADA' && !observacoesQualificacao) {
        alert('Observação é obrigatória para solicitações não qualificadas.');
        // Focar no campo de observação se existir
        if (textarea) {
            textarea.focus();
        }
        return;
    }
    
    // Observação não é obrigatória para CORTESIA, mas recomendada
    
    try {
        const response = await fetch(`<?= url("admin/solicitacoes") ?>/` + tipo + '/' + id + '/atualizar-qualificacao', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                _token: csrfToken || '',
                tipo_qualificacao: tipoQualificacao,
                observacoes_qualificacao: observacoesQualificacao
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Atribuição final atualizada com sucesso!');
            // Fechar modal e recarregar a página para atualizar cards e modal
            fecharModal('modal-detalhes-' + id);
            location.reload();
        } else {
            alert('Erro ao atualizar: ' + (data.error || 'Erro desconhecido'));
            // Recarregar modal para restaurar valor original
            verDetalhes(id);
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao atualizar atribuição final: ' + error.message);
        // Recarregar modal para restaurar valor original
        verDetalhes(id);
    }
}

// Fechar offcanvas com ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const offcanvasDetalhes = document.getElementById('offcanvasDetalhes');
        if (offcanvasDetalhes && !offcanvasDetalhes.classList.contains('hidden')) {
            fecharOffcanvasDetalhes();
        }
    }
});

// ========== OFFCANVAS SOLICITAÇÃO MANUAL ==========
let modoOffcanvasManual = null; // 'criar' ou 'editar'
let solicitacaoManualEditandoId = null;

function abrirOffcanvasNovaSolicitacaoManual() {
    modoOffcanvasManual = 'criar';
    solicitacaoManualEditandoId = null;
    
    const offcanvas = document.getElementById('offcanvasSolicitacaoManual');
    const panel = document.getElementById('offcanvasSolicitacaoManualPanel');
    const loading = document.getElementById('loadingSolicitacaoManual');
    const form = document.getElementById('formSolicitacaoManual');
    const title = document.getElementById('offcanvasSolicitacaoManualTitle');
    
    title.textContent = 'Nova Solicitação Manual';
    
    offcanvas.classList.remove('hidden');
    setTimeout(() => panel.classList.remove('translate-x-full'), 10);
    
    loading.classList.remove('hidden');
    form.classList.add('hidden');
    
    // Buscar dados via API
    fetch('<?= url("admin/solicitacoes-manuais/nova/api") ?>', {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loading.classList.add('hidden');
            form.classList.remove('hidden');
            renderizarFormularioCriarManual(data);
        } else {
            alert('Erro ao carregar dados: ' + (data.error || 'Erro desconhecido'));
            fecharOffcanvasSolicitacaoManual();
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao carregar dados do formulário');
        fecharOffcanvasSolicitacaoManual();
    });
}

function abrirOffcanvasEditarSolicitacaoManual(id) {
    modoOffcanvasManual = 'editar';
    solicitacaoManualEditandoId = id;
    
    const offcanvas = document.getElementById('offcanvasSolicitacaoManual');
    const panel = document.getElementById('offcanvasSolicitacaoManualPanel');
    const loading = document.getElementById('loadingSolicitacaoManual');
    const form = document.getElementById('formSolicitacaoManual');
    const title = document.getElementById('offcanvasSolicitacaoManualTitle');
    
    title.textContent = 'Editar Solicitação Manual';
    
    offcanvas.classList.remove('hidden');
    setTimeout(() => panel.classList.remove('translate-x-full'), 10);
    
    loading.classList.remove('hidden');
    form.classList.add('hidden');
    
    // Buscar dados via API
    fetch(`<?= url("admin/solicitacoes-manuais") ?>/${id}/api`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loading.classList.add('hidden');
            form.classList.remove('hidden');
            renderizarFormularioEditarManual(data);
        } else {
            alert('Erro ao carregar dados: ' + (data.error || 'Erro desconhecido'));
            fecharOffcanvasSolicitacaoManual();
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao carregar dados da solicitação');
        fecharOffcanvasSolicitacaoManual();
    });
}

function fecharOffcanvasSolicitacaoManual() {
    const offcanvas = document.getElementById('offcanvasSolicitacaoManual');
    const panel = document.getElementById('offcanvasSolicitacaoManualPanel');
    
    panel.classList.add('translate-x-full');
    setTimeout(() => {
        offcanvas.classList.add('hidden');
        modoOffcanvasManual = null;
        solicitacaoManualEditandoId = null;
        document.getElementById('formSolicitacaoManual').innerHTML = '';
    }, 300);
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatarCPF(cpf) {
    if (!cpf) return '';
    const cpfLimpo = cpf.replace(/\D/g, '');
    return cpfLimpo.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
}

function formatarWhatsApp(whatsapp) {
    if (!whatsapp) return '';
    const wppLimpo = whatsapp.replace(/\D/g, '');
    if (wppLimpo.length <= 10) {
        return wppLimpo.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
    } else {
        return wppLimpo.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
    }
}

function formatarCEP(cep) {
    if (!cep) return '';
    const cepLimpo = cep.replace(/\D/g, '');
    return cepLimpo.replace(/(\d{5})(\d{3})/, '$1-$2');
}

function renderizarFormularioCriarManual(data) {
    const form = document.getElementById('formSolicitacaoManual');
    const csrfToken = document.querySelector('input[name="_token"]')?.value || '';
    
    // Gerar options para imobiliárias
    let imobiliariasOptions = '<option value="">Selecione...</option>';
    data.imobiliarias.forEach(imob => {
        imobiliariasOptions += `<option value="${imob.id}">${escapeHtml(imob.nome)}</option>`;
    });
    
    // Gerar options para status
    let statusOptions = '<option value="">Status Padrão (Nova Solicitação)</option>';
    data.statusList.forEach(status => {
        statusOptions += `<option value="${status.id}">${escapeHtml(status.nome)}</option>`;
    });
    
    // Gerar options para categorias
    let categoriasOptions = '<option value="">Selecione...</option>';
    data.categorias.forEach(cat => {
        categoriasOptions += `<option value="${cat.id}">${escapeHtml(cat.nome)}</option>`;
    });
    
    // Estados
    const estados = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];
    let estadosOptions = '<option value="">Selecione...</option>';
    estados.forEach(uf => {
        estadosOptions += `<option value="${uf}">${uf}</option>`;
    });
    
    form.innerHTML = `
        <form id="formCriarEditarSolicitacaoManual" onsubmit="salvarSolicitacaoManual(event)" enctype="multipart/form-data" class="space-y-6">
            <input type="hidden" name="_token" value="${csrfToken}">
            
            <!-- Dados Pessoais -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-user mr-2 text-blue-600"></i>
                    Dados Pessoais
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Imobiliária *</label>
                        <select name="imobiliaria_id" id="imobiliaria_id-offcanvas" required class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
                            ${imobiliariasOptions}
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">CPF *</label>
                        <input type="text" name="cpf" id="cpf-offcanvas" required maxlength="14" class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all" placeholder="000.000.000-00">
                        <p class="text-xs text-gray-500 mt-1" id="cpf-status-offcanvas"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nome Completo *</label>
                        <input type="text" name="nome_completo" id="nome_completo-offcanvas" required class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">WhatsApp *</label>
                        <input type="text" name="whatsapp" id="whatsapp-offcanvas" required class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all" placeholder="(00) 00000-0000">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nº do Contrato</label>
                        <input type="text" name="numero_contrato" class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="status_id" class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
                            ${statusOptions}
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Endereço -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-map-marker-alt mr-2 text-red-600"></i>
                    Endereço do Imóvel
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Imóvel *</label>
                        <select name="tipo_imovel" required class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
                            <option value="">Selecione...</option>
                            <option value="Residencial">Residencial</option>
                            <option value="Comercial">Comercial</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Subtipo</label>
                        <input type="text" name="subtipo_imovel" class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all" placeholder="Ex: Apartamento, Casa, Loja...">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">CEP *</label>
                        <div class="flex gap-2">
                            <input type="text" name="cep" id="cep-offcanvas" required maxlength="9" class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all" placeholder="00000-000">
                            <button type="button" id="btn-buscar-cep-offcanvas" class="px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                                <i class="fas fa-search mr-2"></i>Buscar
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Digite o CEP e clique em buscar para preencher automaticamente</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Endereço *</label>
                        <input type="text" name="endereco" id="endereco-offcanvas" required class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Número *</label>
                        <input type="text" name="numero" id="numero-offcanvas" required class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Complemento</label>
                        <input type="text" name="complemento" id="complemento-offcanvas" class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Bairro *</label>
                        <input type="text" name="bairro" id="bairro-offcanvas" required class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Cidade *</label>
                        <input type="text" name="cidade" id="cidade-offcanvas" required class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Estado *</label>
                        <select name="estado" id="estado-offcanvas" required class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
                            ${estadosOptions}
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Serviço -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-cog mr-2 text-purple-600"></i>
                    Serviço Solicitado
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Categoria *</label>
                        <select name="categoria_id" id="categoria_id-offcanvas" required class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
                            ${categoriasOptions}
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Subcategoria *</label>
                        <select name="subcategoria_id" id="subcategoria_id-offcanvas" required class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
                            <option value="">Selecione primeiro a categoria</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Local da Manutenção</label>
                        <input type="text" name="local_manutencao" class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all" placeholder="Ex: Sala, Cozinha, Quarto...">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Descrição do Problema *</label>
                        <textarea name="descricao_problema" required rows="4" class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all" placeholder="Descreva detalhadamente o problema..."></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Horários Preferenciais -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-clock mr-2 text-orange-600"></i>
                    Horários Preferenciais (Opcional)
                </h2>
                <p class="text-sm text-gray-600 mb-4">Selecione até 3 datas e horários preferenciais para o atendimento</p>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Data</label>
                        <input type="date" id="data_selecionada-offcanvas" class="block w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all" min="${new Date(Date.now() + 86400000).toISOString().split('T')[0]}" max="${new Date(Date.now() + 30*86400000).toISOString().split('T')[0]}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Horário</label>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <label class="relative">
                                <input type="radio" name="horario_selecionado" value="08:00-11:00" class="sr-only horario-radio-offcanvas">
                                <div class="border-2 border-gray-200 rounded-lg p-3 text-center cursor-pointer hover:border-green-300 transition-colors horario-card-offcanvas">
                                    <div class="text-sm font-medium text-gray-900">08h00 às 11h00</div>
                                </div>
                            </label>
                            <label class="relative">
                                <input type="radio" name="horario_selecionado" value="11:00-14:00" class="sr-only horario-radio-offcanvas">
                                <div class="border-2 border-gray-200 rounded-lg p-3 text-center cursor-pointer hover:border-green-300 transition-colors horario-card-offcanvas">
                                    <div class="text-sm font-medium text-gray-900">11h00 às 14h00</div>
                                </div>
                            </label>
                            <label class="relative">
                                <input type="radio" name="horario_selecionado" value="14:00-17:00" class="sr-only horario-radio-offcanvas">
                                <div class="border-2 border-gray-200 rounded-lg p-3 text-center cursor-pointer hover:border-green-300 transition-colors horario-card-offcanvas">
                                    <div class="text-sm font-medium text-gray-900">14h00 às 17h00</div>
                                </div>
                            </label>
                            <label class="relative">
                                <input type="radio" name="horario_selecionado" value="17:00-20:00" class="sr-only horario-radio-offcanvas">
                                <div class="border-2 border-gray-200 rounded-lg p-3 text-center cursor-pointer hover:border-green-300 transition-colors horario-card-offcanvas">
                                    <div class="text-sm font-medium text-gray-900">17h00 às 20h00</div>
                                </div>
                            </label>
                        </div>
                    </div>
                    <div id="horarios-selecionados-offcanvas" class="hidden">
                        <h4 class="text-sm font-medium text-gray-700 mb-3">
                            Horários Selecionados (<span id="contador-horarios-offcanvas">0</span>/3)
                        </h4>
                        <div id="lista-horarios-offcanvas" class="space-y-2"></div>
                    </div>
                    <input type="hidden" name="horarios_opcoes" id="horarios_opcoes-offcanvas" value="[]">
                </div>
            </div>
            
            <!-- Fotos -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-images mr-2 text-green-600"></i>
                    Fotos (Opcional)
                </h2>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Anexar Fotos</label>
                    <input type="file" name="fotos[]" multiple accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100 transition-all">
                    <p class="text-xs text-gray-500 mt-2">Você pode selecionar múltiplas fotos (JPG, PNG, GIF, WEBP)</p>
                </div>
                <div id="fotos-existentes-container" class="hidden mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fotos Existentes</label>
                    <div id="fotos-existentes-grid" class="grid grid-cols-2 md:grid-cols-4 gap-3"></div>
                    <input type="hidden" name="fotos_existentes" id="fotos_existentes-offcanvas" value="[]">
                </div>
            </div>
            
            <!-- Botões -->
            <div class="flex justify-end gap-3">
                <button type="button" onclick="fecharOffcanvasSolicitacaoManual()" class="px-6 py-3 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                    Cancelar
                </button>
                <button type="submit" class="px-6 py-3 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>
                    ${modoOffcanvasManual === 'criar' ? 'Salvar Solicitação' : 'Salvar Alterações'}
                </button>
            </div>
        </form>
    `;
    
    // Inicializar máscaras e funcionalidades
    inicializarFormularioOffcanvas(data);
}

function renderizarFormularioEditarManual(data) {
    const sol = data.solicitacao;
    
    // Formatar dados
    const cpfFormatado = formatarCPF(sol.cpf || '');
    const whatsappFormatado = formatarWhatsApp(sol.whatsapp || '');
    const cepFormatado = formatarCEP(sol.cep || '');
    
    // Formatar horários preferenciais
    let horariosExistentes = [];
    if (sol.horarios_preferenciais && Array.isArray(sol.horarios_preferenciais)) {
        sol.horarios_preferenciais.forEach(horario => {
            if (typeof horario === 'string') {
                // Converter formato "2025-10-29 08:00:00" para "29/10/2025 - 08:00-11:00"
                if (horario.match(/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}$/)) {
                    const [dataPart, horaPart] = horario.split(' ');
                    const [ano, mes, dia] = dataPart.split('-');
                    const [hora, min] = horaPart.split(':');
                    const horaInt = parseInt(hora);
                    let horaFim = 0;
                    if (horaInt == 8) horaFim = 11;
                    else if (horaInt == 11) horaFim = 14;
                    else if (horaInt == 14) horaFim = 17;
                    else if (horaInt == 17) horaFim = 20;
                    else horaFim = horaInt + 3;
                    horariosExistentes.push(`${dia}/${mes}/${ano} - ${hora}:${min}-${horaFim}:${min}`);
                } else {
                    horariosExistentes.push(horario);
                }
            }
        });
    }
    
    // Renderizar formulário (mesmo HTML do criar)
    renderizarFormularioCriarManual(data);
    
    // Preencher campos com dados existentes
    setTimeout(() => {
        const form = document.getElementById('formCriarEditarSolicitacaoManual');
        if (form) {
            form.querySelector('[name="imobiliaria_id"]').value = sol.imobiliaria_id || '';
            form.querySelector('[name="nome_completo"]').value = escapeHtml(sol.nome_completo || '');
            form.querySelector('[name="cpf"]').value = cpfFormatado;
            form.querySelector('[name="whatsapp"]').value = whatsappFormatado;
            form.querySelector('[name="numero_contrato"]').value = escapeHtml(sol.numero_contrato || '');
            form.querySelector('[name="status_id"]').value = sol.status_id || '';
            form.querySelector('[name="tipo_imovel"]').value = sol.tipo_imovel || '';
            form.querySelector('[name="subtipo_imovel"]').value = escapeHtml(sol.subtipo_imovel || '');
            form.querySelector('[name="cep"]').value = cepFormatado;
            form.querySelector('[name="endereco"]').value = escapeHtml(sol.endereco || '');
            form.querySelector('[name="numero"]').value = escapeHtml(sol.numero || '');
            form.querySelector('[name="complemento"]').value = escapeHtml(sol.complemento || '');
            form.querySelector('[name="bairro"]').value = escapeHtml(sol.bairro || '');
            form.querySelector('[name="cidade"]').value = escapeHtml(sol.cidade || '');
            form.querySelector('[name="estado"]').value = sol.estado || '';
            form.querySelector('[name="categoria_id"]').value = sol.categoria_id || '';
            form.querySelector('[name="local_manutencao"]').value = escapeHtml(sol.local_manutencao || '');
            form.querySelector('[name="descricao_problema"]').value = escapeHtml(sol.descricao_problema || '');
            
            // Atualizar subcategorias
            const categoriaSelect = form.querySelector('[name="categoria_id"]');
            if (categoriaSelect) {
                categoriaSelect.dispatchEvent(new Event('change'));
                setTimeout(() => {
                    form.querySelector('[name="subcategoria_id"]').value = sol.subcategoria_id || '';
                }, 100);
            }
            
            // Preencher horários existentes
            if (horariosExistentes.length > 0) {
                window.horariosEscolhidosOffcanvas = horariosExistentes;
                atualizarListaHorariosOffcanvas();
            }
            
            // Preencher fotos existentes
            if (sol.fotos && Array.isArray(sol.fotos) && sol.fotos.length > 0) {
                const container = document.getElementById('fotos-existentes-container');
                const grid = document.getElementById('fotos-existentes-grid');
                const input = document.getElementById('fotos_existentes-offcanvas');
                if (container && grid && input) {
                    container.classList.remove('hidden');
                    grid.innerHTML = '';
                    sol.fotos.forEach((foto, index) => {
                        const div = document.createElement('div');
                        div.className = 'relative group';
                        div.innerHTML = `
                            <img src="${foto}" alt="Foto ${index + 1}" class="w-full h-24 object-cover rounded-lg border border-gray-200">
                            <button type="button" onclick="removerFotoExistenteOffcanvas(${index})" class="absolute top-1 right-1 bg-red-600 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <i class="fas fa-times text-xs"></i>
                            </button>
                        `;
                        grid.appendChild(div);
                    });
                    input.value = JSON.stringify(sol.fotos);
                }
            }
        }
    }, 100);
}

function inicializarFormularioOffcanvas(data) {
    // Máscaras
    const cpfInput = document.getElementById('cpf-offcanvas');
    const whatsappInput = document.getElementById('whatsapp-offcanvas');
    const cepInput = document.getElementById('cep-offcanvas');
    
    if (cpfInput) {
        let cpfTimeoutOffcanvas;
        cpfInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 11) {
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                e.target.value = value;
            }
            
            // Buscar dados quando CPF estiver completo (11 dígitos)
            const cpfLimpo = e.target.value.replace(/\D/g, '');
            const imobiliariaId = document.getElementById('imobiliaria_id-offcanvas')?.value;
            const statusElement = document.getElementById('cpf-status-offcanvas');
            
            // Limpar timeout anterior
            if (cpfTimeoutOffcanvas) {
                clearTimeout(cpfTimeoutOffcanvas);
            }
            
            if (cpfLimpo.length === 11 && imobiliariaId) {
                if (statusElement) {
                    statusElement.textContent = 'Buscando dados...';
                    statusElement.className = 'text-xs text-blue-500 mt-1';
                }
                
                // Aguardar 500ms após parar de digitar
                cpfTimeoutOffcanvas = setTimeout(async () => {
                    try {
                        const response = await fetch('<?= url("admin/solicitacoes-manuais/buscar-cpf") ?>', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: new URLSearchParams({
                                cpf: cpfLimpo,
                                imobiliaria_id: imobiliariaId
                            })
                        });
                        
                        const result = await response.json();
                        
                        if (result.success && result.exists) {
                            // Preencher campos automaticamente
                            if (result.data.nome) {
                                const nomeInput = document.getElementById('nome_completo-offcanvas');
                                if (nomeInput) nomeInput.value = result.data.nome;
                            }
                            if (result.data.whatsapp) {
                                const whatsapp = result.data.whatsapp.replace(/\D/g, '');
                                let formatted = whatsapp;
                                if (whatsapp.length <= 10) {
                                    formatted = whatsapp.replace(/(\d{2})(\d)/, '($1) $2');
                                    formatted = formatted.replace(/(\d{4})(\d)/, '$1-$2');
                                } else {
                                    formatted = whatsapp.replace(/(\d{2})(\d)/, '($1) $2');
                                    formatted = formatted.replace(/(\d{5})(\d)/, '$1-$2');
                                }
                                const whatsappInput = document.getElementById('whatsapp-offcanvas');
                                if (whatsappInput) whatsappInput.value = formatted;
                            }
                            if (result.data.endereco_logradouro) {
                                const enderecoInput = document.getElementById('endereco-offcanvas');
                                if (enderecoInput) enderecoInput.value = result.data.endereco_logradouro;
                            }
                            if (result.data.endereco_numero) {
                                const numeroInput = document.getElementById('numero-offcanvas');
                                if (numeroInput) numeroInput.value = result.data.endereco_numero;
                            }
                            if (result.data.endereco_complemento) {
                                const complementoInput = document.getElementById('complemento-offcanvas');
                                if (complementoInput) complementoInput.value = result.data.endereco_complemento;
                            }
                            if (result.data.endereco_bairro) {
                                const bairroInput = document.getElementById('bairro-offcanvas');
                                if (bairroInput) bairroInput.value = result.data.endereco_bairro;
                            }
                            if (result.data.endereco_cidade) {
                                const cidadeInput = document.getElementById('cidade-offcanvas');
                                if (cidadeInput) cidadeInput.value = result.data.endereco_cidade;
                            }
                            if (result.data.endereco_estado) {
                                const estadoInput = document.getElementById('estado-offcanvas');
                                if (estadoInput) estadoInput.value = result.data.endereco_estado;
                            }
                            if (result.data.endereco_cep) {
                                const cep = result.data.endereco_cep.replace(/\D/g, '');
                                const cepInput = document.getElementById('cep-offcanvas');
                                if (cepInput) cepInput.value = cep.replace(/(\d{5})(\d)/, '$1-$2');
                            }
                            if (result.data.numero_contrato) {
                                const contratoInput = document.querySelector('#formCriarEditarSolicitacaoManual [name="numero_contrato"]');
                                if (contratoInput) contratoInput.value = result.data.numero_contrato;
                            }
                            
                            if (statusElement) {
                                statusElement.textContent = '✓ CPF encontrado - Dados preenchidos automaticamente';
                                statusElement.className = 'text-xs text-green-600 mt-1';
                            }
                        } else {
                            if (statusElement) {
                                statusElement.textContent = 'CPF não encontrado - Preencha os dados manualmente';
                                statusElement.className = 'text-xs text-gray-500 mt-1';
                            }
                        }
                    } catch (error) {
                        console.error('Erro ao buscar CPF:', error);
                        if (statusElement) {
                            statusElement.textContent = '';
                        }
                    }
                }, 500);
            } else {
                if (statusElement) {
                    statusElement.textContent = '';
                }
            }
        });
    }
    
    if (whatsappInput) {
        whatsappInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 11) {
                if (value.length <= 10) {
                    value = value.replace(/(\d{2})(\d)/, '($1) $2');
                    value = value.replace(/(\d{4})(\d)/, '$1-$2');
                } else {
                    value = value.replace(/(\d{2})(\d)/, '($1) $2');
                    value = value.replace(/(\d{5})(\d)/, '$1-$2');
                }
                e.target.value = value;
            }
        });
    }
    
    if (cepInput) {
        cepInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 8) {
                value = value.replace(/(\d{5})(\d)/, '$1-$2');
                e.target.value = value;
            }
        });
        
        // Buscar CEP
        const btnBuscarCep = document.getElementById('btn-buscar-cep-offcanvas');
        if (btnBuscarCep) {
            btnBuscarCep.addEventListener('click', async function() {
                const cep = cepInput.value.replace(/\D/g, '');
                if (cep.length !== 8) {
                    alert('CEP inválido. Digite um CEP com 8 dígitos.');
                    cepInput.focus();
                    return;
                }
                
                const btn = this;
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Buscando...';
                btn.disabled = true;
                
                try {
                    const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
                    const data = await response.json();
                    
                    if (data.erro) {
                        alert('CEP não encontrado. Verifique o CEP digitado.');
                        cepInput.focus();
                    } else {
                        document.getElementById('endereco-offcanvas').value = data.logradouro || '';
                        document.getElementById('bairro-offcanvas').value = data.bairro || '';
                        document.getElementById('cidade-offcanvas').value = data.localidade || '';
                        document.getElementById('estado-offcanvas').value = data.uf || '';
                        document.getElementById('numero-offcanvas').focus();
                    }
                } catch (error) {
                    console.error('Erro ao buscar CEP:', error);
                    alert('Erro ao buscar CEP. Tente novamente.');
                } finally {
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                }
            });
        }
        
        cepInput.addEventListener('blur', async function() {
            const cep = this.value.replace(/\D/g, '');
            if (cep.length === 8 && btnBuscarCep) {
                btnBuscarCep.click();
            }
        });
    }
    
    // Atualizar subcategorias quando categoria mudar
    const categoriaSelect = document.getElementById('categoria_id-offcanvas');
    const subcategoriaSelect = document.getElementById('subcategoria_id-offcanvas');
    
    if (categoriaSelect && subcategoriaSelect) {
        categoriaSelect.addEventListener('change', function() {
            const categoriaId = this.value;
            subcategoriaSelect.innerHTML = '<option value="">Selecione...</option>';
            
            if (categoriaId && data.categorias) {
                const categoria = data.categorias.find(c => c.id == categoriaId);
                if (categoria && categoria.subcategorias) {
                    categoria.subcategorias.forEach(sub => {
                        const option = document.createElement('option');
                        option.value = sub.id;
                        option.textContent = sub.nome;
                        subcategoriaSelect.appendChild(option);
                    });
                }
            }
        });
    }
    
    // Sistema de horários
    inicializarSistemaHorariosOffcanvas();
}

let horariosEscolhidosOffcanvas = [];
let dataSelecionadaAtualOffcanvas = '';

function inicializarSistemaHorariosOffcanvas() {
    const dataInput = document.getElementById('data_selecionada-offcanvas');
    const horarioRadios = document.querySelectorAll('.horario-radio-offcanvas');
    const horarioCards = document.querySelectorAll('.horario-card-offcanvas');
    
    if (dataInput) {
        dataInput.addEventListener('change', function() {
            if (!this.value) return;
            const dataSelecionada = new Date(this.value + 'T12:00:00');
            const diaDaSemana = dataSelecionada.getDay();
            dataSelecionadaAtualOffcanvas = this.value;
            
            if (diaDaSemana === 0 || diaDaSemana === 6) {
                alert('⚠️ Atendimentos não são realizados aos fins de semana.');
                this.value = '';
                dataSelecionadaAtualOffcanvas = '';
                return;
            }
            
            horarioRadios.forEach(r => r.checked = false);
        });
    }
    
    horarioRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            const data = dataSelecionadaAtualOffcanvas;
            const horario = this.value;
            
            if (!data) {
                alert('Por favor, selecione uma data primeiro');
                this.checked = false;
                return;
            }
            
            if (data && horario) {
                const horarioCompleto = `${formatarData(data)} - ${horario}`;
                
                if (!horariosEscolhidosOffcanvas.includes(horarioCompleto) && horariosEscolhidosOffcanvas.length < 3) {
                    horariosEscolhidosOffcanvas.push(horarioCompleto);
                    atualizarListaHorariosOffcanvas();
                    this.checked = false;
                } else if (horariosEscolhidosOffcanvas.length >= 3) {
                    alert('Você pode selecionar no máximo 3 horários.');
                    this.checked = false;
                } else {
                    this.checked = false;
                }
            }
        });
    });
    
    horarioCards.forEach(card => {
        card.addEventListener('click', function(e) {
            if (e.target.type === 'radio') return;
            const label = this.closest('label');
            const radio = label?.querySelector('.horario-radio-offcanvas');
            if (radio && !radio.checked) {
                radio.checked = true;
                radio.dispatchEvent(new Event('change'));
            }
        });
    });
}

function atualizarListaHorariosOffcanvas() {
    const container = document.getElementById('horarios-selecionados-offcanvas');
    const lista = document.getElementById('lista-horarios-offcanvas');
    const contador = document.getElementById('contador-horarios-offcanvas');
    
    if (horariosEscolhidosOffcanvas.length > 0) {
        container.classList.remove('hidden');
        contador.textContent = horariosEscolhidosOffcanvas.length;
        
        lista.innerHTML = '';
        horariosEscolhidosOffcanvas.forEach((horario, index) => {
            const div = document.createElement('div');
            div.className = 'flex items-center justify-between bg-green-50 border border-green-200 rounded-lg p-3';
            div.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-clock text-green-600 mr-2"></i>
                    <span class="text-sm text-green-800">${horario}</span>
                </div>
                <button type="button" onclick="removerHorarioOffcanvas(${index})" class="text-red-500 hover:text-red-700">
                    <i class="fas fa-times"></i>
                </button>
            `;
            lista.appendChild(div);
        });
    } else {
        container.classList.add('hidden');
    }
}

window.removerHorarioOffcanvas = function(index) {
    horariosEscolhidosOffcanvas.splice(index, 1);
    atualizarListaHorariosOffcanvas();
};

window.removerFotoExistenteOffcanvas = function(index) {
    if (confirm('Deseja realmente remover esta foto?')) {
        const input = document.getElementById('fotos_existentes-offcanvas');
        if (input) {
            const fotos = JSON.parse(input.value || '[]');
            fotos.splice(index, 1);
            input.value = JSON.stringify(fotos);
            location.reload(); // Recarregar para atualizar a lista
        }
    }
};

function formatarData(data) {
    const [ano, mes, dia] = data.split('-');
    return `${dia}/${mes}/${ano}`;
}

async function salvarSolicitacaoManual(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    // Converter horários para formato esperado
    const horariosFormatados = horariosEscolhidosOffcanvas.map(horario => {
        const [dataStr, faixaHorario] = horario.split(' - ');
        const [dia, mes, ano] = dataStr.split('/');
        const horarioInicial = faixaHorario.split('-')[0];
        return `${ano}-${mes}-${dia} ${horarioInicial}:00`;
    });
    
    formData.append('horarios_opcoes', JSON.stringify(horariosFormatados));
    
    // Adicionar header para identificar como AJAX
    formData.append('X-Requested-With', 'XMLHttpRequest');
    
    const url = modoOffcanvasManual === 'criar' 
        ? '<?= url("admin/solicitacoes-manuais/nova") ?>'
        : `<?= url("admin/solicitacoes-manuais") ?>/${solicitacaoManualEditandoId}/editar`;
    
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Salvando...';
    
    try {
        const response = await fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(data.message || 'Solicitação salva com sucesso!');
            fecharOffcanvasSolicitacaoManual();
            location.reload();
        } else {
            const errorMsg = data.error || (data.errors ? data.errors.join('. ') : 'Erro ao salvar solicitação');
            alert('Erro: ' + errorMsg);
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao salvar solicitação: ' + error.message);
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
}

// ============================================================
// AUTO-REFRESH: Atualização automática da lista e estatísticas
// ============================================================

let ultimaContagemManuais = <?= $stats['nao_migradas'] ?? 0 ?>;
let autoRefreshAtivo = true;

// Função para atualizar a lista de solicitações via AJAX
async function atualizarListaSolicitacoes() {
    if (!autoRefreshAtivo) return;
    
    try {
        // Pegar filtros atuais da URL
        const urlParams = new URLSearchParams(window.location.search);
        const params = new URLSearchParams();
        
        if (urlParams.get('busca')) params.set('busca', urlParams.get('busca'));
        if (urlParams.get('imobiliaria_id')) params.set('imobiliaria_id', urlParams.get('imobiliaria_id'));
        if (urlParams.get('status_id')) params.set('status_id', urlParams.get('status_id'));
        if (urlParams.get('migrada')) params.set('migrada', urlParams.get('migrada'));
        
        const response = await fetch(`<?= url('admin/solicitacoes-manuais/lista/api') ?>?${params.toString()}`);
        const data = await response.json();
        
        if (data.success) {
            // Atualizar estatísticas
            const statTotal = document.getElementById('stat-total');
            const statNaoMigradas = document.getElementById('stat-nao-migradas');
            const statMigradas = document.getElementById('stat-migradas');
            
            if (statTotal) statTotal.textContent = data.stats.total;
            if (statNaoMigradas) statNaoMigradas.textContent = data.stats.nao_migradas;
            if (statMigradas) statMigradas.textContent = data.stats.migradas;
            
            // Verificar se houve mudança na contagem
            if (data.contagem !== ultimaContagemManuais) {
                ultimaContagemManuais = data.contagem;
                
                // Recarregar a página para atualizar a lista completa com todas as funcionalidades
                // (evita duplicar toda a lógica de renderização em JS)
                location.reload();
            }
        }
    } catch (error) {
        console.error('Erro ao atualizar lista de solicitações:', error);
    }
}

// Escutar evento de atualização do badge (disparado pelo layout admin)
window.addEventListener('solicitacoesManuaisAtualizado', function(e) {
    if (e.detail && e.detail.success) {
        const novaContagem = e.detail.contagem || 0;
        
        // Atualizar estatísticas se tiver dados
        const statNaoMigradas = document.getElementById('stat-nao-migradas');
        if (statNaoMigradas && novaContagem !== parseInt(statNaoMigradas.textContent)) {
            // Houve mudança, recarregar para mostrar novas solicitações
            console.log('Nova solicitação manual detectada! Recarregando...');
            location.reload();
        }
    }
});

// Atualizar a cada 15 segundos (mais suave que o badge de 10s)
setInterval(atualizarListaSolicitacoes, 15000);

// Log para debug
console.log('Auto-refresh de solicitações manuais ativado');
</script>

<?php
$content = ob_get_clean();
include 'app/Views/layouts/admin.php';
?>

