<?php
$title = 'Dashboard';
$currentPage = 'dashboard';
$pageTitle = 'Dashboard';
ob_start();
?>

<!-- Estatísticas Gerais -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white p-6 rounded-lg shadow-sm">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-clipboard-list text-blue-600"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Total Solicitações</p>
                <p class="text-2xl font-semibold text-gray-900"><?= $estatisticas['total'] ?? 0 ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white p-6 rounded-lg shadow-sm">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-600"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Concluídas</p>
                <p class="text-2xl font-semibold text-gray-900"><?= $estatisticas['concluidas'] ?? 0 ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white p-6 rounded-lg shadow-sm">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-clock text-yellow-600"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Tempo Médio</p>
                <p class="text-2xl font-semibold text-gray-900"><?= round($estatisticas['tempo_medio_resolucao'] ?? 0) ?>h</p>
            </div>
        </div>
    </div>
    
    <div class="bg-white p-6 rounded-lg shadow-sm">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-star text-purple-600"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Satisfação</p>
                <p class="text-2xl font-semibold text-gray-900"><?= round($estatisticas['satisfacao_media'] ?? 0, 1) ?>/5</p>
            </div>
        </div>
    </div>
</div>

<!-- Card de Solicitações Manuais Pendentes -->
<?php
try {
    $solicitacaoManualModel = new \App\Models\SolicitacaoManual();
    $naoMigradas = count($solicitacaoManualModel->getNaoMigradas(999));
    
    if ($naoMigradas > 0):
?>
<div class="bg-gradient-to-r from-yellow-50 to-orange-50 border-l-4 border-yellow-500 rounded-lg shadow-sm p-6 mb-8">
    <div class="flex items-center justify-between">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-file-alt text-yellow-600 text-xl"></i>
                </div>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-exclamation-triangle text-yellow-500 mr-2"></i>
                    Solicitações Manuais Aguardando Triagem
                </h3>
                <p class="text-sm text-gray-600 mt-1">
                    Você tem <strong class="text-yellow-700"><?= $naoMigradas ?> solicitação<?= $naoMigradas > 1 ? 'ões' : '' ?></strong> 
                    criada<?= $naoMigradas > 1 ? 's' : '' ?> por usuários não logados aguardando revisão e migração para o sistema.
                </p>
            </div>
        </div>
        <div class="flex-shrink-0">
            <a href="<?= url('admin/solicitacoes-manuais') ?>" 
               class="inline-flex items-center px-5 py-3 bg-yellow-500 text-white font-medium rounded-lg hover:bg-yellow-600 transition-colors shadow-sm">
                <i class="fas fa-eye mr-2"></i>
                Revisar Agora
                <span class="ml-2 bg-white text-yellow-700 px-2 py-1 rounded-full text-xs font-bold">
                    <?= $naoMigradas ?>
                </span>
            </a>
        </div>
    </div>
</div>
<?php 
    endif;
} catch (\Exception $e) {
    // Silencioso se der erro
}
?>

<!-- Filtros de Período -->
<div class="bg-white p-6 rounded-lg shadow-sm mb-8">
    <div class="flex items-center justify-between">
        <h3 class="text-lg font-medium text-gray-900">Período de Análise</h3>
        <div class="flex space-x-2">
            <button onclick="updatePeriod('7')" class="px-3 py-1 text-sm rounded-md <?= $periodo === '7' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">7 dias</button>
            <button onclick="updatePeriod('30')" class="px-3 py-1 text-sm rounded-md <?= $periodo === '30' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">30 dias</button>
            <button onclick="updatePeriod('90')" class="px-3 py-1 text-sm rounded-md <?= $periodo === '90' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">90 dias</button>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    <!-- Gráfico de Solicitações por Status -->
    <div class="bg-white p-6 rounded-lg shadow-sm">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Solicitações por Status</h3>
        <div style="height: 300px; position: relative;">
            <canvas id="statusChart"></canvas>
        </div>
    </div>
    
    <!-- Gráfico de Solicitações por Imobiliária -->
    <div class="bg-white p-6 rounded-lg shadow-sm">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Solicitações por Imobiliária</h3>
        <div style="height: 300px; position: relative;">
            <canvas id="imobiliariaChart"></canvas>
        </div>
    </div>
</div>

<!-- Kanban de Solicitações -->
<?php /*
<div class="bg-white rounded-lg shadow-sm">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Solicitações Recentes</h3>
    </div>
    
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php 
            // Agrupar solicitações por status
            $statusUnicos = [];
            $solicitacoesPorStatus = [];
            
            foreach ($kanbanData as $solicitacao) {
                $statusId = $solicitacao['status_id'];
                
                // Adicionar status único
                if (!isset($statusUnicos[$statusId])) {
                    $statusUnicos[$statusId] = [
                        'id' => $statusId,
                        'nome' => $solicitacao['status_nome'],
                        'cor' => $solicitacao['status_cor']
                    ];
                    $solicitacoesPorStatus[$statusId] = [];
                }
                
                // Adicionar solicitação ao status
                $solicitacoesPorStatus[$statusId][] = $solicitacao;
            }
            
            // Exibir cada status único
            foreach ($statusUnicos as $statusId => $status): 
            ?>
            <div class="kanban-column">
                <div class="flex items-center mb-4">
                    <div class="w-3 h-3 rounded-full mr-2" style="background-color: <?= $status['cor'] ?>"></div>
                    <h4 class="font-medium text-gray-900"><?= htmlspecialchars($status['nome']) ?></h4>
                    <span class="ml-auto bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded-full">
                        <?= count($solicitacoesPorStatus[$statusId]) ?>
                    </span>
                </div>
                
                <div class="space-y-3">
                    <?php 
                    // Mostrar até 5 solicitações por status
                    foreach (array_slice($solicitacoesPorStatus[$statusId], 0, 5) as $solicitacao): 
                    ?>
                    <div class="drag-item bg-gray-50 p-4 rounded-lg border border-gray-200 cursor-pointer hover:shadow-md transition-shadow">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h5 class="text-sm font-bold text-gray-900">#<?= $solicitacao['numero_solicitacao'] ?? ('KSS' . $solicitacao['id']) ?></h5>
                                <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars($solicitacao['categoria_nome']) ?></p>
                                <p class="text-xs text-gray-400 mt-1"><?= date('d/m/Y', strtotime($solicitacao['created_at'])) ?></p>
                            </div>
                            <button onclick="abrirDetalhes(<?= $solicitacao['id'] ?>)" class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-eye text-xs"></i>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
*/ ?>

<!-- Solicitações Pendentes -->
<?php if (!empty($solicitacoesPendentes)): ?>
<div class="mt-8 bg-yellow-50 border border-yellow-200 rounded-lg p-6">
    <div class="flex items-center">
        <i class="fas fa-exclamation-triangle text-yellow-600 mr-3"></i>
        <h3 class="text-lg font-medium text-yellow-800">Solicitações Pendentes</h3>
    </div>
    <p class="mt-2 text-yellow-700">
        <?= count($solicitacoesPendentes) ?> solicitações estão aguardando há mais de 10 dias e precisam de atenção.
    </p>
    <div class="mt-4">
        <a href="<?= url('solicitacoes?status=pendente') ?>" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-yellow-800 bg-yellow-100 hover:bg-yellow-200">
            Ver Solicitações Pendentes
        </a>
    </div>
</div>
<?php endif; ?>

<!-- Dados Administrativos (apenas para admins) -->
<?php if ($user['nivel_permissao'] === 'ADMINISTRADOR'): ?>
<div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="bg-white p-6 rounded-lg shadow-sm">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-building text-blue-600"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Imobiliárias Ativas</p>
                <p class="text-2xl font-semibold text-gray-900"><?= $total_imobiliarias ?? 0 ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white p-6 rounded-lg shadow-sm">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-users text-green-600"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Usuários Ativos</p>
                <p class="text-2xl font-semibold text-gray-900"><?= $total_usuarios ?? 0 ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white p-6 rounded-lg shadow-sm">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-tags text-purple-600"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Categorias Ativas</p>
                <p class="text-2xl font-semibold text-gray-900"><?= $total_categorias ?? 0 ?></p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Offcanvas para Detalhes da Solicitação -->
<div id="detalhesOffcanvas" class="fixed inset-0 z-50 hidden">
    <!-- Overlay -->
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 transition-opacity" onclick="fecharDetalhes()"></div>
    
    <!-- Offcanvas Panel -->
    <div id="offcanvasPanel" class="fixed right-0 top-0 h-full w-full md:w-[90%] lg:w-[900px] bg-gray-50 shadow-xl transform translate-x-full transition-transform duration-300 ease-in-out overflow-y-auto">
        <!-- Header -->
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 z-10">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class="fas fa-file-alt text-gray-600"></i>
                    <h2 class="text-xl font-bold text-gray-900">Detalhes da Solicitação</h2>
                </div>
                <div class="flex items-center gap-3">
                    <button onclick="copiarInformacoes()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors">
                        <i class="fas fa-copy mr-2"></i>
                        Copiar Informações
                    </button>
                    <button onclick="fecharDetalhes()" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Content -->
        <div id="offcanvasContent" class="p-6">
            <!-- Loading -->
            <div id="loadingContent" class="flex items-center justify-center py-12">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin text-4xl text-blue-600 mb-4"></i>
                    <p class="text-gray-600">Carregando detalhes...</p>
                </div>
            </div>
            
            <!-- Detalhes -->
            <div id="detalhesContent" class="hidden"></div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'app/Views/layouts/admin.php';
?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
function updatePeriod(periodo) {
    // Atualizar URL e recarregar página com novo período
    const url = new URL(window.location.href);
    url.searchParams.set('periodo', periodo);
    window.location.href = url.toString();
}

// Funções do Offcanvas (removidas - usando versão atualizada abaixo)

function copiarInformacoes() {
    if (!offcanvasSolicitacaoId) {
        alert('Nenhuma solicitação selecionada');
        return;
    }
    
    // Buscar dados da solicitação atual do offcanvas
    const solicitacao = window.solicitacaoAtual || null;
    
    if (!solicitacao) {
        alert('Erro: Dados da solicitação não encontrados. Por favor, recarregue a página.');
        return;
    }
    
    // Formatar data de criação
    const dataCriacao = formatarDataHora(solicitacao.created_at);
    const dataCriacaoFormatada = dataCriacao.replace(' às ', ' às ');
    
    // Formatar endereço
    let enderecoCompleto = '';
    if (solicitacao.imovel_endereco) {
        enderecoCompleto = solicitacao.imovel_endereco;
        if (solicitacao.imovel_numero) {
            enderecoCompleto += ', ' + solicitacao.imovel_numero;
        }
    }
    
    // Formatar localização (Bairro/Cidade/Estado)
    const localizacao = [solicitacao.imovel_bairro, solicitacao.imovel_cidade, solicitacao.imovel_estado].filter(Boolean).join(' - ');
    
    // Buscar horários informados pelo locatário
    let horariosLocatario = [];
    if (solicitacao.horarios_indisponiveis) {
        horariosLocatario = solicitacao.datas_opcoes ? JSON.parse(solicitacao.datas_opcoes) : [];
    } else {
        horariosLocatario = solicitacao.horarios_opcoes ? JSON.parse(solicitacao.horarios_opcoes) : [];
    }
    const horariosTexto = Array.isArray(horariosLocatario) ? horariosLocatario.filter(Boolean).join('\n') : '';
    
    // Montar informações completas do locatário para enviar ao prestador
    let info = `═══════════════════════════════════════

📋 INFORMAÇÕES DA SOLICITAÇÃO

═══════════════════════════════════════



🔢 Número da Solicitação: ${solicitacao.numero_solicitacao || 'KSS' + solicitacao.id}

📊 Status: ${solicitacao.status_nome || 'Não informado'}

📅 Data de Criação: ${dataCriacaoFormatada}



═══════════════════════════════════════

👤 DADOS DO LOCATÁRIO

═══════════════════════════════════════



Nome: ${solicitacao.locatario_nome || 'Não informado'}

${solicitacao.locatario_cpf ? `CPF: ${solicitacao.locatario_cpf}\n` : ''}${solicitacao.locatario_telefone ? `Telefone: ${solicitacao.locatario_telefone}\n` : ''}Nº do Contrato: ${solicitacao.numero_contrato || ''}

${solicitacao.imobiliaria_nome ? `Imobiliária: ${solicitacao.imobiliaria_nome}\n` : ''}

${horariosTexto ? `═══════════════════════════════════════

📅 Data Informada pelo Locatário

═══════════════════════════════════════
${horariosTexto}

═══════════════════════════════════════

` : ''}📍 ENDEREÇO DO IMÓVEL

═══════════════════════════════════════



${enderecoCompleto ? `Endereço: ${enderecoCompleto}\n` : ''}${localizacao ? `Bairro/Cidade/Estado: ${localizacao}\n` : ''}${solicitacao.imovel_cep ? `CEP: ${solicitacao.imovel_cep}\n` : ''}

═══════════════════════════════════════

📝 DESCRIÇÃO DO PROBLEMA

═══════════════════════════════════════



${solicitacao.descricao_problema || 'Nenhuma descrição fornecida.'}`.trim();
    
    navigator.clipboard.writeText(info).then(() => {
        alert('✅ Informações copiadas para a área de transferência!');
    }).catch(err => {
        console.error('Erro ao copiar:', err);
        alert('Erro ao copiar informações. Por favor, tente novamente.');
    });
}

function toggleCampoReembolso() {
    const checkbox = document.getElementById('checkboxReembolso');
    const campo = document.getElementById('campoValorReembolso');
    
    if (checkbox.checked) {
        campo.classList.remove('hidden');
    } else {
        campo.classList.add('hidden');
        document.getElementById('valorReembolso').value = '';
    }
}

function formatarMoeda(input) {
    let valor = input.value.replace(/\D/g, '');
    valor = (parseFloat(valor) / 100).toFixed(2);
    
    if (isNaN(valor) || valor === '0.00') {
        input.value = '';
        return;
    }
    
    valor = valor.replace('.', ',');
    valor = valor.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
    input.value = 'R$ ' + valor;
}

function formatarValorMoeda(valor) {
    if (!valor || valor === 0) return '';
    
    let valorFormatado = parseFloat(valor).toFixed(2);
    valorFormatado = valorFormatado.replace('.', ',');
    valorFormatado = valorFormatado.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
    return 'R$ ' + valorFormatado;
}

// ✅ Variáveis globais para rastrear mudanças não salvas
let hasUnsavedChanges = false;
let offcanvasSolicitacaoId = null;

// ✅ Função para monitorar mudanças nos campos
function monitorarMudancas() {
    // Monitorar textarea de observações
    const textarea = document.querySelector('textarea[placeholder*="Descreva qualquer situação"]');
    if (textarea) {
        textarea.addEventListener('input', () => {
            hasUnsavedChanges = true;
        });
        textarea.addEventListener('change', () => {
            hasUnsavedChanges = true;
        });
    }
    
    // Monitorar inputs de texto
    const inputs = document.querySelectorAll('#protocoloSeguradora, #valorReembolso');
    inputs.forEach(input => {
        if (input) {
            input.addEventListener('input', () => {
                hasUnsavedChanges = true;
            });
            input.addEventListener('change', () => {
                hasUnsavedChanges = true;
            });
        }
    });
    
    // Monitorar checkbox de reembolso
    const checkboxReembolso = document.getElementById('checkboxReembolso');
    if (checkboxReembolso) {
        checkboxReembolso.addEventListener('change', () => {
            hasUnsavedChanges = true;
        });
    }
    
    // Monitorar checkboxes de horários
    const checkboxes = document.querySelectorAll('.horario-offcanvas');
    checkboxes.forEach(chk => {
        chk.addEventListener('change', () => {
            hasUnsavedChanges = true;
        });
    });
}

function abrirDetalhes(solicitacaoId) {
    const offcanvas = document.getElementById('detalhesOffcanvas');
    const panel = document.getElementById('offcanvasPanel');
    const loadingContent = document.getElementById('loadingContent');
    const detalhesContent = document.getElementById('detalhesContent');
    
    // ✅ Resetar flag de mudanças não salvas
    hasUnsavedChanges = false;
    offcanvasSolicitacaoId = solicitacaoId;
    
    // Mostrar offcanvas
    offcanvas.classList.remove('hidden');
    
    // Animar entrada
    setTimeout(() => {
        panel.classList.remove('translate-x-full');
    }, 10);
    
    // Mostrar loading
    loadingContent.classList.remove('hidden');
    detalhesContent.classList.add('hidden');
    
    // Carregar dados
    fetch('<?= url('admin/solicitacoes/') ?>' + solicitacaoId + '/api')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Armazenar solicitação globalmente para uso em copiarInformacoes
                window.solicitacaoAtual = data.solicitacao;
                renderizarDetalhes(data.solicitacao);
                // ✅ Monitorar mudanças após renderizar
                setTimeout(() => {
                    monitorarMudancas();
                }, 100);
            } else {
                detalhesContent.innerHTML = `
                    <div class="text-center py-12">
                        <i class="fas fa-exclamation-triangle text-4xl text-red-600 mb-4"></i>
                        <p class="text-gray-600">${data.message || 'Erro ao carregar detalhes'}</p>
                    </div>
                `;
            }
            loadingContent.classList.add('hidden');
            detalhesContent.classList.remove('hidden');
        })
        .catch(error => {
            console.error('Erro:', error);
            detalhesContent.innerHTML = `
                <div class="text-center py-12">
                    <i class="fas fa-exclamation-triangle text-4xl text-red-600 mb-4"></i>
                    <p class="text-gray-600">Erro ao carregar detalhes da solicitação</p>
                </div>
            `;
            loadingContent.classList.add('hidden');
            detalhesContent.classList.remove('hidden');
        });
}

function fecharDetalhes() {
    // ✅ Verificar se há mudanças não salvas
    if (hasUnsavedChanges) {
        const confirm = window.confirm(
            'Você tem alterações não salvas. Deseja realmente fechar?\n\n' +
            'As alterações serão perdidas se você não salvar.'
        );
        if (!confirm) {
            return;
        }
    }
    
    // Limpar flags
    hasUnsavedChanges = false;
    offcanvasSolicitacaoId = null;
    
    const offcanvas = document.getElementById('detalhesOffcanvas');
    const panel = document.getElementById('offcanvasPanel');
    
    // Animar saída
    panel.classList.add('translate-x-full');
    
    // Esconder offcanvas após animação
    setTimeout(() => {
        offcanvas.classList.add('hidden');
    }, 300);
}

// ✅ Prevenir navegação se houver mudanças não salvas
window.addEventListener('beforeunload', (e) => {
    if (hasUnsavedChanges) {
        e.preventDefault();
        e.returnValue = 'Você tem alterações não salvas. Deseja realmente sair?';
        return e.returnValue;
    }
});

function renderizarDetalhes(solicitacao) {
    const content = document.getElementById('detalhesContent');
    
    // ✅ Resetar flag de mudanças ao renderizar
    hasUnsavedChanges = false;
    
    const statusClass = getStatusClass(solicitacao.status_nome);
    const prioridadeClass = getPrioridadeClass(solicitacao.prioridade);
    
    // Parse horários se existirem
    let horariosOpcoes = [];
    try {
        horariosOpcoes = solicitacao.horarios_opcoes ? JSON.parse(solicitacao.horarios_opcoes) : [];
    } catch (e) {
        horariosOpcoes = [];
    }
    
    content.innerHTML = `
        <!-- Cabeçalho com ID e Data -->
        <div class="bg-white rounded-lg p-5 mb-4">
            <div class="flex items-start justify-between">
                <div>
                    <div class="text-3xl font-bold text-gray-900 mb-2">${solicitacao.numero_solicitacao || 'KSS' + solicitacao.id}</div>
                    <div class="text-lg font-semibold text-gray-800">${solicitacao.categoria_nome}</div>
                    ${solicitacao.subcategoria_nome ? `<div class="text-sm text-gray-600 mt-1">${solicitacao.subcategoria_nome}</div>` : ''}
                </div>
                <div class="text-right text-sm text-gray-500">
                    ${formatarData(solicitacao.created_at)}
                </div>
            </div>
        </div>
        
        <!-- Layout 2 Colunas -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <!-- COLUNA ESQUERDA -->
            <div class="space-y-4">
                
                <!-- Informações do Cliente -->
                <div class="bg-white rounded-lg p-5">
                    <div class="flex items-center gap-2 mb-4">
                        <i class="fas fa-user text-gray-600"></i>
                        <h3 class="font-semibold text-gray-900">Informações do Cliente</h3>
                    </div>
                    <div class="space-y-3 text-sm">
                        <div>
                            <p class="text-gray-500 mb-1">Nome:</p>
                            <p class="font-medium text-gray-900">${solicitacao.locatario_nome}</p>
                        </div>
                        ${solicitacao.locatario_cpf ? `
                        <div>
                            <p class="text-gray-500 mb-1">CPF:</p>
                            <p class="font-medium text-gray-900">${solicitacao.locatario_cpf}</p>
                        </div>
                        ` : ''}
                        ${solicitacao.imobiliaria_nome ? `
                        <div>
                            <p class="text-gray-500 mb-1">Imobiliária:</p>
                            <p class="font-medium text-gray-900">${solicitacao.imobiliaria_nome}</p>
                        </div>
                        ` : ''}
                    </div>
                </div>
                
                <!-- Descrição do Problema -->
                <div class="bg-white rounded-lg p-5">
                    <div class="flex items-center gap-2 mb-3">
                        <i class="fas fa-clipboard-list text-gray-600"></i>
                        <h3 class="font-semibold text-gray-900">Descrição do Problema</h3>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded p-3 text-sm text-gray-900 min-h-[80px]">
                        ${solicitacao.descricao_problema || 'Nenhuma descrição fornecida.'}
                    </div>
                </div>
                
                <!-- Serviço -->
                <div class="bg-white rounded-lg p-5">
                    <div class="flex items-center gap-2 mb-4">
                        <i class="fas fa-tools text-gray-600"></i>
                        <h3 class="font-semibold text-gray-900">Informações do Serviço</h3>
                    </div>
                    <div class="space-y-3 text-sm">
                        <div>
                            <p class="text-gray-500 mb-1">Categoria:</p>
                            <p class="font-medium text-gray-900">${solicitacao.categoria_nome}</p>
                        </div>
                        ${solicitacao.subcategoria_nome ? `
                        <div>
                            <p class="text-gray-500 mb-1">Subcategoria:</p>
                            <p class="font-medium text-gray-900">${solicitacao.subcategoria_nome}</p>
                        </div>
                        ` : ''}
                        <div>
                            <p class="text-gray-500 mb-1">Prioridade:</p>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium ${prioridadeClass}">
                                ${solicitacao.prioridade}
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Disponibilidade Informada -->
                ${horariosOpcoes.length > 0 ? `
                <div class="bg-white rounded-lg p-5">
                    <div class="flex items-center gap-2 mb-3">
                        <i class="fas fa-clock text-gray-600"></i>
                        <div>
                            <h3 class="font-semibold text-gray-900">Disponibilidade Informada pelo Segurado</h3>
                            <p class="text-xs text-gray-500">Horários da Solicitação Inicial</p>
                        </div>
                    </div>
                    <div class="space-y-2">
                        ${horariosOpcoes.map((horario, index) => {
                            try {
                                const dt = new Date(horario);
                                const dia = String(dt.getDate()).padStart(2, '0');
                                const mes = String(dt.getMonth() + 1).padStart(2, '0');
                                const ano = dt.getFullYear();
                                const hora = String(dt.getHours()).padStart(2, '0');
                                const min = String(dt.getMinutes()).padStart(2, '0');
                                const faixaHora = hora + ':00-' + (parseInt(hora) + 3) + ':00';
                                const textoHorario = `${dia}/${mes}/${ano} - ${faixaHora}`;
                                
                                // ✅ Verificar se este horário está confirmado (igual ao Kanban)
                                let isConfirmed = false;
                                
                                // 1. Verificar em confirmed_schedules (JSON) - PRIORIDADE
                                if (solicitacao.confirmed_schedules) {
                                    try {
                                        const confirmed = typeof solicitacao.confirmed_schedules === 'string' 
                                            ? JSON.parse(solicitacao.confirmed_schedules) 
                                            : solicitacao.confirmed_schedules;
                                        
                                        if (Array.isArray(confirmed) && confirmed.length > 0) {
                                            isConfirmed = confirmed.some(s => {
                                                if (!s) return false;
                                                
                                                if (s.raw) {
                                                    const sRaw = String(s.raw).trim().replace(/\s+/g, ' ');
                                                    const textoNorm = textoHorario.trim().replace(/\s+/g, ' ');
                                                    
                                                    if (sRaw === textoNorm) return true;
                                                    if (sRaw.includes(textoNorm) || textoNorm.includes(sRaw)) return true;
                                                }
                                                
                                                if (s.date && s.time) {
                                                    try {
                                                        const sDate = new Date(s.date);
                                                        const sTime = String(s.time).trim();
                                                        const dtNorm = new Date(dt);
                                                        
                                                        if (sDate.getDate() === dtNorm.getDate() &&
                                                            sDate.getMonth() === dtNorm.getMonth() &&
                                                            sDate.getFullYear() === dtNorm.getFullYear()) {
                                                            const hora = String(dt.getHours()).padStart(2, '0');
                                                            const horaMatch = sTime.includes(hora) || hora.includes(sTime.substring(0, 2));
                                                            if (horaMatch) return true;
                                                        }
                                                    } catch (e) {
                                                        // Ignorar erro de data
                                                    }
                                                }
                                                
                                                return false;
                                            });
                                        }
                                    } catch (e) {
                                        console.error('Erro ao parsear confirmed_schedules:', e);
                                    }
                                }
                                
                                // 2. Verificar em horario_confirmado_raw (texto direto)
                                if (!isConfirmed && solicitacao.horario_confirmado_raw) {
                                    const horarioRaw = String(solicitacao.horario_confirmado_raw).trim();
                                    const textoNorm = textoHorario.trim();
                                    
                                    if (horarioRaw === textoNorm) {
                                        isConfirmed = true;
                                    } else {
                                        const rawNorm = horarioRaw.replace(/\s+/g, ' ').trim();
                                        const textoNorm2 = textoNorm.replace(/\s+/g, ' ').trim();
                                        
                                        if (rawNorm === textoNorm2) {
                                            isConfirmed = true;
                                        } else if (horarioRaw.includes(textoNorm) || textoNorm.includes(horarioRaw)) {
                                            isConfirmed = true;
                                        } else {
                                            const regex = /(\d{2}\/\d{2}\/\d{4})\s*-\s*(\d{2}:\d{2})/;
                                            const matchRaw = horarioRaw.match(regex);
                                            const matchTexto = textoNorm.match(regex);
                                            
                                            if (matchRaw && matchTexto) {
                                                const [, dataRaw, horaIniRaw] = matchRaw;
                                                const [, dataTexto, horaIniTexto] = matchTexto;
                                                
                                                if (dataRaw === dataTexto && horaIniRaw === horaIniTexto) {
                                                    isConfirmed = true;
                                                }
                                            }
                                        }
                                    }
                                }
                                
                                // 3. Verificar por data_agendamento + horario_agendamento (compatibilidade)
                                if (!isConfirmed && solicitacao.data_agendamento && solicitacao.horario_agendamento) {
                                    try {
                                        const dataAg = new Date(solicitacao.data_agendamento);
                                        const horaAg = String(solicitacao.horario_agendamento).trim();
                                        
                                        if (dataAg.getDate() === dt.getDate() &&
                                            dataAg.getMonth() === dt.getMonth() &&
                                            dataAg.getFullYear() === dt.getFullYear()) {
                                            const hora = String(dt.getHours()).padStart(2, '0');
                                            if (horaAg.includes(hora)) {
                                                isConfirmed = true;
                                            }
                                        }
                                    } catch (e) {
                                        // Ignorar erro de data
                                    }
                                }
                                
                                return `
                                <div class="flex items-center gap-3 py-2 ${isConfirmed ? 'bg-green-50 rounded px-2' : ''}">
                                    <input type="checkbox" 
                                           class="w-4 h-4 text-blue-600 rounded horario-offcanvas" 
                                           data-raw="${textoHorario}" 
                                           id="horario-${index}"
                                           ${isConfirmed ? 'checked' : ''}>
                                    <label for="horario-${index}" class="text-sm text-gray-700 flex items-center gap-2">
                                        ${isConfirmed ? '<i class="fas fa-check-circle text-green-600"></i>' : ''}
                                        <span>${textoHorario}</span>
                                        ${isConfirmed ? '<span class="text-xs text-green-700 font-semibold">(Confirmado)</span>' : ''}
                                    </label>
                                </div>
                                `;
                            } catch (e) {
                                return '';
                            }
                        }).join('')}
                    </div>
                    <div class="mt-4 pt-3 border-t">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" class="w-4 h-4 text-blue-600 rounded">
                            <span class="text-sm text-gray-700">Nenhum horário está disponível</span>
                        </label>
                    </div>
                </div>
                ` : ''}
                
                <!-- Anexar Documento -->
                <div class="bg-white rounded-lg p-5">
                    <div class="flex items-center gap-2 mb-3">
                        <i class="fas fa-paperclip text-gray-600"></i>
                        <h3 class="font-semibold text-gray-900">Anexar Documento</h3>
                    </div>
                    <p class="text-xs text-gray-500 mb-3">(PDF, DOC, DOCX, JPG, PNG - máx 5MB)</p>
                    <button class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition-colors">
                        Anexar Documento
                    </button>
                    <p class="text-xs text-gray-500 mt-2">0/3 documentos</p>
                </div>
                
            </div>
            
            <!-- COLUNA DIREITA -->
            <div class="space-y-4">
                
                <!-- Endereço -->
                ${solicitacao.imovel_endereco ? `
                <div class="bg-white rounded-lg p-5">
                    <div class="flex items-center gap-2 mb-3">
                        <i class="fas fa-map-marker-alt text-gray-600"></i>
                        <h3 class="font-semibold text-gray-900">Endereço</h3>
                    </div>
                    <div class="text-sm text-gray-900">
                        <p class="font-medium">${solicitacao.imovel_endereco}${solicitacao.imovel_numero ? ', ' + solicitacao.imovel_numero : ''}</p>
                    </div>
                </div>
                ` : ''}
                
                <!-- Observações do Segurado -->
                <div class="bg-white rounded-lg p-5">
                    <div class="flex items-center gap-2 mb-3">
                        <i class="fas fa-comment-dots text-gray-600"></i>
                        <h3 class="font-semibold text-gray-900">Observações do Segurado</h3>
                    </div>
                    <textarea class="w-full bg-gray-50 border border-gray-200 rounded p-3 text-sm text-gray-700 min-h-[120px] resize-none" 
                              placeholder="Descreva qualquer situação adicional (ex: prestador não compareceu, precisa comprar peças, etc.)">${solicitacao.observacoes || ''}</textarea>
                </div>
                
                <!-- Precisa de Reembolso -->
                <div class="bg-white rounded-lg p-5">
                    <label class="flex items-center gap-2 mb-3">
                        <input type="checkbox" 
                               id="checkboxReembolso" 
                               class="w-4 h-4 text-blue-600 rounded" 
                               onchange="toggleCampoReembolso()"
                               ${solicitacao.precisa_reembolso ? 'checked' : ''}>
                        <span class="text-sm font-medium text-gray-900">Precisa de Reembolso?</span>
                    </label>
                    <div id="campoValorReembolso" class="${solicitacao.precisa_reembolso ? '' : 'hidden'} mt-3">
                        <label class="text-xs text-gray-600 mb-1 block">Valor do Reembolso (R$)</label>
                        <input type="text" 
                               id="valorReembolso"
                               placeholder="R$ 0,00" 
                               value="${solicitacao.valor_reembolso ? formatarValorMoeda(solicitacao.valor_reembolso) : ''}"
                               class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-sm text-gray-900"
                               onkeyup="formatarMoeda(this)">
                    </div>
                </div>
                
                <!-- Status da Solicitação -->
                <div class="bg-white rounded-lg p-5">
                    <div class="flex items-center gap-2 mb-3">
                        <i class="fas fa-info-circle text-gray-600"></i>
                        <h3 class="font-semibold text-gray-900">Status da Solicitação</h3>
                    </div>
                    <select class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-sm text-gray-900">
                        <option selected>${solicitacao.status_nome}</option>
                    </select>
                </div>
                
                <!-- Protocolo da Seguradora -->
                <div class="bg-white rounded-lg p-5">
                    <div class="flex items-center gap-2 mb-3">
                        <i class="fas fa-hashtag text-gray-600"></i>
                        <h3 class="font-semibold text-gray-900">Protocolo da Seguradora</h3>
                    </div>
                    <input type="text" 
                           id="protocoloSeguradora"
                           placeholder="Ex.: 123456/2025" 
                           value="${solicitacao.protocolo_seguradora || ''}"
                           class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-sm text-gray-900">
                </div>
                
                <!-- Linha do Tempo -->
                <div class="bg-white rounded-lg p-5">
                    <div class="flex items-center gap-2 mb-4">
                        <i class="fas fa-history text-gray-600"></i>
                        <h3 class="font-semibold text-gray-900">Linha do Tempo</h3>
                    </div>
                    <div class="space-y-4">
                        <div class="flex gap-3">
                            <div class="flex flex-col items-center">
                                <div class="w-3 h-3 rounded-full bg-yellow-400"></div>
                                <div class="w-0.5 h-full bg-gray-200 mt-1"></div>
                            </div>
                            <div class="flex-1 pb-4">
                                <p class="font-medium text-sm text-gray-900">${solicitacao.status_nome}</p>
                                <p class="text-xs text-gray-500 mt-1">Por Sistema</p>
                                <p class="text-xs text-gray-500">Solicitação criada</p>
                                <p class="text-xs text-gray-400 mt-2">${formatarDataHora(solicitacao.created_at)}</p>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
        
        <!-- Botões de Ação -->
        <div class="mt-6 flex gap-3">
            <button id="btnSalvarAlteracoes" onclick="salvarAlteracoes(${solicitacao.id})" 
                    class="flex-1 flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white py-3 px-6 rounded-lg font-medium transition-colors">
                <i class="fas fa-save"></i>
                Salvar Alterações
            </button>
            <a href="<?= url('admin/solicitacoes/') ?>${solicitacao.id}" 
               class="px-6 py-3 bg-white hover:bg-gray-50 text-gray-700 font-medium rounded-lg transition-colors border border-gray-300">
                <i class="fas fa-external-link-square-alt mr-2"></i>
                Ver Página Completa
            </a>
            <button onclick="fecharDetalhes()" 
                    class="px-6 py-3 bg-white hover:bg-gray-50 text-gray-700 font-medium rounded-lg transition-colors border border-gray-300">
                Fechar
            </button>
        </div>
    `;
}

// ======== Horários (offcanvas) ========
function parseScheduleRawOffcanvas(raw) {
    const out = { date: null, time: null, raw };
    if (!raw) return out;
    const dBR = raw.match(/(\d{2})\/(\d{2})\/(\d{4})/);
    if (dBR) out.date = `${dBR[3]}-${dBR[2]}-${dBR[1]}`;
    const range = raw.match(/(\d{2}:\d{2})\s?-\s?(\d{2}:\d{2})/);
    if (range) out.time = `${range[1]}-${range[2]}`; else {
        const single = raw.match(/\b(\d{2}:\d{2})\b/);
        if (single) out.time = single[1];
    }
    return out;
}

function coletarSchedulesOffcanvas() {
    return Array.from(document.querySelectorAll('.horario-offcanvas:checked'))
        .map(chk => parseScheduleRawOffcanvas(chk.getAttribute('data-raw')))
        .filter(s => s.date || s.time);
}

function salvarAlteracoes(solicitacaoId) {
    // ✅ Loading state granular: Desabilitar botão e mostrar feedback
    const btnSalvar = document.getElementById('btnSalvarAlteracoes');
    const originalText = btnSalvar.innerHTML;
    const originalDisabled = btnSalvar.disabled;
    
    btnSalvar.disabled = true;
    btnSalvar.innerHTML = `
        <i class="fas fa-spinner fa-spin mr-2"></i>
        Salvando...
    `;
    btnSalvar.classList.remove('hover:bg-blue-700');
    
    // Coletar dados do formulário
    const observacoes = document.querySelector('textarea[placeholder*="Descreva qualquer situação"]')?.value || '';
    const precisaReembolso = document.getElementById('checkboxReembolso')?.checked || false;
    
    // Pegar o valor do reembolso e converter corretamente
    let valorReembolso = '0';
    const inputValor = document.getElementById('valorReembolso')?.value || '';
    if (inputValor && inputValor.trim() !== '') {
        // Remove "R$", pontos (separador de milhar) e troca vírgula por ponto
        valorReembolso = inputValor.replace('R$', '').replace(/\s/g, '').replace(/\./g, '').replace(',', '.');
    }
    
    const protocoloSeguradora = document.getElementById('protocoloSeguradora')?.value || '';
    
    // ✅ Coletar horários selecionados (sempre enviar, mesmo que vazio)
    const schedules = coletarSchedulesOffcanvas();
    
    // Criar objeto com os dados
    const dados = {
        observacoes: observacoes,
        precisa_reembolso: precisaReembolso,
        valor_reembolso: valorReembolso,
        protocolo_seguradora: protocoloSeguradora,
        schedules: schedules  // ✅ Sempre enviar (array vazio se nenhum marcado)
    };
    
    console.log('Dados a serem salvos:', dados); // Debug
    
    // Enviar para o servidor
    fetch(`<?= url('admin/solicitacoes/') ?>${solicitacaoId}/atualizar`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(dados)
    })
    .then(async response => {
        // ✅ Verificar se a resposta é JSON válido antes de parsear
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Resposta não é JSON:', text);
            throw new Error('Resposta do servidor não é JSON válido. ' + text.substring(0, 200));
        }
        return response.json();
    })
    .then(data => {
        console.log('Resposta do servidor:', data); // Debug
        if (data.success) {
            // ✅ Feedback específico de sucesso
            btnSalvar.innerHTML = `
                <i class="fas fa-check mr-2"></i>
                Salvo!
            `;
            btnSalvar.classList.remove('bg-blue-600');
            btnSalvar.classList.add('bg-green-600');
            
            // Limpar flag de mudanças não salvas
            hasUnsavedChanges = false;
            
            // Aguardar um momento antes de fechar para mostrar feedback
            setTimeout(() => {
                fecharDetalhes();
                // Recarregar a página para atualizar os dados
                window.location.reload();
            }, 1000);
        } else {
            // ✅ Restaurar botão em caso de erro
            btnSalvar.innerHTML = originalText;
            btnSalvar.disabled = originalDisabled;
            btnSalvar.classList.add('hover:bg-blue-700');
            alert('Erro ao salvar: ' + (data.error || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        // ✅ Restaurar botão em caso de erro
        btnSalvar.innerHTML = originalText;
        btnSalvar.disabled = originalDisabled;
        btnSalvar.classList.add('hover:bg-blue-700');
        alert('Erro ao salvar alterações. Tente novamente.');
    });
}

function getStatusClass(status) {
    const statusMap = {
        'Nova Solicitação': 'status-nova-solicitacao',
        'Buscando Prestador': 'status-buscando-prestador',
        'Serviço Agendado': 'status-servico-agendado',
        'Em Andamento': 'status-em-andamento',
        'Concluído': 'status-concluido',
        'Cancelado': 'status-cancelado'
    };
    return statusMap[status] || 'bg-gray-100 text-gray-800';
}

function getPrioridadeClass(prioridade) {
    const prioridadeMap = {
        'ALTA': 'bg-red-100 text-red-800',
        'MEDIA': 'bg-yellow-100 text-yellow-800',
        'NORMAL': 'bg-green-100 text-green-800',
        'BAIXA': 'bg-blue-100 text-blue-800'
    };
    return prioridadeMap[prioridade] || 'bg-gray-100 text-gray-800';
}

function formatarData(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR');
}

function formatarDataHora(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR') + ' às ' + date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
}

// Aguardar o DOM estar pronto
document.addEventListener('DOMContentLoaded', function() {
    // Gráfico de Status
    const statusCtx = document.getElementById('statusChart');
    if (statusCtx) {
        const statusChart = new Chart(statusCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Concluídas', 'Novas', 'Agendados', 'Aguardando Peça', 'Outros'],
                datasets: [{
                    data: [
                        <?= $estatisticas['concluidas'] ?? 0 ?>,
                        <?= $estatisticas['novas'] ?? 0 ?>,
                        <?= $estatisticas['agendados'] ?? 0 ?>,
                        <?= $estatisticas['aguardando_peca'] ?? 0 ?>,
                        <?= ($estatisticas['total'] ?? 0) - ($estatisticas['concluidas'] ?? 0) - ($estatisticas['novas'] ?? 0) - ($estatisticas['agendados'] ?? 0) - ($estatisticas['aguardando_peca'] ?? 0) ?>
                    ],
                    backgroundColor: [
                        '#10B981',  // Verde - Concluídas
                        '#3B82F6',  // Azul - Novas
                        '#8B5CF6',  // Roxo - Agendados
                        '#F59E0B',  // Laranja - Aguardando Peça
                        '#6B7280'   // Cinza - Outros
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                let value = context.parsed || 0;
                                let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                let percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return label + ': ' + value + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    // Gráfico de Imobiliárias
    const imobiliariaCtx = document.getElementById('imobiliariaChart');
    let imobiliariaChart = null;
    
    function carregarGraficoImobiliarias(periodo) {
        const periodoAtual = periodo || '<?= $periodo ?? '30' ?>';
        
        fetch('<?= url('admin/dashboard/solicitacoes-por-imobiliaria') ?>?periodo=' + periodoAtual)
            .then(response => response.json())
            .then(data => {
                const labels = data.map(item => item.imobiliaria_nome || 'Sem imobiliária');
                const valores = data.map(item => parseInt(item.quantidade) || 0);
                
                if (imobiliariaChart) {
                    imobiliariaChart.destroy();
                }
                
                imobiliariaChart = new Chart(imobiliariaCtx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Solicitações',
                            data: valores,
                            backgroundColor: '#3B82F6'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            })
            .catch(error => {
                console.error('Erro ao carregar dados do gráfico de imobiliárias:', error);
            });
    }
    
    if (imobiliariaCtx) {
        carregarGraficoImobiliarias();
    }
});
</script>
