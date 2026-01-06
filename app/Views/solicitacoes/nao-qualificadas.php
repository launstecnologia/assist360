<?php
/**
 * View: Solicitações Não Qualificadas (Admin)
 * Listagem de todas as solicitações não qualificadas (normais + manuais)
 */
$title = 'Solicitações Não Qualificadas - Portal do Operador';
$currentPage = 'solicitacoes-nao-qualificadas';
ob_start();
?>

<!-- Token CSRF (oculto) -->
<?= \App\Core\View::csrfField() ?>

<!-- Header -->
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-exclamation-triangle mr-2 text-red-600"></i>
                Solicitações Não Qualificadas
            </h1>
            <p class="text-gray-600 mt-1">
                Solicitações que excederam o limite de categoria ou não possuem validação de bolsão
            </p>
        </div>
        <div class="flex gap-3">
            <a href="<?= url('admin/solicitacoes-manuais') ?>" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                <i class="fas fa-file-alt mr-2"></i>
                Solicitações Manuais
            </a>
            <a href="<?= url('admin/solicitacoes') ?>" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                <i class="fas fa-clipboard-list mr-2"></i>
                Solicitações Normais
            </a>
            <a href="<?= url('admin/kanban') ?>" 
               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-columns mr-2"></i>
                Ver Kanban
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
                <span class="text-gray-500">Não Qualificados</span>
            </div>
        </li>
    </ol>
</nav>

<!-- Filtros -->
<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <form method="GET" action="<?= url('admin/solicitacoes-nao-qualificadas') ?>" class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <!-- Busca -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
            <input type="text" name="busca" 
                   class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500"
                   placeholder="Nome, CPF..."
                   value="<?= htmlspecialchars($filtros['busca'] ?? '') ?>">
        </div>
        
        <!-- Imobiliária -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Imobiliária</label>
            <select name="imobiliaria_id" 
                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500">
                <option value="">Todas</option>
                <?php foreach ($imobiliarias as $imob): ?>
                    <option value="<?= $imob['id'] ?>" <?= ($filtros['imobiliaria_id'] ?? '') == $imob['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($imob['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- Tipo -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Tipo</label>
            <select name="tipo" 
                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500">
                <option value="">Todos</option>
                <option value="NORMAL" <?= ($filtros['tipo'] ?? '') === 'NORMAL' ? 'selected' : '' ?>>Normais</option>
                <option value="MANUAL" <?= ($filtros['tipo'] ?? '') === 'MANUAL' ? 'selected' : '' ?>>Manuais</option>
            </select>
        </div>
        
        <!-- Data Início -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Data Início</label>
            <input type="date" name="data_inicio" 
                   class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500"
                   value="<?= htmlspecialchars($filtros['data_inicio'] ?? '') ?>">
        </div>
        
        <!-- Data Fim -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Data Fim</label>
            <input type="date" name="data_fim" 
                   class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500"
                   value="<?= htmlspecialchars($filtros['data_fim'] ?? '') ?>">
        </div>
        
        <!-- Botões -->
        <div class="md:col-span-5 flex justify-end gap-2">
            <a href="<?= url('admin/solicitacoes-nao-qualificadas') ?>" 
               class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                Limpar
            </a>
            <button type="submit" 
                    class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                <i class="fas fa-search mr-2"></i>
                Filtrar
            </button>
        </div>
    </form>
</div>

<!-- Lista de Solicitações -->
<div class="bg-white rounded-lg shadow-sm">
    <?php if (!empty($solicitacoes)): ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Tipo
                        </th>
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
                            Status
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Data
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Qualificação
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Ações
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($solicitacoes as $solicitacao): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <?php if (($solicitacao['tipo_solicitacao'] ?? '') === 'MANUAL'): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        <i class="fas fa-file-alt mr-1"></i> Manual
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <i class="fas fa-clipboard-list mr-1"></i> Normal
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php if (!empty($solicitacao['imobiliaria_logo'])): ?>
                                    <img src="<?= url('Public/uploads/logos/' . $solicitacao['imobiliaria_logo']) ?>" 
                                         alt="<?= htmlspecialchars($solicitacao['imobiliaria_nome'] ?? 'Imobiliária') ?>" 
                                         class="h-8 w-auto object-contain max-w-[100px]"
                                         onerror="this.style.display='none';">
                                <?php else: ?>
                                    <span class="text-sm text-gray-900"><?= htmlspecialchars($solicitacao['imobiliaria_nome'] ?? '-') ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($solicitacao['nome_completo'] ?? $solicitacao['locatario_nome'] ?? 'N/A') ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <?php if (($solicitacao['tipo_solicitacao'] ?? '') === 'MANUAL'): ?>
                                        CPF: <?= htmlspecialchars($solicitacao['cpf'] ?? 'N/A') ?><br>
                                        <?= htmlspecialchars($solicitacao['whatsapp'] ?? '') ?>
                                    <?php else: ?>
                                        <?= htmlspecialchars($solicitacao['locatario_telefone'] ?? '') ?>
                                    <?php endif; ?>
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
                                    <?= htmlspecialchars($solicitacao['categoria_nome'] ?? 'N/A') ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" 
                                      style="background-color: <?= htmlspecialchars($solicitacao['status_cor'] ?? '#6B7280') ?>20; color: <?= htmlspecialchars($solicitacao['status_cor'] ?? '#6B7280') ?>;">
                                    <?= htmlspecialchars($solicitacao['status_nome'] ?? 'N/A') ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <?= date('d/m/Y H:i', strtotime($solicitacao['created_at'])) ?>
                            </td>
                            <td class="px-6 py-4">
                                <select onchange="atualizarQualificacao('<?= ($solicitacao['tipo_solicitacao'] ?? 'NORMAL') === 'MANUAL' ? 'manual' : 'normal' ?>', <?= $solicitacao['id'] ?>, this.value)" 
                                        class="text-xs border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500">
                                    <option value="">-- Selecione --</option>
                                    <option value="BOLSAO" <?= ($solicitacao['tipo_qualificacao'] ?? '') === 'BOLSAO' ? 'selected' : '' ?>>Bolsão</option>
                                    <option value="CORTESIA" <?= ($solicitacao['tipo_qualificacao'] ?? '') === 'CORTESIA' ? 'selected' : '' ?>>Cortesia</option>
                                    <option value="NAO_QUALIFICADA" <?= ($solicitacao['tipo_qualificacao'] ?? 'NAO_QUALIFICADA') === 'NAO_QUALIFICADA' ? 'selected' : '' ?>>Recusado</option>
                                </select>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <?php if (($solicitacao['tipo_solicitacao'] ?? '') === 'MANUAL'): ?>
                                    <button type="button" onclick="verDetalhesManual(<?= $solicitacao['id'] ?>)" 
                                            class="text-blue-600 hover:text-blue-900">
                                        <i class="fas fa-eye mr-1"></i> Ver
                                    </button>
                                <?php else: ?>
                                    <button type="button" onclick="verDetalhesNormal(<?= $solicitacao['id'] ?>)" 
                                            class="text-blue-600 hover:text-blue-900">
                                        <i class="fas fa-eye mr-1"></i> Ver
                                    </button>
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
            <h3 class="text-lg font-medium text-gray-900 mb-2">Nenhuma solicitação não qualificada encontrada</h3>
            <p class="text-gray-500">Não há solicitações não qualificadas no momento.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Modal de Detalhes -->
<div id="modal-detalhes" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
            <h3 class="text-xl font-semibold text-gray-900">
                <i class="fas fa-file-alt mr-2"></i>
                Detalhes da Solicitação
            </h3>
            <button onclick="fecharModalDetalhes()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <div id="modal-content" class="px-6 py-4">
            <!-- Conteúdo será preenchido via JavaScript -->
            <div class="flex items-center justify-center py-12">
                <i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i>
            </div>
        </div>
    </div>
</div>

<script>
// Fechar modal
function fecharModalDetalhes() {
    document.getElementById('modal-detalhes').classList.add('hidden');
}

// Ver detalhes de solicitação manual
async function verDetalhesManual(id) {
    const modal = document.getElementById('modal-detalhes');
    const content = document.getElementById('modal-content');
    
    if (!modal || !content) {
        alert('Erro: Modal não encontrado. Recarregue a página.');
        return;
    }
    
    modal.classList.remove('hidden');
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
        const tipoQualificacao = s.tipo_qualificacao || 'NAO_QUALIFICADA';
        const validacaoBolsao = s.validacao_bolsao || 0;
        
        content.innerHTML = `
            <div class="space-y-6">
                <!-- Dados Pessoais -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-gray-900 mb-3 flex items-center">
                        <i class="fas fa-user mr-2 text-blue-600"></i>
                        Dados Pessoais
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div>
                            <span class="font-medium text-gray-600">Nome:</span>
                            <p class="text-gray-900">${s.nome_completo || 'N/A'}</p>
                        </div>
                        <div>
                            <span class="font-medium text-gray-600">CPF:</span>
                            <p class="text-gray-900">${s.cpf || 'N/A'}</p>
                        </div>
                        <div>
                            <span class="font-medium text-gray-600">WhatsApp:</span>
                            <p class="text-gray-900">
                                ${s.whatsapp ? `<a href="https://wa.me/55${s.whatsapp.replace(/\D/g, '')}" target="_blank" class="text-green-600 hover:text-green-700">
                                    <i class="fab fa-whatsapp mr-1"></i>${s.whatsapp}
                                </a>` : 'N/A'}
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Endereço -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-gray-900 mb-3 flex items-center">
                        <i class="fas fa-map-marker-alt mr-2 text-red-600"></i>
                        Endereço do Imóvel
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="font-medium text-gray-600">Tipo:</span>
                            <p class="text-gray-900">${s.tipo_imovel || 'N/A'}${s.subtipo_imovel ? ' - ' + s.subtipo_imovel : ''}</p>
                        </div>
                        <div>
                            <span class="font-medium text-gray-600">CEP:</span>
                            <p class="text-gray-900">${s.cep || 'N/A'}</p>
                        </div>
                        <div class="md:col-span-2">
                            <span class="font-medium text-gray-600">Endereço Completo:</span>
                            <p class="text-gray-900">
                                ${s.endereco || ''}, ${s.numero || ''}${s.complemento ? ' - ' + s.complemento : ''}<br>
                                ${s.bairro || ''}, ${s.cidade || ''} - ${s.estado || ''}
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Serviço -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-gray-900 mb-3 flex items-center">
                        <i class="fas fa-cog mr-2 text-purple-600"></i>
                        Serviço Solicitado
                    </h4>
                    <div class="space-y-3 text-sm">
                        <div>
                            <span class="font-medium text-gray-600">Categoria:</span>
                            <p class="text-gray-900">${s.categoria_nome || 'N/A'}</p>
                        </div>
                        <div>
                            <span class="font-medium text-gray-600">Subcategoria:</span>
                            <p class="text-gray-900">${s.subcategoria_nome || 'N/A'}</p>
                        </div>
                        <div>
                            <span class="font-medium text-gray-600">Descrição do Problema:</span>
                            <p class="text-gray-900 whitespace-pre-wrap">${s.descricao_problema || 'N/A'}</p>
                        </div>
                    </div>
                </div>
                
                <!-- Atribuição final -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-gray-900 mb-3 flex items-center">
                        <i class="fas fa-tag mr-2 text-indigo-600"></i>
                        Atribuição final
                    </h4>
                    <div class="flex items-center gap-3">
                        ${tipoQualificacao === 'BOLSAO' ? `
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                <i class="fas fa-check-circle mr-2"></i>Bolsão
                            </span>
                        ` : tipoQualificacao === 'NAO_QUALIFICADA' ? `
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                <i class="fas fa-exclamation-triangle mr-2"></i>Recusado
                            </span>
                        ` : tipoQualificacao === 'CORTESIA' ? `
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-2"></i>Cortesia
                            </span>
                        ` : tipoQualificacao === 'REGRA_2' ? `
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800">
                                <i class="fas fa-calendar-check mr-2"></i>Dt Assinatura
                            </span>
                        ` : `
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                                <i class="fas fa-question-circle mr-2"></i>Não definido
                            </span>
                        `}
                        <select onchange="atualizarQualificacaoManualModal('manual', ${s.id}, this.value)" 
                                class="text-sm border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">-- Selecione --</option>
                            <option value="BOLSAO" ${tipoQualificacao === 'BOLSAO' ? 'selected' : ''}>Bolsão</option>
                            <option value="CORTESIA" ${tipoQualificacao === 'CORTESIA' ? 'selected' : ''}>Cortesia</option>
                            <option value="NAO_QUALIFICADA" ${tipoQualificacao === 'NAO_QUALIFICADA' ? 'selected' : ''}>Recusado</option>
                        </select>
                    </div>
                </div>
                
                <!-- Horários -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-gray-900 mb-3 flex items-center">
                        <i class="fas fa-clock mr-2 text-orange-600"></i>
                        Horários Preferenciais
                    </h4>
                    ${horarios.length > 0 ? `
                        <ul class="text-sm text-gray-900 space-y-1">
                            ${horarios.map(h => `<li><i class="fas fa-calendar-alt mr-2 text-gray-400"></i>${h}</li>`).join('')}
                        </ul>
                    ` : '<p class="text-sm text-gray-500">Nenhum horário informado</p>'}
                </div>
                
                <!-- Status -->
                <div class="border-t border-gray-200 pt-4">
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-medium text-gray-700">Status:</span>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium" 
                              style="background-color: ${s.status_cor}20; color: ${s.status_cor};">
                            ${s.status_nome || 'N/A'}
                        </span>
                    </div>
                    ${s.migrada_para_solicitacao_id ? `
                        <div class="mt-4 bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h5 class="text-sm font-semibold text-green-900 mb-1">
                                        <i class="fas fa-check-circle mr-1"></i>Solicitação Migrada
                                    </h5>
                                    <p class="text-sm text-green-700">
                                        Migrada em ${new Date(s.migrada_em).toLocaleString('pt-BR')} por ${s.migrada_por_nome || 'Desconhecido'}
                                    </p>
                                </div>
                                <a href="<?= url('admin/solicitacoes/show') ?>/${s.migrada_para_solicitacao_id}" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                                    <i class="fas fa-external-link-alt mr-2"></i>Ver Solicitação
                                </a>
                            </div>
                        </div>
                    ` : ''}
                </div>
                
                <!-- Informações Técnicas -->
                <div class="text-xs text-gray-500 border-t border-gray-200 pt-4">
                    <p><strong>ID:</strong> ${s.id}</p>
                    <p><strong>Criada em:</strong> ${new Date(s.created_at).toLocaleString('pt-BR')}</p>
                    <p><strong>Imobiliária:</strong> ${s.imobiliaria_nome || 'N/A'}</p>
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
                <button onclick="fecharModalDetalhes()" class="mt-4 px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                    Fechar
                </button>
            </div>
        `;
    }
}

// Ver detalhes de solicitação normal
async function verDetalhesNormal(id) {
    const modal = document.getElementById('modal-detalhes');
    const content = document.getElementById('modal-content');
    
    if (!modal || !content) {
        alert('Erro: Modal não encontrado. Recarregue a página.');
        return;
    }
    
    modal.classList.remove('hidden');
    content.innerHTML = '<div class="flex items-center justify-center py-12"><i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i></div>';
    
    try {
        const response = await fetch(`<?= url('admin/solicitacoes') ?>/${id}/api`, {
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
        const tipoQualificacao = s.tipo_qualificacao || 'NAO_QUALIFICADA';
        
        content.innerHTML = `
            <div class="space-y-6">
                <!-- Atribuição final -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-gray-900 mb-3 flex items-center">
                        <i class="fas fa-tag mr-2 text-indigo-600"></i>
                        Atribuição final
                    </h4>
                    <div class="flex items-center gap-3">
                        ${tipoQualificacao === 'NAO_QUALIFICADA' ? `
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                <i class="fas fa-exclamation-triangle mr-2"></i>Recusado
                            </span>
                        ` : `
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-2"></i>Cortesia
                            </span>
                        `}
                        <select onchange="atualizarQualificacaoManualModal('normal', ${s.id}, this.value)" 
                                class="text-sm border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="NAO_QUALIFICADA" ${tipoQualificacao === 'NAO_QUALIFICADA' ? 'selected' : ''}>Recusado</option>
                            <option value="CORTESIA" ${tipoQualificacao === 'CORTESIA' ? 'selected' : ''}>Cortesia</option>
                        </select>
                    </div>
                </div>
                
                <div class="text-center py-4">
                    <a href="<?= url('admin/solicitacoes') ?>/${s.id}" 
                       class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-external-link-alt mr-2"></i>
                        Ver Detalhes Completos
                    </a>
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
                <button onclick="fecharModalDetalhes()" class="mt-4 px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                    Fechar
                </button>
            </div>
        `;
    }
}

// Atualizar qualificação do modal
async function atualizarQualificacaoManualModal(tipo, id, tipoQualificacao) {
    const csrfToken = document.querySelector('input[name="_token"]')?.value;
    
    if (!confirm('Deseja realmente alterar a atribuição final desta solicitação?')) {
        if (tipo === 'manual') {
            verDetalhesManual(id);
        } else {
            verDetalhesNormal(id);
        }
        return;
    }
    
    try {
        const response = await fetch(`<?= url("admin/solicitacoes") ?>/` + tipo + '/' + id + '/atualizar-qualificacao', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams({
                _token: csrfToken || '',
                tipo_qualificacao: tipoQualificacao
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Atribuição final atualizada com sucesso!');
            if (tipo === 'manual') {
                verDetalhesManual(id);
            } else {
                verDetalhesNormal(id);
            }
        } else {
            alert('Erro ao atualizar: ' + (data.error || 'Erro desconhecido'));
            location.reload();
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao atualizar atribuição final');
        location.reload();
    }
}

// Fechar modal ao clicar fora
document.getElementById('modal-detalhes')?.addEventListener('click', function(e) {
    if (e.target === this) {
        fecharModalDetalhes();
    }
});

function atualizarQualificacao(tipo, id, tipoQualificacao) {
    const csrfToken = document.querySelector('input[name="_token"]')?.value;
    
    if (!confirm('Deseja realmente alterar a atribuição final desta solicitação?')) {
        // Recarregar a página para restaurar o valor anterior
        location.reload();
        return;
    }
    
    fetch('<?= url("admin/solicitacoes") ?>/' + tipo + '/' + id + '/atualizar-qualificacao', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams({
            _token: csrfToken,
            tipo: tipo,
            id: id,
            tipo_qualificacao: tipoQualificacao
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Atribuição final atualizada com sucesso!');
            // Se mudou para CORTESIA, pode remover da lista
            if (tipoQualificacao === 'CORTESIA') {
                location.reload();
            }
        } else {
            alert('Erro ao atualizar: ' + (data.error || 'Erro desconhecido'));
            location.reload();
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao atualizar atribuição final');
        location.reload();
    });
}
</script>

<?php
$content = ob_get_clean();
include 'app/Views/layouts/admin.php';
?>

