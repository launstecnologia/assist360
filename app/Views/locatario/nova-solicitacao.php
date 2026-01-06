<?php
/**
 * View: Nova Solicitação do Locatário - Sistema de Steps
 */
$title = 'Nova Solicitação - Assistência 360°';
$currentPage = 'locatario-nova-solicitacao';
ob_start();

// Definir etapa atual (pode vir do controller, da sessão, ou padrão 1)
$etapaAtual = $etapa ?? $_SESSION['nova_solicitacao']['etapa'] ?? 1;
$etapaAtual = (int)$etapaAtual;

// Se não há dados na sessão e não é etapa 1, forçar etapa 1
if (!isset($_SESSION['nova_solicitacao']) && $etapaAtual > 1) {
    $etapaAtual = 1;
}

// Definir steps
$steps = [
    1 => ['nome' => 'Endereço', 'icone' => 'fas fa-map-marker-alt'],
    2 => ['nome' => 'Serviço', 'icone' => 'fas fa-cog'],
    3 => ['nome' => 'Descrição', 'icone' => 'fas fa-edit'],
    4 => ['nome' => 'Agendamento', 'icone' => 'fas fa-calendar'],
    5 => ['nome' => 'Confirmação', 'icone' => 'fas fa-check']
];

// Função para gerar resumo das etapas anteriores
function gerarResumoEtapas($etapaAtual, $locatario) {
    $nova_solicitacao = $_SESSION['nova_solicitacao'] ?? [];
    $resumo = [];
    
    // Etapa 1: Endereço
    if ($etapaAtual > 1 && isset($nova_solicitacao['endereco_selecionado'])) {
        $imovel = $locatario['imoveis'][$nova_solicitacao['endereco_selecionado']] ?? [];
        if (!empty($imovel)) {
            $endereco = htmlspecialchars($imovel['endereco'] ?? '') . ', ' . htmlspecialchars($imovel['numero'] ?? '');
            $resumo[] = [
                'titulo' => 'Endereço',
                'icone' => 'fas fa-map-marker-alt',
                'conteudo' => $endereco
            ];
        }
    }
    
    // Etapa 2: Serviço
    if ($etapaAtual > 2 && isset($nova_solicitacao['subcategoria_id'])) {
        $subcategoriaModel = new \App\Models\Subcategoria();
        $subcategoria = $subcategoriaModel->find($nova_solicitacao['subcategoria_id']);
        if ($subcategoria) {
            $resumo[] = [
                'titulo' => 'Serviço',
                'icone' => 'fas fa-cog',
                'conteudo' => htmlspecialchars($subcategoria['nome'] ?? '')
            ];
        }
    }
    
    // Etapa 3: Descrição
    if ($etapaAtual > 3 && !empty($nova_solicitacao['descricao_problema'])) {
        $descricao = htmlspecialchars($nova_solicitacao['descricao_problema']);
        if (strlen($descricao) > 100) {
            $descricao = substr($descricao, 0, 100) . '...';
        }
        $resumo[] = [
            'titulo' => 'Descrição',
            'icone' => 'fas fa-edit',
            'conteudo' => $descricao
        ];
    }
    
    // Etapa 4: Agendamento
    if ($etapaAtual > 4 && !empty($nova_solicitacao['horarios_preferenciais'])) {
        $horarios = $nova_solicitacao['horarios_preferenciais'];
        if (is_array($horarios) && !empty($horarios)) {
            $primeiroHorario = $horarios[0];
            $horarioFormatado = $primeiroHorario;
            
            // Formatar horário: "2025-12-23 08:00:00-11:00:00" -> "23/12/2025 08:00 - 11:00"
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2})\s+(\d{2}):(\d{2}):\d{2}-(\d{2}):(\d{2}):\d{2}$/', $primeiroHorario, $matches)) {
                // Formato: YYYY-MM-DD HH:MM:SS-HH:MM:SS
                $horarioFormatado = $matches[3] . '/' . $matches[2] . '/' . $matches[1] . ' ' . $matches[4] . ':' . $matches[5] . ' - ' . $matches[6] . ':' . $matches[7];
            } elseif (preg_match('/^(\d{4})-(\d{2})-(\d{2})\s+(\d{2}):(\d{2})/', $primeiroHorario, $matches)) {
                // Formato: YYYY-MM-DD HH:MM (sem intervalo)
                $horarioFormatado = $matches[3] . '/' . $matches[2] . '/' . $matches[1] . ' ' . $matches[4] . ':' . $matches[5];
            }
            
                $totalHorarios = count($horarios);
            $texto = $horarioFormatado;
                if ($totalHorarios > 1) {
                    $opcoesAdicionais = $totalHorarios - 1;
                    $texto .= ' (+' . $opcoesAdicionais . ' ' . ($opcoesAdicionais > 1 ? 'opções' : 'opção') . ')';
                }
            
                $resumo[] = [
                    'titulo' => 'Agendamento',
                    'icone' => 'fas fa-calendar',
                    'conteudo' => $texto
                ];
        }
    }
    
    return $resumo;
}
?>

<!-- Header -->
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-plus-circle mr-2"></i>
                Nova Solicitação
            </h1>
        </div>
        <a href="<?= url($locatario['instancia'] . '/dashboard') ?>" 
           class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>
            Voltar
        </a>
    </div>
</div>

<!-- Progress Steps -->
<div class="mb-8">
    <!-- Mobile: Versão compacta apenas com números -->
    <div class="md:hidden">
        <div class="flex items-center justify-between">
            <?php foreach ($steps as $numero => $step): ?>
                <div class="flex flex-col items-center flex-1">
                    <!-- Step Circle -->
                    <div class="flex items-center justify-center w-8 h-8 rounded-full border-2 <?= $numero <= $etapaAtual ? 'bg-green-600 border-green-600 text-white' : 'border-gray-300 text-gray-400' ?>">
                        <?php if ($numero < $etapaAtual): ?>
                            <i class="fas fa-check text-xs"></i>
                        <?php else: ?>
                            <span class="text-xs font-medium"><?= $numero ?></span>
                        <?php endif; ?>
                    </div>
                    <!-- Step Label (apenas para etapa atual) -->
                    <?php if ($numero == $etapaAtual): ?>
                        <p class="text-xs font-medium text-green-600 mt-1 text-center"><?= $step['nome'] ?></p>
                    <?php endif; ?>
                </div>
                <?php if ($numero < count($steps)): ?>
                    <div class="flex-1 mx-1 h-0.5 <?= $numero < $etapaAtual ? 'bg-green-600' : 'bg-gray-300' ?>"></div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Desktop: Versão completa -->
    <div class="hidden md:flex items-center justify-between">
        <?php foreach ($steps as $numero => $step): ?>
            <div class="flex items-center">
                <!-- Step Circle -->
                <div class="flex items-center justify-center w-10 h-10 rounded-full border-2 <?= $numero <= $etapaAtual ? 'bg-green-600 border-green-600 text-white' : 'border-gray-300 text-gray-400' ?>">
                    <?php if ($numero < $etapaAtual): ?>
                        <i class="fas fa-check text-sm"></i>
                    <?php else: ?>
                        <span class="text-sm font-medium"><?= $numero ?></span>
                    <?php endif; ?>
                </div>
                
                <!-- Step Label -->
                <div class="ml-3">
                    <p class="text-sm font-medium <?= $numero <= $etapaAtual ? 'text-green-600' : 'text-gray-400' ?>">
                        <?= $step['nome'] ?>
                    </p>
                </div>
                
                <!-- Connector Line -->
                <?php if ($numero < count($steps)): ?>
                    <div class="flex-1 mx-4 h-0.5 <?= $numero < $etapaAtual ? 'bg-green-600' : 'bg-gray-300' ?>"></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Messages -->
<?php if (isset($_GET['error'])): ?>
    <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded alert-message">
        <i class="fas fa-exclamation-circle mr-2"></i>
        <?= htmlspecialchars($_GET['error']) ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['success'])): ?>
    <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded alert-message">
        <i class="fas fa-check-circle mr-2"></i>
        <?= htmlspecialchars($_GET['success']) ?>
    </div>
<?php endif; ?>

<!-- Step Content -->
    <div class="bg-white rounded-lg shadow-sm">
    <?php 
    // Exibir resumo das etapas anteriores (exceto na etapa 1 e na última etapa)
    if ($etapaAtual > 1 && $etapaAtual < 5):
        $resumoEtapas = gerarResumoEtapas($etapaAtual, $locatario);
        if (!empty($resumoEtapas)):
    ?>
        <!-- Resumo das Etapas Anteriores - Dropdown -->
        <div class="px-6 py-3 bg-gray-50 border-b border-gray-200">
            <button type="button" 
                    onclick="toggleResumoEtapas()" 
                    class="w-full flex items-center justify-between text-left focus:outline-none focus:ring-2 focus:ring-green-500 rounded-lg p-2 -m-2">
                <div class="flex items-center">
                    <i class="fas fa-list-ul text-gray-600 mr-2"></i>
                    <h3 class="text-sm font-medium text-gray-700 whitespace-nowrap">Resumo das Etapas Anteriores</h3>
                </div>
                <i class="fas fa-chevron-down text-gray-400 transition-transform duration-200" id="resumo-chevron"></i>
            </button>
            <div id="resumo-conteudo" class="hidden mt-3 pt-3 border-t border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-<?= min(count($resumoEtapas), 4) ?> gap-3">
                    <?php foreach ($resumoEtapas as $item): ?>
                        <div class="bg-white rounded-lg p-3 border border-gray-200">
                            <div class="flex items-start">
                                <i class="<?= $item['icone'] ?> text-gray-400 mr-2 mt-0.5 text-sm"></i>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-medium text-gray-500 mb-1"><?= $item['titulo'] ?></p>
                                    <p class="text-sm text-gray-900 truncate" title="<?= htmlspecialchars($item['conteudo']) ?>">
                                        <?= $item['conteudo'] ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php 
        endif;
    endif; 
    ?>
    
    <?php if ($etapaAtual == 1): ?>
        <!-- ETAPA 1: ENDEREÇO -->
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">
                <i class="fas fa-map-marker-alt mr-2"></i>
                Onde será realizado o serviço?
            </h2>
            <p class="text-sm text-gray-500 mt-1">Selecione um endereço salvo e o tipo de propriedade</p>
        </div>
        
        <div class="p-6">
            <form method="POST" action="<?= url($locatario['instancia'] . '/nova-solicitacao') ?>" class="space-y-6">
                <?= \App\Core\View::csrfField() ?>
                <input type="hidden" name="etapa" value="1">
                
                <!-- Endereços Salvos - ABORDAGEM SIMPLIFICADA COM INLINE STYLES -->
                <div>
                    <h3 class="text-sm font-medium text-gray-700 mb-3">Endereços Salvos</h3>
                    <div id="endereco-container-live">
                        <?php if (!empty($locatario['imoveis'])): ?>
                            <?php foreach ($locatario['imoveis'] as $index => $imovel): ?>
                                <?php
                                $endereco = $imovel['endereco'] ?? '';
                                $numero = $imovel['numero'] ?? '';
                                $bairro = $imovel['bairro'] ?? '';
                                $cidade = $imovel['cidade'] ?? '';
                                $uf = $imovel['uf'] ?? '';
                                $cep = $imovel['cep'] ?? '';
                                $codigo = $imovel['codigo'] ?? '';
                                
                                $contratoInfo = '';
                                if (!empty($imovel['contratos'])) {
                                    foreach ($imovel['contratos'] as $c) {
                                        if ($c['CtrTipo'] == 'PRINCIPAL') {
                                            $contratoInfo = $c['CtrCod'] . '-' . $c['CtrDV'];
                                            break;
                                        }
                                    }
                                    if (!$contratoInfo && !empty($imovel['contratos'][0])) {
                                        $contratoInfo = $imovel['contratos'][0]['CtrCod'] . '-' . $imovel['contratos'][0]['CtrDV'];
                                    }
                                }
                                ?>
                                <div class="endereco-item-<?= $index ?>" data-endereco="<?= $index ?>" data-contrato="<?= htmlspecialchars($contratoInfo) ?>" style="margin-bottom:12px;">
                                    <input type="radio" name="endereco_selecionado" value="<?= $index ?>" id="end-<?= $index ?>" style="position:absolute;opacity:0;" <?= $index == 0 ? 'checked' : '' ?>>
                                    <label for="end-<?= $index ?>" style="display:block;border:2px solid <?= $index == 0 ? '#10b981' : '#d1d5db' ?>;background:<?= $index == 0 ? '#ecfdf5' : '#fff' ?>;border-radius:8px;padding:16px;cursor:pointer;">
                                        <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                                            <div style="flex:1;padding-right:32px;">
                                                <div style="background:#dbeafe;color:#1e40af;padding:4px 8px;border-radius:4px;font-size:11px;display:inline-block;margin-bottom:8px;font-weight:500;">
                                                    Imóvel Contratual
                                                </div>
                                                <div style="font-weight:600;font-size:14px;color:#111827;margin-bottom:4px;">
                                                    <?= htmlspecialchars($endereco . ', ' . $numero) ?>
                                                </div>
                                            </div>
                                            <div style="width:24px;height:24px;border-radius:50%;background:<?= $index == 0 ? '#10b981' : '#fff' ?>;border:2px solid <?= $index == 0 ? '#10b981' : '#d1d5db' ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                                <i class="fas fa-check" style="color:<?= $index == 0 ? '#fff' : 'transparent' ?>;font-size:10px;"></i>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-12">
                                <div class="mb-4">
                                    <i class="fas fa-home text-5xl text-gray-300"></i>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">
                                    Nenhum imóvel encontrado
                                </h3>
                                <p class="text-sm text-gray-600 mb-4">
                                    Não foi possível carregar seus imóveis. Isso pode ocorrer por:
                                </p>
                                <ul class="text-sm text-gray-600 text-left inline-block mb-6">
                                    <li class="mb-2">• Você não possui imóveis cadastrados no sistema</li>
                                    <li class="mb-2">• Erro de conexão com a imobiliária</li>
                                    <li class="mb-2">• Sessão expirada</li>
                                </ul>
                                <div class="flex justify-center space-x-3">
                                    <a href="<?= url($locatario['instancia'] . '/dashboard') ?>" 
                                       class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                                        <i class="fas fa-arrow-left mr-2"></i>
                                        Voltar ao Dashboard
                                    </a>
                                    <button onclick="location.reload()" 
                                            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                        <i class="fas fa-sync-alt mr-2"></i>
                                        Tentar Novamente
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Finalidade da Locação -->
                <div>
                    <h3 class="text-sm font-medium text-gray-700 mb-3">Finalidade da Locação</h3>
                    <select name="finalidade_locacao" id="finalidade_locacao" required 
                            class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 text-sm">
                        <option value="RESIDENCIAL" selected>Residencial</option>
                        <option value="COMERCIAL">Comercial</option>
                    </select>
                </div>
                
                <!-- Tipo de Imóvel -->
                <div id="tipo_imovel_container">
                    <h3 class="text-sm font-medium text-gray-700 mb-3">Tipo de Imóvel</h3>
                    <div class="flex space-x-4">
                        <label class="flex items-center">
                            <input type="radio" name="tipo_imovel" value="CASA" checked 
                                   class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300">
                            <span class="ml-2 text-sm text-gray-700">
                                <i class="fas fa-home mr-1"></i>
                                Casa
                            </span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="tipo_imovel" value="APARTAMENTO" 
                                   class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300">
                            <span class="ml-2 text-sm text-gray-700">
                                <i class="fas fa-building mr-1"></i>
                                Apartamento
                            </span>
                    </label>
                    </div>
                </div>
                
                <!-- Navigation -->
                <div class="flex justify-between pt-6">
                    <a href="<?= url($locatario['instancia'] . '/dashboard') ?>" 
                       class="px-6 py-3 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                        Voltar
                    </a>
                    <button type="submit" id="btn-continuar-etapa1"
                            class="px-6 py-3 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors">
                        Continuar
                    </button>
                </div>
            </form>
    </div>
    
    <?php elseif ($etapaAtual == 2): ?>
        <!-- ETAPA 2: SERVIÇO -->
        <?php
        $finalidadeLocacao = $finalidade_locacao ?? $_SESSION['nova_solicitacao']['finalidade_locacao'] ?? 'RESIDENCIAL';
        $finalidadeTexto = $finalidadeLocacao === 'RESIDENCIAL' ? 'Residencial' : 'Comercial';
        $tipoImovel = $nova_solicitacao['tipo_imovel'] ?? 'RESIDENCIAL';
        $tipoTexto = $tipoImovel === 'RESIDENCIAL' ? 'Residencial' : 'Comercial';
        ?>
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">
                <i class="fas fa-cog mr-2 text-green-600"></i>
                Serviço Necessário
            </h2>
            <p class="text-sm text-gray-600 mt-1">Escolha a categoria do serviço que melhor representa sua necessidade</p>
        </div>
        
        <div class="p-6">
            <form method="POST" action="<?= url($locatario['instancia'] . '/nova-solicitacao/etapa/2') ?>" class="space-y-6">
                <?= \App\Core\View::csrfField() ?>
                
                <!-- Categorias -->
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-medium text-gray-700">
                        Categoria do Serviço
                        <?php if (!empty($tipoImovel) && $etapaAtual >= 2): ?>
                            <span class="text-xs text-gray-500 font-normal">
                                (Mostrando categorias para <?= strtolower($tipoTexto) ?>)
                            </span>
                        <?php endif; ?>
                    </h3>
                    </div>
                    <div class="space-y-3">
                        <?php if (!empty($categorias)): ?>
                            <?php foreach ($categorias as $categoriaPai): ?>
                                <?php 
                                    // Verificar se a categoria pai tem filhas
                                    $temFilhas = !empty($categoriaPai['filhas']) && count($categoriaPai['filhas']) > 0;
                                ?>
                                
                                <?php if ($temFilhas): ?>
                                    <?php 
                                    // Verificar se é "Manutenção e Prevenção" ou "Manutenção e Instalação"
                                    $isManutencaoPrevencao = stripos($categoriaPai['nome'], 'Manutenção') !== false && 
                                                             (stripos($categoriaPai['nome'], 'Prevenção') !== false || 
                                                              stripos($categoriaPai['nome'], 'Instalação') !== false);
                                    ?>
                                    
                                    <?php if ($isManutencaoPrevencao && count($categoriaPai['filhas']) <= 3): ?>
                                        <!-- Categoria Pai "Manutenção e Prevenção" - Dropdown com Seleção de Subcategorias -->
                                        <div class="categoria-pai-container" data-categoria-pai-id="<?= $categoriaPai['id'] ?>" data-tipo-selecao-multipla-subcategorias="true">
                                            <!-- Card da categoria pai -->
                                            <div class="w-full flex items-center gap-3 border-2 border-gray-200 rounded-lg p-4 cursor-pointer hover:border-gray-300 hover:bg-gray-50 transition-colors">
                                                <i class="<?= $categoriaPai['icone'] ?? 'fas fa-cog' ?> text-xl text-gray-600 flex-shrink-0"></i>
                                                <span class="text-sm font-medium text-gray-900 flex-1 text-left"><?= htmlspecialchars($categoriaPai['nome']) ?></span>
                                                <?php if (!empty($categoriaPai['condicoes_gerais'])): ?>
                                                    <button type="button" 
                                                            class="btn-condicoes-gerais text-blue-600 hover:text-blue-800 transition-colors flex-shrink-0"
                                                            data-nome="<?= htmlspecialchars($categoriaPai['nome'], ENT_QUOTES) ?>"
                                                            data-condicoes="<?= base64_encode(mb_convert_encoding($categoriaPai['condicoes_gerais'], 'UTF-8', 'UTF-8')) ?>"
                                                            title="Ver condições gerais">
                                                        <i class="fas fa-info-circle text-sm"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button type="button" 
                                                        class="btn-toggle-categoria-pai text-gray-400 hover:text-gray-600 transition-colors flex-shrink-0"
                                                        data-categoria-pai="<?= $categoriaPai['id'] ?>"
                                                        onclick="toggleCategoriaPaiManual(<?= $categoriaPai['id'] ?>)"
                                                        title="Expandir/Recolher">
                                                    <i class="fas fa-chevron-down text-sm transition-transform" id="chevron-toggle-pai-<?= $categoriaPai['id'] ?>"></i>
                                                </button>
                                            </div>
                                            
                                            <!-- Categorias Filhas (ocultas por padrão) -->
                                            <div class="categoria-filhas-container hidden mt-0 border-x-2 border-b-2 border-gray-200 rounded-b-lg bg-gray-50" id="filhas-<?= $categoriaPai['id'] ?>" style="display: none;">
                                                <div class="px-4 pt-3 pb-4 space-y-3">
                                                    <p class="text-xs text-gray-500 mb-3">Selecione até 3 subcategorias: <span id="contador-subcategorias-<?= $categoriaPai['id'] ?>">0</span>/3</p>
                                                    <?php 
                                                    // Buscar subcategorias já selecionadas
                                                    $subcategoriasSelecionadas = [];
                                                    if (!empty($nova_solicitacao['categorias_selecionadas']) && is_array($nova_solicitacao['categorias_selecionadas'])) {
                                                        foreach ($nova_solicitacao['categorias_selecionadas'] as $cat) {
                                                            if (!empty($cat['subcategoria_id'])) {
                                                                $subcategoriasSelecionadas[] = (int)$cat['subcategoria_id'];
                                                            }
                                                        }
                                                    }
                                                    ?>
                                                    <?php foreach ($categoriaPai['filhas'] as $categoriaFilha): ?>
                                                    <!-- Cada categoria filha como card expansível -->
                                                    <div class="categoria-container" data-categoria-id="<?= $categoriaFilha['id'] ?>">
                                                        <!-- Card da Categoria Filha -->
                                                        <div class="w-full flex items-center gap-3 border-2 border-gray-200 rounded-lg p-4 bg-white cursor-pointer hover:border-gray-300 hover:bg-gray-50 transition-colors">
                                                            <i class="<?= $categoriaFilha['icone'] ?? 'fas fa-cog' ?> text-lg text-gray-600 flex-shrink-0"></i>
                                                            <span class="text-sm font-medium text-gray-900 flex-1 text-left"><?= htmlspecialchars($categoriaFilha['nome']) ?></span>
                                                            <?php if (!empty($categoriaFilha['condicoes_gerais'])): ?>
                                                                <button type="button" 
                                                                        class="btn-condicoes-gerais text-blue-600 hover:text-blue-800 transition-colors flex-shrink-0"
                                                                        data-nome="<?= htmlspecialchars($categoriaFilha['nome'], ENT_QUOTES) ?>"
                                                                        data-condicoes="<?= base64_encode(mb_convert_encoding($categoriaFilha['condicoes_gerais'], 'UTF-8', 'UTF-8')) ?>"
                                                                        title="Ver condições gerais">
                                                                    <i class="fas fa-info-circle text-sm"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                            <button type="button" 
                                                                    class="btn-toggle-categoria text-gray-400 hover:text-gray-600 transition-colors flex-shrink-0"
                                                                    data-categoria="<?= $categoriaFilha['id'] ?>"
                                                                    onclick="toggleCategoriaManual(<?= $categoriaFilha['id'] ?>)"
                                                                    title="Expandir/Recolher">
                                                                <i class="fas fa-chevron-down text-sm transition-transform" id="chevron-toggle-<?= $categoriaFilha['id'] ?>"></i>
                                                            </button>
                                                        </div>
                                                        
                                                        <!-- Subcategorias (ocultas por padrão) -->
                                                        <div class="categoria-descricao-container hidden border-x-2 border-b-2 border-gray-200 rounded-b-lg bg-white" id="descricao-cat-<?= $categoriaFilha['id'] ?>">
                                                            <!-- Subcategorias com checkboxes -->
                                                            <div class="px-4 pb-4 bg-gray-50">
                                                                <div class="space-y-3 pt-3">
                                                                    <?php if (!empty($categoriaFilha['subcategorias'])): ?>
                                                                        <?php foreach ($categoriaFilha['subcategorias'] as $subcategoria): ?>
                                                                            <div class="subcategoria-item-wrapper" 
                                                                                 data-categoria-filha-id="<?= $categoriaFilha['id'] ?>"
                                                                                 data-subcategoria-id="<?= $subcategoria['id'] ?>">
                                                                                <div class="border border-gray-200 rounded-lg p-3 bg-white subcategoria-card-manutencao cursor-pointer hover:border-blue-300 transition-colors <?= in_array((int)$subcategoria['id'], $subcategoriasSelecionadas) ? 'border-blue-500 bg-blue-50' : '' ?> <?= (!empty($subcategoria['is_emergencial']) && ($subcategoria['is_emergencial'] == 1 || $subcategoria['is_emergencial'] === true)) ? 'border-red-300 bg-red-50' : '' ?>">
                                                                                    <div class="flex items-start justify-between">
                                                                                        <div class="flex-1">
                                                                                            <div class="flex items-center gap-1 mb-1">
                                                                                                <i class="<?= $categoriaFilha['icone'] ?? 'fas fa-cog' ?> text-sm text-gray-600"></i>
                                                                                                <h5 class="text-sm font-medium text-gray-900">
                                                                                                    <?= htmlspecialchars($subcategoria['nome']) ?>
                                                                                                </h5>
                                                                                                <?php if (!empty($subcategoria['is_emergencial']) && ($subcategoria['is_emergencial'] == 1 || $subcategoria['is_emergencial'] === true)): ?>
                                                                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-200 ml-2">
                                                                                                        <i class="fas fa-exclamation-triangle mr-1"></i>
                                                                                                        Emergencial
                                                                                                    </span>
                                                                                                <?php endif; ?>
                                                                                            </div>
                                                                                            <?php if (!empty($subcategoria['descricao'])): ?>
                                                                                                <p class="text-xs text-gray-600 mt-1">
                                                                                                    <?= htmlspecialchars($subcategoria['descricao']) ?>
                                                                                                </p>
                                                                                            <?php endif; ?>
                                                                                        </div>
                                                                                        <div class="ml-3 flex items-center gap-2 flex-shrink-0">
                                                                                            <?php if (!empty($subcategoria['condicoes_gerais'])): ?>
                                                                                                <button type="button" 
                                                                                                        class="btn-condicoes-gerais text-blue-600 hover:text-blue-800 transition-colors flex-shrink-0"
                                                                                                        data-nome="<?= htmlspecialchars($subcategoria['nome'], ENT_QUOTES) ?>"
                                                                                                        data-condicoes="<?= base64_encode(mb_convert_encoding($subcategoria['condicoes_gerais'], 'UTF-8', 'UTF-8')) ?>"
                                                                                                        title="Ver condições gerais">
                                                                                                    <i class="fas fa-info-circle text-xs"></i>
                                                                                                </button>
                                                                                            <?php endif; ?>
                                                                                            <label class="cursor-pointer">
                                                                                                <input type="checkbox" 
                                                                                                       name="subcategorias_selecionadas[]" 
                                                                                                       value="<?= $subcategoria['id'] ?>"
                                                                                                       class="sr-only subcategoria-checkbox-manutencao"
                                                                                                       data-categoria-filha="<?= $categoriaFilha['id'] ?>"
                                                                                                       data-subcategoria="<?= $subcategoria['id'] ?>"
                                                                                                       data-categoria-pai="<?= $categoriaPai['id'] ?>"
                                                                                                       id="checkbox-<?= $categoriaPai['id'] ?>-<?= $categoriaFilha['id'] ?>-<?= $subcategoria['id'] ?>"
                                                                                                       <?= in_array((int)$subcategoria['id'], $subcategoriasSelecionadas) ? 'checked' : '' ?>>
                                                                                                <div class="w-5 h-5 border-2 <?= in_array((int)$subcategoria['id'], $subcategoriasSelecionadas) ? 'border-blue-500 bg-blue-500' : 'border-gray-300' ?> rounded flex items-center justify-center subcategoria-check-manutencao">
                                                                                                    <?php if (in_array((int)$subcategoria['id'], $subcategoriasSelecionadas)): ?>
                                                                                                        <i class="fas fa-check text-white text-xs"></i>
                                                                                                    <?php endif; ?>
                                                                                                </div>
                                                                                            </label>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        <?php endforeach; ?>
                                                                    <?php else: ?>
                                                                        <p class="text-sm text-gray-500">Nenhum serviço disponível.</p>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <!-- Categoria Pai COM Filhas (Separadora Expansível) - Comportamento padrão -->
                                    <div class="categoria-pai-container" data-categoria-pai-id="<?= $categoriaPai['id'] ?>">
                                        <!-- Card da categoria pai -->
                                        <div class="w-full flex items-center gap-3 border-2 border-gray-200 rounded-lg p-4 cursor-pointer hover:border-gray-300 hover:bg-gray-50 transition-colors">
                                            <i class="<?= $categoriaPai['icone'] ?? 'fas fa-cog' ?> text-xl text-gray-600 flex-shrink-0"></i>
                                            <span class="text-sm font-medium text-gray-900 flex-1 text-left"><?= htmlspecialchars($categoriaPai['nome']) ?></span>
                                            <?php if (!empty($categoriaPai['condicoes_gerais'])): ?>
                                                <button type="button" 
                                                        class="btn-condicoes-gerais text-blue-600 hover:text-blue-800 transition-colors flex-shrink-0"
                                                        data-nome="<?= htmlspecialchars($categoriaPai['nome'], ENT_QUOTES) ?>"
                                                        data-condicoes="<?= base64_encode(mb_convert_encoding($categoriaPai['condicoes_gerais'], 'UTF-8', 'UTF-8')) ?>"
                                                        title="Ver condições gerais">
                                                    <i class="fas fa-info-circle text-sm"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button type="button" 
                                                    class="btn-toggle-categoria-pai text-gray-400 hover:text-gray-600 transition-colors flex-shrink-0"
                                                    data-categoria-pai="<?= $categoriaPai['id'] ?>"
                                                    onclick="toggleCategoriaPaiManual(<?= $categoriaPai['id'] ?>)"
                                                    title="Expandir/Recolher">
                                                <i class="fas fa-chevron-down text-sm transition-transform" id="chevron-toggle-pai-<?= $categoriaPai['id'] ?>"></i>
                                            </button>
                                        </div>
                                        
                                        <!-- Categorias Filhas (ocultas por padrão) -->
                                        <div class="categoria-filhas-container hidden mt-0 border-x-2 border-b-2 border-gray-200 rounded-b-lg bg-gray-50" id="filhas-<?= $categoriaPai['id'] ?>" style="display: none;">
                                            <div class="px-4 pt-3 pb-4 space-y-3">
                                                <?php foreach ($categoriaPai['filhas'] as $categoriaFilha): ?>
                                                    <!-- Cada categoria filha como card expansível -->
                                                    <div class="categoria-container" data-categoria-id="<?= $categoriaFilha['id'] ?>">
                                                        <!-- Card da Categoria Filha -->
                                                        <div class="w-full flex items-center gap-3 border-2 border-gray-200 rounded-lg p-4 bg-white cursor-pointer hover:border-gray-300 hover:bg-gray-50 transition-colors">
                                                            <i class="<?= $categoriaFilha['icone'] ?? 'fas fa-cog' ?> text-lg text-gray-600 flex-shrink-0"></i>
                                                            <span class="text-sm font-medium text-gray-900 flex-1 text-left"><?= htmlspecialchars($categoriaFilha['nome']) ?></span>
                                                            <?php if (!empty($categoriaFilha['condicoes_gerais'])): ?>
                                                                <button type="button" 
                                                                        class="btn-condicoes-gerais text-blue-600 hover:text-blue-800 transition-colors flex-shrink-0"
                                                                        data-nome="<?= htmlspecialchars($categoriaFilha['nome'], ENT_QUOTES) ?>"
                                                                        data-condicoes="<?= base64_encode(mb_convert_encoding($categoriaFilha['condicoes_gerais'], 'UTF-8', 'UTF-8')) ?>"
                                                                        title="Ver condições gerais">
                                                                    <i class="fas fa-info-circle text-sm"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                            <button type="button" 
                                                                    class="btn-toggle-categoria text-gray-400 hover:text-gray-600 transition-colors flex-shrink-0"
                                                                    data-categoria="<?= $categoriaFilha['id'] ?>"
                                                                    onclick="toggleCategoriaManual(<?= $categoriaFilha['id'] ?>)"
                                                                    title="Expandir/Recolher">
                                                                <i class="fas fa-chevron-down text-sm transition-transform" id="chevron-toggle-<?= $categoriaFilha['id'] ?>"></i>
                                                            </button>
                                                        </div>
                                                        
                                                        <!-- Subcategorias (ocultas por padrão) -->
                                                        <div class="categoria-descricao-container hidden border-x-2 border-b-2 border-gray-200 rounded-b-lg bg-white" id="descricao-cat-<?= $categoriaFilha['id'] ?>">
                                                            <!-- Subcategorias -->
                                                            <div class="px-4 pb-4 bg-gray-50">
                                                                <div class="space-y-3 pt-3">
                                                                    <?php if (!empty($categoriaFilha['subcategorias'])): ?>
                                                                        <?php foreach ($categoriaFilha['subcategorias'] as $subcategoria): ?>
                                                                            <label class="relative block cursor-pointer">
                                                                                <input type="radio" name="categoria_id" value="<?= $categoriaFilha['id'] ?>" 
                                                                                       class="sr-only categoria-radio" data-categoria="<?= $categoriaFilha['id'] ?>"
                                                                                       <?= ($nova_solicitacao['categoria_id'] ?? '') == $categoriaFilha['id'] ? 'checked' : '' ?>>
                                                                                <input type="radio" name="subcategoria_id" value="<?= $subcategoria['id'] ?>" 
                                                                                       class="sr-only subcategoria-radio"
                                                                                       <?= ($nova_solicitacao['subcategoria_id'] ?? '') == $subcategoria['id'] ? 'checked' : '' ?>>
                                                                                <div class="border border-gray-200 rounded-lg p-3 bg-white hover:border-blue-300 transition-colors subcategoria-card <?= (!empty($subcategoria['is_emergencial']) && ($subcategoria['is_emergencial'] == 1 || $subcategoria['is_emergencial'] === true)) ? 'border-red-300 bg-red-50' : '' ?>">
                                                                                    <div class="flex items-start justify-between">
                                                                                        <div class="flex-1">
                                                                                            <div class="flex items-center gap-2 mb-1">
                                                                                                <i class="<?= $categoriaFilha['icone'] ?? 'fas fa-cog' ?> text-sm text-gray-600"></i>
                                                                                                <h5 class="text-sm font-medium text-gray-900">
                                                                                                    <?= htmlspecialchars($subcategoria['nome']) ?>
                                                                                                </h5>
                                                                                                <?php if (!empty($subcategoria['condicoes_gerais'])): ?>
                                                                                                    <button type="button" 
                                                                                                            class="btn-condicoes-gerais text-blue-600 hover:text-blue-800 transition-colors flex-shrink-0"
                                                                                                            data-nome="<?= htmlspecialchars($subcategoria['nome'], ENT_QUOTES) ?>"
                                                                                                            data-condicoes="<?= base64_encode(mb_convert_encoding($subcategoria['condicoes_gerais'], 'UTF-8', 'UTF-8')) ?>"
                                                                                                            title="Ver condições gerais"
                                                                                                            onclick="event.stopPropagation();">
                                                                                                        <i class="fas fa-info-circle text-xs"></i>
                                                                                                    </button>
                                                                                                <?php endif; ?>
                                                                                                <?php if (!empty($subcategoria['is_emergencial']) && ($subcategoria['is_emergencial'] == 1 || $subcategoria['is_emergencial'] === true)): ?>
                                                                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-200">
                                                                                                        <i class="fas fa-exclamation-triangle mr-1"></i>
                                                                                                        Emergencial
                                                                                                    </span>
                                                                                                <?php endif; ?>
                                                                                            </div>
                                                                                            <?php if (!empty($subcategoria['descricao'])): ?>
                                                                                                <p class="text-xs text-gray-600 mt-1">
                                                                                                    <?= htmlspecialchars($subcategoria['descricao']) ?>
                                                                                                </p>
                                                                                            <?php endif; ?>
                                                                                        </div>
                                                                                        <div class="ml-3 flex-shrink-0">
                                                                                            <div class="w-5 h-5 border-2 border-gray-300 rounded-full subcategoria-check"></div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </label>
                                                                        <?php endforeach; ?>
                                                                    <?php else: ?>
                                                                        <p class="text-sm text-gray-500">Nenhum serviço disponível.</p>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <!-- Categoria SEM Filhas (Categoria Normal) -->
                                    <div class="categoria-container" data-categoria-id="<?= $categoriaPai['id'] ?>">
                                        <!-- Card da Categoria -->
                                        <div class="w-full flex items-center gap-3 border-2 border-gray-200 rounded-lg p-4 cursor-pointer hover:border-gray-300 hover:bg-gray-50 transition-colors">
                                            <i class="<?= $categoriaPai['icone'] ?? 'fas fa-cog' ?> text-xl text-gray-600 flex-shrink-0"></i>
                                            <span class="text-sm font-medium text-gray-900 flex-1 text-left"><?= htmlspecialchars($categoriaPai['nome']) ?></span>
                                            <?php if (!empty($categoriaPai['condicoes_gerais'])): ?>
                                                <button type="button" 
                                                        class="btn-condicoes-gerais text-blue-600 hover:text-blue-800 transition-colors flex-shrink-0"
                                                        data-nome="<?= htmlspecialchars($categoriaPai['nome'], ENT_QUOTES) ?>"
                                                        data-condicoes="<?= base64_encode(mb_convert_encoding($categoriaPai['condicoes_gerais'], 'UTF-8', 'UTF-8')) ?>"
                                                        title="Ver condições gerais">
                                                    <i class="fas fa-info-circle text-sm"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button type="button" 
                                                    class="btn-toggle-categoria text-gray-400 hover:text-gray-600 transition-colors flex-shrink-0"
                                                    data-categoria="<?= $categoriaPai['id'] ?>"
                                                    onclick="toggleCategoriaManual(<?= $categoriaPai['id'] ?>)"
                                                    title="Expandir/Recolher">
                                                <i class="fas fa-chevron-down text-sm transition-transform" id="chevron-toggle-<?= $categoriaPai['id'] ?>"></i>
                                            </button>
                                        </div>
                                        
                                        <!-- Subcategorias (ocultas por padrão) -->
                                        <div class="categoria-descricao-container hidden border-x-2 border-b-2 border-gray-200 rounded-b-lg bg-gray-50" id="descricao-cat-<?= $categoriaPai['id'] ?>">
                                            <!-- Subcategorias -->
                                            <div class="px-4 pb-4">
                                                <div class="space-y-3 pt-3">
                                                    <?php if (!empty($categoriaPai['subcategorias'])): ?>
                                                        <?php foreach ($categoriaPai['subcategorias'] as $subcategoria): ?>
                                                            <label class="relative block cursor-pointer">
                                                                <input type="radio" name="categoria_id" value="<?= $categoriaPai['id'] ?>" 
                                                                       class="sr-only categoria-radio" data-categoria="<?= $categoriaPai['id'] ?>"
                                                                       <?= ($nova_solicitacao['categoria_id'] ?? '') == $categoriaPai['id'] ? 'checked' : '' ?>>
                                                                <input type="radio" name="subcategoria_id" value="<?= $subcategoria['id'] ?>" 
                                                                       class="sr-only subcategoria-radio"
                                                                       <?= ($nova_solicitacao['subcategoria_id'] ?? '') == $subcategoria['id'] ? 'checked' : '' ?>>
                                                                <div class="border border-gray-200 rounded-lg p-3 bg-white hover:border-blue-300 transition-colors subcategoria-card <?= (!empty($subcategoria['is_emergencial']) && ($subcategoria['is_emergencial'] == 1 || $subcategoria['is_emergencial'] === true)) ? 'border-red-300 bg-red-50' : '' ?>">
                                                                    <div class="flex items-start justify-between">
                                                                        <div class="flex-1">
                                                                            <div class="flex items-center gap-2 mb-1">
                                                                                <i class="<?= $categoriaPai['icone'] ?? 'fas fa-cog' ?> text-sm text-gray-600"></i>
                                                                                <h5 class="text-sm font-medium text-gray-900">
                                                                                    <?= htmlspecialchars($subcategoria['nome']) ?>
                                                                                </h5>
                                                                                <?php if (!empty($subcategoria['is_emergencial']) && ($subcategoria['is_emergencial'] == 1 || $subcategoria['is_emergencial'] === true)): ?>
                                                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-200">
                                                                                        <i class="fas fa-exclamation-triangle mr-1"></i>
                                                                                        Emergencial
                                                                                    </span>
                                                                                <?php endif; ?>
                                                                            </div>
                                                                            <?php if (!empty($subcategoria['descricao'])): ?>
                                                                                <p class="text-xs text-gray-600 mt-1">
                                                                                    <?= htmlspecialchars($subcategoria['descricao']) ?>
                                                                                </p>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                        <div class="ml-3 flex-shrink-0">
                                                                            <div class="w-5 h-5 border-2 border-gray-300 rounded-full subcategoria-check"></div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </label>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <p class="text-sm text-gray-500">Nenhum serviço disponível.</p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-center py-8 text-gray-500">Nenhuma categoria disponível</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Navigation -->
                <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 sm:justify-between pt-6">
                    <div class="flex flex-col sm:flex-row gap-3 flex-1">
                        <a href="<?= url($locatario['instancia'] . '/nova-solicitacao/etapa/1') ?>" 
                           class="w-full sm:w-auto px-6 py-3 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors text-center sm:text-left">
                            <i class="fas fa-arrow-left mr-2"></i>Voltar
                        </a>
                        <a href="/condicoes-gerais?return=<?= urlencode(url($locatario['instancia'] . '/nova-solicitacao/etapa/2')) ?>" target="_blank" 
                           class="w-full sm:w-auto px-6 py-3 border border-blue-300 bg-white text-blue-600 font-medium rounded-lg hover:bg-blue-50 transition-colors text-center">
                            <i class="fas fa-info-circle mr-2"></i>Condições Gerais
                        </a>
                    </div>
                    <button type="submit"
                            class="w-full sm:w-auto px-6 py-3 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors">
                        Continuar <i class="fas fa-arrow-right ml-2"></i>
                    </button>
                </div>
            </form>
        </div>
    
    <?php elseif ($etapaAtual == 3): ?>
        <!-- ETAPA 3: DESCRIÇÃO -->
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">
                <i class="fas fa-edit mr-2"></i>
                Descreva o problema
            </h2>
            <p class="text-sm text-gray-500 mt-1">Forneça detalhes sobre o serviço necessário</p>
        </div>
        
        <div class="p-6">
            <form method="POST" action="<?= url($locatario['instancia'] . '/nova-solicitacao/etapa/3') ?>" 
                  enctype="multipart/form-data" class="space-y-6" onsubmit="combinarFotosAntesEnvio(event)">
                <?= \App\Core\View::csrfField() ?>
                
                <!-- Local da Manutenção -->
                <div>
                    <label for="local_manutencao" class="block text-sm font-medium text-gray-700 mb-2">
                        Local do imóvel onde será feito a manutenção
                    </label>
                    <input type="text" id="local_manutencao" name="local_manutencao" 
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 text-sm"
                           placeholder="Ex: Fechadura do Portão da Rua"
                           value="<?= htmlspecialchars($_SESSION['nova_solicitacao']['local_manutencao'] ?? '') ?>">
                </div>
                
                <!-- Descrição do Problema -->
                <div>
                    <label for="descricao_problema" class="block text-sm font-medium text-gray-700 mb-2">
                        Descrição do Problema
                    </label>
                    <textarea id="descricao_problema" name="descricao_problema" rows="6" required
                              class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 text-sm resize-none"
                              placeholder="Descreva detalhadamente o problema que precisa ser resolvido..."><?= htmlspecialchars($_SESSION['nova_solicitacao']['descricao_problema'] ?? '') ?></textarea>
                </div>
                
                <!-- Upload de Fotos -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Fotos (Opcional)
                    </label>
                    <p class="text-sm text-gray-500 mb-3">Adicione fotos para ajudar a entender melhor o problema</p>
                    
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-green-400 transition-colors cursor-pointer" 
                         onclick="abrirModalFoto()">
                        <i class="fas fa-camera text-3xl text-gray-400 mb-2"></i>
                        <p class="text-sm text-gray-600">Clique para adicionar uma foto</p>
                        <p class="text-xs text-gray-400 mt-1">PNG, JPG até 10MB</p>
                </div>
                
                    <input type="file" id="fotos" name="fotos[]" multiple accept="image/*"
                           class="hidden" onchange="previewPhotos(this)">
                    <input type="file" id="fotos-camera" name="fotos[]" multiple accept="image/*" capture="environment"
                           class="hidden" onchange="previewPhotos(this)">
                    
                    <!-- Modal para escolher entre câmera ou arquivos -->
                    <div id="modal-foto" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
                        <div class="bg-white rounded-lg p-6 max-w-sm w-full mx-4">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Adicionar Foto</h3>
                            <div class="space-y-3">
                                <button type="button" onclick="escolherCamera()" 
                                        class="w-full flex items-center justify-center gap-3 px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                    <i class="fas fa-camera text-xl"></i>
                                    <span>Abrir Câmera</span>
                                </button>
                                <button type="button" onclick="escolherArquivo()" 
                                        class="w-full flex items-center justify-center gap-3 px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                    <i class="fas fa-folder-open text-xl"></i>
                                    <span>Carregar Arquivos do Dispositivo</span>
                                </button>
                                <button type="button" onclick="fecharModalFoto()" 
                                        class="w-full px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Preview das fotos -->
                    <div id="fotos-preview-container" class="mt-4 relative">
                        <div id="fotos-preview" class="grid grid-cols-2 md:grid-cols-4 gap-4 hidden">
                        <!-- Fotos serão inseridas aqui via JavaScript -->
                        </div>
                        <!-- Loading overlay -->
                        <div id="fotos-loading" class="hidden absolute inset-0 bg-white bg-opacity-95 z-50 flex items-center justify-center rounded-lg" style="min-height: 200px;">
                            <div class="text-center">
                                <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-4 border-green-600 mb-3"></div>
                                <p class="text-sm text-gray-600 font-medium">Processando fotos...</p>
                            </div>
                        </div>
                </div>
                </div>
                
                <!-- Navigation -->
                <div class="flex justify-between pt-6">
                    <a href="<?= url($locatario['instancia'] . '/nova-solicitacao/etapa/2') ?>" 
                       class="px-6 py-3 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                        Voltar
                    </a>
                    <button type="submit" 
                            class="px-6 py-3 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors">
                        Continuar
                    </button>
                </div>
            </form>
    </div>
    
    <?php elseif ($etapaAtual == 4): ?>
        <!-- ETAPA 4: AGENDAMENTO -->
        <?php
        // Verificar se a subcategoria é emergencial
        $subcategoriaModel = new \App\Models\Subcategoria();
        $subcategoriaId = $_SESSION['nova_solicitacao']['subcategoria_id'] ?? 0;
        $subcategoria = $subcategoriaModel->find($subcategoriaId);
        $isEmergencial = !empty($subcategoria['is_emergencial']);
        
        // Calcular data mínima para agendamento baseado no prazo_minimo
        $dataMinimaAgendamento = null;
        if (!$isEmergencial && $subcategoriaId) {
            $dataMinimaAgendamento = $subcategoriaModel->calcularDataLimiteAgendamento($subcategoriaId);
        }
        
        // Verificar se está fora do horário comercial usando configurações
        $configuracaoModel = new \App\Models\Configuracao();
        $isForaHorario = $configuracaoModel->isForaHorarioComercial();
        
        // Buscar telefone de emergência
        $telefoneEmergenciaModel = new \App\Models\TelefoneEmergencia();
        $telefoneEmergencia = $telefoneEmergenciaModel->getPrincipal();
        ?>
        
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">
                <i class="fas fa-calendar mr-2"></i>
                <?php if ($isEmergencial): ?>
                    <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
                    Atendimento Emergencial
                <?php else: ?>
                    Quando você prefere o atendimento?
                <?php endif; ?>
            </h2>
        </div>
        
        <div class="p-6">
            <!-- Avisos Importantes (não desaparecem mais) -->
            <div class="mb-6 space-y-3 relative z-0">
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-triangle text-yellow-600 mr-3 mt-0.5"></i>
                        <div>
                            <h4 class="text-sm font-medium text-yellow-800">Condomínio</h4>
                            <p class="text-sm text-yellow-700 mt-1">
                                Se o serviço for realizado em apartamento ou condomínio, é obrigatório comunicar previamente a administração ou portaria sobre a visita técnica agendada.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-triangle text-yellow-600 mr-3 mt-0.5"></i>
                        <div>
                            <h4 class="text-sm font-medium text-yellow-800">Responsável no Local</h4>
                            <p class="text-sm text-yellow-700 mt-1">
                                É obrigatória a presença de uma pessoa maior de 18 anos no local durante todo o período de execução do serviço para acompanhar e autorizar os trabalhos.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <form method="POST" action="<?= url($locatario['instancia'] . '/nova-solicitacao/etapa/4') ?>" class="space-y-6 relative z-10">
                <?= \App\Core\View::csrfField() ?>
                <input type="hidden" name="is_emergencial" value="<?= $isEmergencial ? '1' : '0' ?>">
                
                <?php if ($isEmergencial): ?>
                    <!-- Emergencial: Duas opções sempre disponíveis -->
                    <div class="space-y-4">
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <div class="text-sm text-blue-800">
                                    <p class="font-medium mb-2">Escolha como deseja prosseguir:</p>
                                </div>
                            </div>
                        
                        <!-- Opção 1: Atendimento em 120 minutos / Atendimento Emergencial -->
                        <label class="relative block cursor-pointer">
                            <input type="radio" name="tipo_atendimento_emergencial" value="120_minutos" 
                                   class="sr-only tipo-atendimento-radio" id="opcao_120_minutos"
                                   <?= $isForaHorario ? 'checked' : '' ?>>
                            <div class="border-2 <?= $isForaHorario ? 'border-green-500 bg-green-50' : 'border-gray-200 bg-white' ?> rounded-lg p-4 hover:border-green-300 hover:bg-green-50 transition-colors tipo-atendimento-card">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0 mt-1">
                                        <div class="w-5 h-5 border-2 <?= $isForaHorario ? 'border-green-600 bg-green-600 flex items-center justify-center rounded-full' : 'border-gray-300 rounded-full' ?> tipo-atendimento-check">
                                            <?php if ($isForaHorario): ?>
                                                <i class="fas fa-check text-white text-xs"></i>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="ml-3 flex-1">
                                        <h4 class="text-sm font-semibold text-gray-900 mb-1">
                                            <i class="fas fa-bolt text-yellow-500 mr-2"></i>
                                            <?= $isForaHorario ? 'Solicitar atendimento emergencial' : 'Solicitar Atendimento em 120 minutos' ?>
                                        </h4>
                                        <p class="text-xs text-gray-600">
                                            Sua solicitação será processada imediatamente. O atendimento será agendado automaticamente e você receberá retorno em até 120 minutos.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </label>
                        
                        <!-- Box de Atendimento Emergencial (aparece quando "120 minutos" está selecionado) -->
                        <div id="box-atendimento-emergencial" class="<?= $isForaHorario ? '' : 'hidden' ?> mt-3">
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                <div class="flex items-start">
                                    <i class="fas fa-exclamation-triangle text-red-600 mr-3 mt-0.5"></i>
                                    <div>
                                        <h4 class="text-sm font-medium text-red-800">Atendimento Emergencial</h4>
                                        <p class="text-sm text-red-700 mt-1">
                                            Esta é uma solicitação de emergência. O atendimento será processado imediatamente sem necessidade de agendamento. Você receberá retorno em até 120 minutos.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Opção 2: Agendar (sempre disponível) -->
                            <label class="relative block cursor-pointer">
                                <input type="radio" name="tipo_atendimento_emergencial" value="agendar" 
                                       class="sr-only tipo-atendimento-radio" id="opcao_agendar">
                                <div class="border-2 border-gray-200 rounded-lg p-4 bg-white hover:border-blue-300 hover:bg-blue-50 transition-colors tipo-atendimento-card">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0 mt-1">
                                            <div class="w-5 h-5 border-2 border-gray-300 rounded-full tipo-atendimento-check"></div>
                                        </div>
                                        <div class="ml-3 flex-1">
                                            <h4 class="text-sm font-semibold text-gray-900 mb-1">
                                                <i class="fas fa-calendar-alt text-blue-500 mr-2"></i>
                                                Agendar
                                            </h4>
                                            <p class="text-xs text-gray-600">
                                                Se preferir, você pode agendar um horário específico para o atendimento.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </label>
                        
                        <!-- Seção de Agendamento (oculta por padrão, aparece quando selecionar "Agendar") -->
                        <div id="secao-agendamento-emergencial" class="hidden space-y-4 pt-4 border-t border-gray-200">
                            <!-- Instruções -->
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <div class="text-sm text-blue-800">
                                    <p class="font-medium mb-2">Selecione até 3 datas e horários preferenciais</p>
                                    <p class="mb-2">Após sua escolha, o prestador verificará a disponibilidade. Caso algum dos horários não esteja livre, poderão ser sugeridas novas opções.</p>
                                    <p>Você receberá uma notificação confirmando a data e o horário final definidos (via WhatsApp e aplicativo).</p>
                                </div>
                            </div>
                            
                            <!-- Seleção de Data -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-3">
                                    Selecione uma Data
                                </label>
                                <!-- Botão para abrir modal -->
                                <button type="button" id="btn-selecionar-data-emergencial" 
                                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg shadow-sm text-left font-medium text-gray-700 bg-white hover:border-green-500 hover:bg-green-50 transition-colors flex items-center justify-between">
                                    <span class="flex items-center">
                                        <i class="fas fa-calendar-alt text-gray-400 mr-3"></i>
                                        <span id="texto-botao-data-emergencial">Selecione uma Data</span>
                                    </span>
                                    <i class="fas fa-chevron-down text-gray-400"></i>
                                </button>
                                <!-- Input hidden para armazenar o valor -->
                                <input type="hidden" id="data_selecionada_emergencial" name="data_selecionada">
                        <!-- Modal para selecionar data -->
                        <div id="modal-data-emergencial" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
                            <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full mx-4">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-semibold text-gray-900">Selecione uma Data</h3>
                                    <button type="button" id="fechar-modal-data-emergencial" class="text-gray-400 hover:text-gray-600">
                                        <i class="fas fa-times text-xl"></i>
                                    </button>
                                    </div>
                                <div class="mb-4" id="calendario-container-data-emergencial">
                                    <!-- Calendário será gerado aqui via JavaScript -->
                                </div>
                            </div>
                                </div>
                                <div class="mt-2 flex items-center text-xs text-gray-500">
                                    <i class="fas fa-info-circle mr-1.5"></i>
                                    <span>Atendimentos disponíveis apenas em dias úteis (segunda a sexta-feira)</span>
                                </div>
                            </div>
                            
                            <!-- Seleção de Horário -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-3">
                                    Selecione um Horário
                                </label>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                    <label class="relative">
                                        <input type="radio" name="horario_selecionado_emergencial" value="08:00-11:00" class="sr-only horario-radio-emergencial">
                                        <div class="border-2 border-gray-200 rounded-lg p-3 text-center cursor-pointer hover:border-green-300 transition-colors horario-card-emergencial">
                                            <div class="text-sm font-medium text-gray-900">08h00 às 11h00</div>
                                        </div>
                                    </label>
                                    
                                    <label class="relative">
                                        <input type="radio" name="horario_selecionado_emergencial" value="11:00-14:00" class="sr-only horario-radio-emergencial">
                                        <div class="border-2 border-gray-200 rounded-lg p-3 text-center cursor-pointer hover:border-green-300 transition-colors horario-card-emergencial">
                                            <div class="text-sm font-medium text-gray-900">11h00 às 14h00</div>
                                        </div>
                                    </label>
                                    
                                    <label class="relative">
                                        <input type="radio" name="horario_selecionado_emergencial" value="14:00-17:00" class="sr-only horario-radio-emergencial">
                                        <div class="border-2 border-gray-200 rounded-lg p-3 text-center cursor-pointer hover:border-green-300 transition-colors horario-card-emergencial">
                                            <div class="text-sm font-medium text-gray-900">14h00 às 17h00</div>
                                        </div>
                                    </label>
                                    
                                    <label class="relative">
                                        <input type="radio" name="horario_selecionado_emergencial" value="17:00-20:00" class="sr-only horario-radio-emergencial">
                                        <div class="border-2 border-gray-200 rounded-lg p-3 text-center cursor-pointer hover:border-green-300 transition-colors horario-card-emergencial">
                                            <div class="text-sm font-medium text-gray-900">17h00 às 20h00</div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Horários Selecionados -->
                            <div id="horarios-selecionados-emergencial" class="hidden">
                                <h4 class="text-sm font-medium text-gray-700 mb-3">
                                    Horários Selecionados (<span id="contador-horarios-emergencial">0</span>/3)
                                </h4>
                                <div id="lista-horarios-emergencial" class="space-y-2">
                                    <!-- Horários serão inseridos aqui via JavaScript -->
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Normal: Mostrar opções de horário -->
                    <!-- Instruções -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 relative z-0">
                        <div class="text-sm text-blue-800">
                            <p class="font-medium mb-2">Selecione até 3 datas e horários preferenciais</p>
                            <p class="mb-2">Após sua escolha, o prestador verificará a disponibilidade. Caso algum dos horários não esteja livre, poderão ser sugeridas novas opções.</p>
                            <p>Você receberá uma notificação confirmando a data e o horário final definidos (via WhatsApp e aplicativo).</p>
                        </div>
                    </div>
                    
                    <!-- Seleção de Data -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            Selecione uma Data
                        </label>
                        <!-- Botão para abrir modal -->
                        <button type="button" id="btn-selecionar-data" 
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg shadow-sm text-left font-medium text-gray-700 bg-white hover:border-green-500 hover:bg-green-50 transition-colors flex items-center justify-between">
                            <span class="flex items-center">
                                <i class="fas fa-calendar-alt text-gray-400 mr-3"></i>
                                <span id="texto-botao-data">Selecione uma Data</span>
                            </span>
                            <i class="fas fa-chevron-down text-gray-400"></i>
                        </button>
                        <!-- Input hidden para armazenar o valor -->
                        <input type="hidden" id="data_selecionada" name="data_selecionada" 
                                   data-min-date="<?= $dataMinimaAgendamento ? $dataMinimaAgendamento->format('Y-m-d') : '' ?>">
                        <!-- Modal para selecionar data -->
                        <div id="modal-data" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
                            <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full mx-4">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-semibold text-gray-900">Selecione uma Data</h3>
                                    <button type="button" id="fechar-modal-data" class="text-gray-400 hover:text-gray-600">
                                        <i class="fas fa-times text-xl"></i>
                                    </button>
                                </div>
                                <div class="mb-4" id="calendario-container-data">
                                    <!-- Calendário será gerado aqui via JavaScript -->
                                </div>
                            </div>
                        </div>
                        <div class="mt-2 flex items-center text-xs text-gray-500">
                            <i class="fas fa-info-circle mr-1.5"></i>
                            <span>
                                Atendimentos disponíveis apenas em dias úteis (segunda a sexta-feira)
                                <?php if ($dataMinimaAgendamento && !$isEmergencial): ?>
                                    <?php
                                    $prazoMinimo = $subcategoria['prazo_minimo'] ?? 1;
                                    $dataMinimaFormatada = $dataMinimaAgendamento->format('d/m/Y');
                                    ?>
                                    <br>
                                    <strong>Data mínima para agendamento: <?= $dataMinimaFormatada ?></strong>
                                    <?php if ($prazoMinimo > 0): ?>
                                        (prazo mínimo de <?= $prazoMinimo ?> dia<?= $prazoMinimo > 1 ? 's' : '' ?>)
                                    <?php endif; ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Seleção de Horário -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            Selecione um Horário
                        </label>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <label class="relative">
                                <input type="radio" name="horario_selecionado" value="08:00-11:00" class="sr-only horario-radio">
                                <div class="border-2 border-gray-200 rounded-lg p-3 text-center cursor-pointer hover:border-green-300 transition-colors horario-card">
                                    <div class="text-sm font-medium text-gray-900">08h00 às 11h00</div>
                                </div>
                            </label>
                            
                            <label class="relative">
                                <input type="radio" name="horario_selecionado" value="11:00-14:00" class="sr-only horario-radio">
                                <div class="border-2 border-gray-200 rounded-lg p-3 text-center cursor-pointer hover:border-green-300 transition-colors horario-card">
                                    <div class="text-sm font-medium text-gray-900">11h00 às 14h00</div>
                                </div>
                            </label>
                            
                            <label class="relative">
                                <input type="radio" name="horario_selecionado" value="14:00-17:00" class="sr-only horario-radio">
                                <div class="border-2 border-gray-200 rounded-lg p-3 text-center cursor-pointer hover:border-green-300 transition-colors horario-card">
                                    <div class="text-sm font-medium text-gray-900">14h00 às 17h00</div>
                                </div>
                            </label>
                            
                            <label class="relative">
                                <input type="radio" name="horario_selecionado" value="17:00-20:00" class="sr-only horario-radio">
                                <div class="border-2 border-gray-200 rounded-lg p-3 text-center cursor-pointer hover:border-green-300 transition-colors horario-card">
                                    <div class="text-sm font-medium text-gray-900">17h00 às 20h00</div>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Horários Selecionados -->
                    <div id="horarios-selecionados" class="hidden">
                        <h4 class="text-sm font-medium text-gray-700 mb-3">
                            Horários Selecionados (<span id="contador-horarios">0</span>/3)
                        </h4>
                        <div id="lista-horarios" class="space-y-2">
                            <!-- Horários serão inseridos aqui via JavaScript -->
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Navigation -->
                <div class="flex justify-between pt-6">
                    <a href="<?= url($locatario['instancia'] . '/nova-solicitacao/etapa/3') ?>" 
                       class="px-6 py-3 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                        Voltar
                    </a>
                    <button type="submit" id="btn-continuar" <?= $isEmergencial ? '' : 'disabled' ?>
                            class="px-6 py-3 <?= $isEmergencial ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-400 cursor-not-allowed' ?> text-white font-medium rounded-lg transition-colors">
                        Continuar
                    </button>
                </div>
            </form>
        </div>
        
    <?php elseif ($etapaAtual == 5): ?>
        <!-- ETAPA 5: CONFIRMAÇÃO -->
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">
                <i class="fas fa-check mr-2"></i>
                Confirmação da Solicitação
            </h2>
            </div>
            
        <div class="p-6">
            <!-- Aviso Responsável -->
            <div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex items-start">
                    <i class="fas fa-exclamation-triangle text-yellow-600 mr-3 mt-0.5"></i>
                    <div>
                        <h4 class="text-sm font-medium text-yellow-800">Responsável no Local</h4>
                        <p class="text-sm text-yellow-700 mt-1">
                            É obrigatória a presença de uma pessoa maior de 18 anos no local durante todo o período de execução do serviço para acompanhar e autorizar os trabalhos.
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Resumo da Solicitação -->
            <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Resumo da Solicitação</h3>
                
                <div class="space-y-4">
                    <!-- Endereço -->
                    <div>
                        <span class="text-sm font-medium text-gray-500">Endereço:</span>
                        <p class="text-sm text-gray-900">
                            <?php 
                            $imovel = $locatario['imoveis'][$nova_solicitacao['endereco_selecionado'] ?? 0] ?? [];
                            echo htmlspecialchars($imovel['endereco'] ?? '') . ', ' . htmlspecialchars($imovel['numero'] ?? '') . ' ' . htmlspecialchars($imovel['bairro'] ?? '') . ', ' . htmlspecialchars($imovel['cidade'] ?? '') . ' - ' . htmlspecialchars($imovel['cep'] ?? '');
                            ?>
                        </p>
                    </div>
                    
                    <!-- Serviço -->
                    <div>
                        <span class="text-sm font-medium text-gray-500">Serviço:</span>
                        <p class="text-sm text-gray-900">
                            <?php
                            // Buscar nome da subcategoria
                            $subcategoriaModel = new \App\Models\Subcategoria();
                            $subcategoria = $subcategoriaModel->find($nova_solicitacao['subcategoria_id'] ?? 0);
                            echo htmlspecialchars($subcategoria['nome'] ?? 'Serviço selecionado');
                            ?>
                        </p>
                    </div>
                    
                    <!-- Local da Manutenção -->
                    <div>
                        <span class="text-sm font-medium text-gray-500">Local da Manutenção:</span>
                        <p class="text-sm text-gray-900"><?= htmlspecialchars($nova_solicitacao['local_manutencao'] ?? 'Não informado') ?></p>
                    </div>
                    
                    <!-- Descrição -->
                    <div>
                        <span class="text-sm font-medium text-gray-500">Descrição:</span>
                        <p class="text-sm text-gray-900"><?= htmlspecialchars($nova_solicitacao['descricao_problema'] ?? 'Não informada') ?></p>
                    </div>
                    
                    <!-- Horários Preferenciais -->
                    <div>
                        <span class="text-sm font-medium text-gray-500">Horários Preferenciais:</span>
                        <?php
                        $horarios = $nova_solicitacao['horarios_preferenciais'] ?? [];
                        if (!empty($horarios) && is_array($horarios)):
                        ?>
                            <div class="mt-2 space-y-2">
                                <?php foreach ($horarios as $index => $horario): ?>
                                    <div class="flex items-center bg-green-50 border border-green-200 rounded-lg p-3">
                                        <i class="fas fa-clock text-green-600 mr-3"></i>
                                        <div>
                                            <span class="text-sm font-medium text-green-800">Opção <?= $index + 1 ?>:</span>
                                            <span class="text-sm text-green-700 ml-2">
                                                <?php
                                                // Formatar horário: 2025-10-29 08:00:00 → 29/10/2025 às 08:00
                                                if (preg_match('/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2})/', $horario, $matches)) {
                                                    echo $matches[3] . '/' . $matches[2] . '/' . $matches[1] . ' às ' . $matches[4] . ':' . $matches[5];
                                                } else {
                                                    echo htmlspecialchars($horario);
                                                }
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-sm text-gray-900 mt-1">Não informado</p>
                        <?php endif; ?>
            </div>
        </div>
    </div>
    
            <?php
            // Verificar se é emergencial e fora do horário comercial
            $isEmergencial = !empty($nova_solicitacao['is_emergencial']);
            $isForaHorario = !empty($nova_solicitacao['is_fora_horario']);
            
            // Buscar telefone de emergência
            $telefoneEmergenciaModel = new \App\Models\TelefoneEmergencia();
            $telefoneEmergencia = $telefoneEmergenciaModel->getPrincipal();
            ?>
            
            <!-- Termo de Aceite -->
            <form method="POST" action="<?= url($locatario['instancia'] . '/nova-solicitacao/etapa/5') ?>" class="space-y-6">
                <?= \App\Core\View::csrfField() ?>
                
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <label class="flex items-start">
                        <input type="checkbox" name="termo_aceite" value="1" required
                               class="mt-1 h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                        <span class="ml-3 text-sm text-gray-700">
                            Li e aceito todas as informações e avisos acima. Confirmo que estarei presente no local durante o atendimento e que comunicarei a administração/portaria quando necessário.
                            <span class="text-red-600">*</span>
                        </span>
                    </label>
                </div>
                
                <!-- Termo de LGPD - SEPARADO -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-4">
                    <label class="flex items-start cursor-pointer">
                        <input type="checkbox" name="lgpd_aceite" id="lgpd_aceite" value="1" required
                               class="mt-1 h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                        <span class="ml-3 text-sm text-blue-900">
                            Li e aceito o tratamento de meus dados pessoais conforme a <a href="<?= url('Public/assets/pdf/CONDICOES_GERAIS_ASSIST_KSS_RESIDENCIAL.pdf') ?>" target="_blank" class="underline font-medium text-blue-700 hover:text-blue-900">Lei Geral de Proteção de Dados (LGPD)</a>. Autorizo o uso de meus dados exclusivamente para o gerenciamento desta solicitação de serviço.
                            <span class="text-red-600">*</span>
                        </span>
                    </label>
                </div>
                
                <!-- Navigation -->
                <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 sm:justify-between pt-6">
                    <a href="<?= url($locatario['instancia'] . '/nova-solicitacao/etapa/4') ?>" 
                       class="w-full sm:w-auto px-6 py-3 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors text-center sm:text-left">
                        <i class="fas fa-arrow-left mr-2"></i>Voltar
                    </a>
                    <button type="submit" id="btn-finalizar"
                            class="w-full sm:w-auto flex-1 sm:flex-none px-6 py-3 <?= ($isEmergencial && $isForaHorario) ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700' ?> text-white font-medium rounded-lg transition-colors">
                        <i class="fas fa-check mr-2"></i><?= ($isEmergencial && $isForaHorario) ? 'Solicitar Emergência' : 'Finalizar Solicitação' ?>
                    </button>
                </div>
            </form>
        </div>
        
    <?php else: ?>
        <!-- ETAPA INVÁLIDA -->
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                Etapa Inválida
            </h2>
        </div>
        
        <div class="p-6 text-center">
            <div class="py-12">
                <i class="fas fa-exclamation-triangle text-4xl text-red-300 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Etapa não encontrada</h3>
                <p class="text-gray-500 mb-6">A etapa solicitada não existe.</p>
                <a href="<?= url($locatario['instancia'] . '/nova-solicitacao') ?>" 
                   class="inline-flex items-center px-4 py-2 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-home mr-2"></i>
                    Voltar ao Início
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Loading Overlay -->
<div id="loading-overlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-8 max-w-md mx-4 text-center">
        <div class="mb-4">
            <!-- Spinner animado -->
            <div class="inline-block animate-spin rounded-full h-16 w-16 border-b-4 border-green-600"></div>
        </div>
        <h3 class="text-xl font-semibold text-gray-900 mb-2">Criando sua solicitação...</h3>
        <p class="text-gray-600 mb-4">Por favor, aguarde enquanto processamos seus dados.</p>
        <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
            <div class="bg-green-600 h-2.5 rounded-full animate-pulse" style="width: 70%"></div>
        </div>
        <p class="text-sm text-gray-500 mt-4">Isso pode levar alguns segundos...</p>
    </div>
</div>

<style>
/* Melhorar aparência do input de data */
input[type="date"] {
    position: relative;
    cursor: pointer;
    font-family: inherit;
}

input[type="date"]::-webkit-calendar-picker-indicator {
    position: absolute;
    right: 12px;
    width: 20px;
    height: 20px;
    cursor: pointer;
    opacity: 0.6;
    transition: opacity 0.2s;
}

input[type="date"]::-webkit-calendar-picker-indicator:hover {
    opacity: 1;
}

input[type="date"]:focus {
    outline: none;
}

/* Estilo quando a data está vazia (placeholder visual) */
input[type="date"]:not(:focus)::-webkit-datetime-edit {
    color: transparent;
}

input[type="date"]:not(:focus)::before {
    content: attr(placeholder);
    color: #9ca3af;
    margin-right: 8px;
}

input[type="date"]:valid:not(:focus)::before,
input[type="date"]:focus::before {
    content: none;
}

input[type="date"]:valid:not(:focus)::-webkit-datetime-edit {
    color: #374151;
}
</style>

<script>
// JavaScript para interação básica
document.addEventListener('DOMContentLoaded', function() {
    console.log('✓ Sistema carregado');
    
    // === ETAPA 4: Sistema de seleção de data com modal e calendário visual (NORMAL) ===
    function formatarDataParaExibicao(dataStr) {
        if (!dataStr) return 'Selecione uma Data';
        const [ano, mes, dia] = dataStr.split('-');
        return `${dia}/${mes}/${ano}`;
    }
    
    function criarCalendarioVisual(containerId, minDate, maxDate, dataMinimaAttr, onSelect) {
        const container = document.getElementById(containerId);
        if (!container) return null;
        
        let currentMonth = new Date();
        let selectedDate = null;
        
        const meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        const diasSemana = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
        
        function renderizarCalendario() {
            const ano = currentMonth.getFullYear();
            const mes = currentMonth.getMonth();
            
            const primeiroDia = new Date(ano, mes, 1);
            const ultimoDia = new Date(ano, mes + 1, 0);
            const primeiroDiaSemana = primeiroDia.getDay();
            const totalDias = ultimoDia.getDate();
            
            let html = `
                <div class="calendar-header flex items-center justify-between mb-4">
                    <button type="button" class="calendar-nav-btn p-2 hover:bg-gray-100 rounded-lg transition-colors" data-direction="-1">
                        <i class="fas fa-chevron-left text-gray-600"></i>
                    </button>
                    <div class="text-lg font-semibold text-gray-800">${meses[mes]} ${ano}</div>
                    <button type="button" class="calendar-nav-btn p-2 hover:bg-gray-100 rounded-lg transition-colors" data-direction="1">
                        <i class="fas fa-chevron-right text-gray-600"></i>
                    </button>
                </div>
                <div class="calendar-grid">
                    <div class="grid grid-cols-7 gap-1 mb-2">
                        ${diasSemana.map(dia => `<div class="text-center text-xs font-semibold text-gray-600 py-1">${dia}</div>`).join('')}
                    </div>
                    <div class="grid grid-cols-7 gap-1" id="calendar-days-${containerId}">
            `;
            
            // Espaços vazios antes do primeiro dia
            for (let i = 0; i < primeiroDiaSemana; i++) {
                html += '<div class="p-2"></div>';
            }
            
            // Dias do mês
            const hoje = new Date();
            hoje.setHours(0, 0, 0, 0);
            
            for (let dia = 1; dia <= totalDias; dia++) {
                const dataAtual = new Date(ano, mes, dia);
                const dataAtualStr = dataAtual.toISOString().split('T')[0];
                const diaSemana = dataAtual.getDay();
                const isFimDeSemana = diaSemana === 0 || diaSemana === 6;
                const isToday = dataAtual.getTime() === hoje.getTime();
                const isSelected = selectedDate && selectedDate.toISOString().split('T')[0] === dataAtualStr;
                
                let disabled = false;
                let disabledClass = '';
                
                if (minDate && dataAtual < new Date(minDate)) disabled = true;
                if (maxDate && dataAtual > new Date(maxDate)) disabled = true;
                if (isFimDeSemana) disabled = true;
                
                if (dataMinimaAttr) {
                    const dataMinima = new Date(dataMinimaAttr + 'T12:00:00');
                    dataMinima.setHours(0, 0, 0, 0);
                    if (dataAtual < dataMinima) disabled = true;
                }
                
                if (disabled) {
                    disabledClass = 'text-gray-300 bg-gray-50 cursor-not-allowed';
                } else if (isSelected) {
                    disabledClass = 'bg-green-600 text-white font-semibold';
                } else if (isToday) {
                    disabledClass = 'bg-green-100 text-green-700 font-semibold hover:bg-green-200';
                } else {
                    disabledClass = 'text-gray-700 hover:bg-gray-100';
                }
                
                html += `
                    <button type="button" 
                            class="calendar-day p-2 text-sm rounded-lg transition-colors ${disabledClass}" 
                            data-date="${dataAtualStr}"
                            ${disabled ? 'disabled' : ''}>
                        ${dia}
                    </button>
                `;
            }
            
            html += `
                    </div>
                </div>
            `;
            
            container.innerHTML = html;
            
            // Event listeners
            container.querySelectorAll('.calendar-nav-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const direction = parseInt(this.getAttribute('data-direction'));
                    currentMonth.setMonth(currentMonth.getMonth() + direction);
                    renderizarCalendario();
                });
            });
            
            container.querySelectorAll('.calendar-day:not(:disabled)').forEach(btn => {
                btn.addEventListener('click', function() {
                    const dateStr = this.getAttribute('data-date');
                    selectedDate = new Date(dateStr + 'T12:00:00');
                    
                    // Atualizar visual
                    container.querySelectorAll('.calendar-day').forEach(b => {
                        b.classList.remove('bg-green-600', 'text-white');
                        if (!b.disabled && !b.classList.contains('bg-green-100')) {
                            b.classList.add('text-gray-700');
                        }
                    });
                    
                    this.classList.add('bg-green-600', 'text-white', 'font-semibold');
                    this.classList.remove('text-gray-700', 'bg-green-100', 'text-green-700');
                    
                    if (onSelect) {
                        onSelect(dateStr);
                    }
                });
            });
        }
        
        renderizarCalendario();
        
        return {
            getSelectedDate: () => selectedDate ? selectedDate.toISOString().split('T')[0] : null,
            setSelectedDate: (dateStr) => {
                if (dateStr) {
                    selectedDate = new Date(dateStr + 'T12:00:00');
                    const dataObj = new Date(dateStr);
                    currentMonth = new Date(dataObj.getFullYear(), dataObj.getMonth(), 1);
                }
                renderizarCalendario();
            }
        };
    }
    
    const btnSelecionarData = document.getElementById('btn-selecionar-data');
    const modalData = document.getElementById('modal-data');
    const dataSelecionadaHidden = document.getElementById('data_selecionada');
    const textoBotaoData = document.getElementById('texto-botao-data');
    const btnFecharModalData = document.getElementById('fechar-modal-data');
    const calendarioContainer = document.getElementById('calendario-container-data');
    
    let calendarioInstance = null;
    
    function resetarBotaoData() {
        if (dataSelecionadaHidden) {
            dataSelecionadaHidden.value = '';
        }
        if (textoBotaoData) {
            textoBotaoData.textContent = 'Selecione uma Data';
        }
    }
    
    if (btnSelecionarData && modalData && calendarioContainer) {
        const dataMinimaAttr = dataSelecionadaHidden ? dataSelecionadaHidden.getAttribute('data-min-date') : null;
        const minDate = dataMinimaAttr || '<?= date('Y-m-d', strtotime('+1 day')) ?>';
        const maxDate = '<?= date('Y-m-d', strtotime('+30 days')) ?>';
        
        // Criar calendário quando abrir o modal
        btnSelecionarData.addEventListener('click', function() {
            modalData.classList.remove('hidden');
            
            // Criar calendário se ainda não existe
            if (!calendarioInstance) {
                calendarioInstance = criarCalendarioVisual(
                    'calendario-container-data',
                    minDate,
                    maxDate,
                    dataMinimaAttr,
                    function(dateStr) {
                        // Validação e salvamento
                        const dataObj = new Date(dateStr + 'T12:00:00');
                        const diaDaSemana = dataObj.getDay();
                        
            if (diaDaSemana === 0 || diaDaSemana === 6) {
                const nomeDia = diaDaSemana === 0 ? 'domingo' : 'sábado';
                alert('⚠️ Atendimentos não são realizados aos fins de semana.\n\nA data selecionada é um ' + nomeDia + '.\nPor favor, selecione um dia útil (segunda a sexta-feira).');
                return;
            }
            
                        if (dataSelecionadaHidden) {
                            dataSelecionadaHidden.value = dateStr;
                        }
                        
                        if (textoBotaoData) {
                            textoBotaoData.textContent = formatarDataParaExibicao(dateStr);
                        }
                        
                        setTimeout(() => {
                            fecharModal();
                        }, 300);
                    }
                );
            } else {
                // Restaurar data selecionada se houver
                if (dataSelecionadaHidden && dataSelecionadaHidden.value) {
                    calendarioInstance.setSelectedDate(dataSelecionadaHidden.value);
                }
            }
        });
        
        function fecharModal() {
            modalData.classList.add('hidden');
        }
        
        if (btnFecharModalData) btnFecharModalData.addEventListener('click', fecharModal);
        
        modalData.addEventListener('click', function(e) {
            if (e.target === modalData) {
                fecharModal();
            }
        });
    }
    
    // === ETAPA 4: Sistema de seleção de data com modal e calendário visual (EMERGENCIAL) ===
    const btnSelecionarDataEmergencial = document.getElementById('btn-selecionar-data-emergencial');
    const modalDataEmergencial = document.getElementById('modal-data-emergencial');
    const dataSelecionadaHiddenEmergencial = document.getElementById('data_selecionada_emergencial');
    const textoBotaoDataEmergencial = document.getElementById('texto-botao-data-emergencial');
    const btnFecharModalDataEmergencial = document.getElementById('fechar-modal-data-emergencial');
    const calendarioContainerEmergencial = document.getElementById('calendario-container-data-emergencial');
    
    let calendarioInstanceEmergencial = null;
    
    function resetarBotaoDataEmergencial() {
        if (dataSelecionadaHiddenEmergencial) {
            dataSelecionadaHiddenEmergencial.value = '';
        }
        if (textoBotaoDataEmergencial) {
            textoBotaoDataEmergencial.textContent = 'Selecione uma Data';
        }
    }
    
    if (btnSelecionarDataEmergencial && modalDataEmergencial && calendarioContainerEmergencial) {
        const minDate = '<?= date('Y-m-d', strtotime('+1 day')) ?>';
        const maxDate = '<?= date('Y-m-d', strtotime('+30 days')) ?>';
        
        btnSelecionarDataEmergencial.addEventListener('click', function() {
            modalDataEmergencial.classList.remove('hidden');
            
            if (!calendarioInstanceEmergencial) {
                calendarioInstanceEmergencial = criarCalendarioVisual(
                    'calendario-container-data-emergencial',
                    minDate,
                    maxDate,
                    null,
                    function(dateStr) {
                        const dataObj = new Date(dateStr + 'T12:00:00');
                        const diaDaSemana = dataObj.getDay();
                        
                        if (diaDaSemana === 0 || diaDaSemana === 6) {
                            const nomeDia = diaDaSemana === 0 ? 'domingo' : 'sábado';
                            alert('⚠️ Atendimentos não são realizados aos fins de semana.\n\nA data selecionada é um ' + nomeDia + '.\nPor favor, selecione um dia útil (segunda a sexta-feira).');
                    return;
                        }
                        
                        if (dataSelecionadaHiddenEmergencial) {
                            dataSelecionadaHiddenEmergencial.value = dateStr;
                        }
                        
                        if (textoBotaoDataEmergencial) {
                            textoBotaoDataEmergencial.textContent = formatarDataParaExibicao(dateStr);
                        }
                        
                        setTimeout(() => {
                            fecharModalEmergencial();
                        }, 300);
                    }
                );
            } else {
                if (dataSelecionadaHiddenEmergencial && dataSelecionadaHiddenEmergencial.value) {
                    calendarioInstanceEmergencial.setSelectedDate(dataSelecionadaHiddenEmergencial.value);
                }
            }
        });
        
        function fecharModalEmergencial() {
            modalDataEmergencial.classList.add('hidden');
        }
        
        if (btnFecharModalDataEmergencial) btnFecharModalDataEmergencial.addEventListener('click', fecharModalEmergencial);
        
        modalDataEmergencial.addEventListener('click', function(e) {
            if (e.target === modalDataEmergencial) {
                fecharModalEmergencial();
            }
        });
    }
    
    // === ETAPA 5: Loading ao finalizar ===
    const btnFinalizar = document.getElementById('btn-finalizar');
    if (btnFinalizar) {
        const formFinalizar = btnFinalizar.closest('form');
        if (formFinalizar) {
            formFinalizar.addEventListener('submit', async function(e) {
                // Verificar se o termo foi aceito
                const termoAceite = formFinalizar.querySelector('input[name="termo_aceite"]');
                const lgpdAceite = formFinalizar.querySelector('input[name="lgpd_aceite"]');
                
                if (!termoAceite || !termoAceite.checked) {
                    e.preventDefault();
                    alert('Por favor, aceite os termos para continuar.');
                    return;
                }
                
                if (!lgpdAceite || !lgpdAceite.checked) {
                    e.preventDefault();
                    alert('Por favor, aceite o termo de LGPD para continuar.');
                    return;
                }
                
                // Verificar se é emergencial e fora do horário
                const isEmergenciaForaHorario = btnFinalizar.getAttribute('data-emergencia-fora-horario') === 'true';
                const telefone = btnFinalizar.getAttribute('data-telefone');
                
                if (isEmergenciaForaHorario && telefone) {
                    e.preventDefault();
                    
                    // Mostrar loading
                    const loadingOverlay = document.getElementById('loading-overlay');
                    if (loadingOverlay) {
                        loadingOverlay.classList.remove('hidden');
                    }
                    
                    // Desabilitar botão
                    btnFinalizar.disabled = true;
                    btnFinalizar.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Salvando...';
                    
                    try {
                        // Criar FormData e enviar para salvar no kanban
                        const formData = new FormData(formFinalizar);
                        
                        const response = await fetch(formFinalizar.action, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            // Após salvar, abrir o telefone imediatamente
                            const telefoneHref = 'tel:' + telefone.replace(/[^0-9+]/g, '');
                            
                            // Abrir o link de telefone
                            window.location.href = telefoneHref;
                            
                            // Redirecionar após um pequeno delay (para dar tempo do telefone abrir)
                            setTimeout(() => {
                                if (result.redirect) {
                                    window.location.href = result.redirect;
                                } else {
                                    window.location.href = '<?= url($locatario['instancia'] . '/solicitacoes') ?>';
                                }
                            }, 2000);
                        } else {
                            alert('Erro ao salvar solicitação: ' + (result.message || 'Erro desconhecido'));
                            btnFinalizar.disabled = false;
                            btnFinalizar.innerHTML = 'Solicitar Emergência';
                            if (loadingOverlay) {
                                loadingOverlay.classList.add('hidden');
                            }
                        }
                    } catch (error) {
                        console.error('Erro:', error);
                        alert('Erro ao processar solicitação. Tente novamente.');
                        btnFinalizar.disabled = false;
                        btnFinalizar.innerHTML = 'Solicitar Emergência';
                        if (loadingOverlay) {
                            loadingOverlay.classList.add('hidden');
                        }
                    }
                    
                    return;
                }
                
                // Para solicitações normais, continuar com o fluxo padrão
                // Mostrar loading
                const loadingOverlay = document.getElementById('loading-overlay');
                if (loadingOverlay) {
                    loadingOverlay.classList.remove('hidden');
                }
                
                // Desabilitar botão para evitar cliques duplos
                btnFinalizar.disabled = true;
                btnFinalizar.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processando...';
            });
        }
    }
    
    // DEBUG: Monitorar submit do formulário da etapa 1
    const btnContinuarEtapa1 = document.getElementById('btn-continuar-etapa1');
    if (btnContinuarEtapa1) {
        const form = btnContinuarEtapa1.closest('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                console.log('📤 Formulário sendo submetido...');
                console.log('Action:', form.action);
                console.log('Method:', form.method);
                
                const formData = new FormData(form);
                console.log('Dados do formulário:');
                for (let [key, value] of formData.entries()) {
                    console.log(`  ${key}: ${value}`);
                }
                
                // Não prevenir o submit, deixar funcionar normalmente
                console.log('✓ Permitindo submit do formulário');
            });
            
            btnContinuarEtapa1.addEventListener('click', function() {
                console.log('🖱️ Botão "Continuar" clicado!');
            });
        }
    }
    
    // === ETAPA 2: Seleção de Categoria ===
    const categoriaRadios = document.querySelectorAll('.categoria-radio');
    
    // === ETAPA 2: Função para expandir/colapsar categoria pai (com filhas) ===
    window.toggleCategoriaPaiManual = function(categoriaPaiId) {
        const container = document.getElementById('filhas-' + categoriaPaiId);
        const chevron = document.getElementById('chevron-toggle-pai-' + categoriaPaiId);
        
        if (!container) return;
        
        if (container.classList.contains('hidden') || container.style.display === 'none') {
            // Expandir
            container.classList.remove('hidden');
            container.style.display = 'block';
            if (chevron) {
                chevron.classList.add('rotate-180');
            }
        } else {
            // Colapsar
            container.classList.add('hidden');
            container.style.display = 'none';
            if (chevron) {
                chevron.classList.remove('rotate-180');
            }
        }
    };
    
    // === ETAPA 2: Função para expandir/colapsar categoria (simples ou filha) ===
    window.toggleCategoriaManual = function(categoriaId) {
        const container = document.getElementById('descricao-cat-' + categoriaId);
        const chevron = document.getElementById('chevron-toggle-' + categoriaId);
        
        if (!container) return;
        
        if (container.classList.contains('hidden')) {
            // Expandir
            container.classList.remove('hidden');
            if (chevron) {
                chevron.classList.add('rotate-180');
            }
        } else {
            // Colapsar
            container.classList.add('hidden');
            if (chevron) {
                chevron.classList.remove('rotate-180');
            }
        }
    };
    
    // === ETAPA 2: Click em qualquer lugar do card da categoria expande/colapsa ===
    document.addEventListener('click', function(e) {
        // Ignorar cliques em botões de condições gerais e botões de toggle
        if (e.target.closest('.btn-condicoes-gerais') || e.target.closest('.btn-toggle-categoria-pai') || e.target.closest('.btn-toggle-categoria')) {
            return;
        }
        
        // Click no card da categoria pai (que tem filhas)
        const categoriaPaiContainer = e.target.closest('.categoria-pai-container');
        if (categoriaPaiContainer) {
            const cardPai = categoriaPaiContainer.querySelector(':scope > div.border-2');
            if (cardPai && cardPai.contains(e.target)) {
                const categoriaPaiId = categoriaPaiContainer.dataset.categoriaPaiId;
                if (categoriaPaiId) {
                    toggleCategoriaPaiManual(categoriaPaiId);
                }
                return;
            }
        }
        
        // Click no card da categoria (filha ou simples)
        const categoriaContainer = e.target.closest('.categoria-container');
        if (categoriaContainer) {
            const cardCategoria = categoriaContainer.querySelector(':scope > div.border-2');
            if (cardCategoria && cardCategoria.contains(e.target)) {
                const categoriaId = categoriaContainer.dataset.categoriaId;
                if (categoriaId) {
                    toggleCategoriaManual(categoriaId);
                }
            }
        }
    });
    
    // === ETAPA 2: Seleção de Subcategoria ===
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('subcategoria-radio')) {
            const allSubCards = document.querySelectorAll('.subcategoria-card');
            const allSubChecks = document.querySelectorAll('.subcategoria-check');
            
            // Remover seleção de todos
            allSubCards.forEach(card => {
                card.classList.remove('border-blue-500', 'bg-blue-50');
                card.classList.add('border-gray-200');
            });
            allSubChecks.forEach(check => {
                check.classList.remove('bg-blue-500', 'border-blue-500');
                check.classList.add('border-gray-300');
            });
            
            // Adicionar seleção ao card pai do radio selecionado
            const selectedCard = e.target.closest('label').querySelector('.subcategoria-card');
            const selectedCheck = e.target.closest('label').querySelector('.subcategoria-check');
            
            if (selectedCard) {
                selectedCard.classList.remove('border-gray-200');
                selectedCard.classList.add('border-blue-500', 'bg-blue-50');
            }
            if (selectedCheck) {
                selectedCheck.classList.remove('border-gray-300');
                selectedCheck.classList.add('bg-blue-500', 'border-blue-500');
            }
            
            // Marcar também a categoria pai
            const categoriaRadio = e.target.closest('label').querySelector('.categoria-radio');
            if (categoriaRadio) {
                categoriaRadio.checked = true;
            }
        }
    });
    
    // Click no card da subcategoria seleciona o radio
    document.addEventListener('click', function(e) {
        const subCard = e.target.closest('.subcategoria-card');
        if (subCard) {
            e.stopPropagation();
            const label = subCard.closest('label');
            const subcategoriaRadio = label ? label.querySelector('.subcategoria-radio') : null;
            const categoriaRadio = label ? label.querySelector('.categoria-radio') : null;
            if (subcategoriaRadio) {
                subcategoriaRadio.checked = true;
                subcategoriaRadio.dispatchEvent(new Event('change', { bubbles: true }));
            }
            if (categoriaRadio) {
                categoriaRadio.checked = true;
            }
        }
    }, true);
    
    // Inicializar estados das subcategorias selecionadas (sem expandir automaticamente)
    document.addEventListener('DOMContentLoaded', function() {
        // Atualizar visual das subcategorias selecionadas sem expandir as categorias
        document.querySelectorAll('.subcategoria-radio:checked').forEach(radio => {
            // Disparar evento para atualizar visual da seleção
            radio.dispatchEvent(new Event('change', { bubbles: true }));
        });
    });
    
    // Função para mostrar modal de limite atingido (definida aqui para estar disponível)
    window.mostrarModalLimite = function(totalAtual, limite) {
        const modal = document.getElementById('modal-limite-atingido');
        const mensagem = document.getElementById('modal-limite-mensagem');
        
        if (!modal || !mensagem) {
            // Fallback para alert se modal não estiver disponível
            alert(`Você já possui ${totalAtual} solicitação${totalAtual > 1 ? 'ões' : ''} desta categoria nos últimos 12 meses. O limite permitido é de ${limite} solicitação${limite > 1 ? 'ões' : ''}.`);
            return;
        }
        
        const textoMensagem = `Você já possui <strong>${totalAtual}</strong> solicitação${totalAtual > 1 ? 'ões' : ''} desta categoria nos últimos 12 meses. O limite permitido é de <strong>${limite}</strong> solicitação${limite > 1 ? 'ões' : ''}.`;
        
        mensagem.innerHTML = textoMensagem;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        
        // Prevenir scroll do body quando modal estiver aberto
        document.body.style.overflow = 'hidden';
    };
    
    // Verificar limites de todas as categorias ao carregar a página
    function verificarLimitesCategorias() {
        const enderecoSelecionado = document.querySelector('input[name="endereco_selecionado"]:checked');
        if (!enderecoSelecionado) {
            return;
        }
        
        const enderecoIndex = enderecoSelecionado.value;
        const enderecoItem = document.querySelector(`.endereco-item-${enderecoIndex}`);
        if (!enderecoItem) {
            return;
        }
        
        const numeroContrato = enderecoItem.getAttribute('data-contrato') || '';
        if (!numeroContrato) {
            return;
        }
        
        const instancia = '<?= $locatario["instancia"] ?? "" ?>';
        
        // Buscar todos os containers de categoria (nova estrutura)
        const categoriaContainers = document.querySelectorAll('.categoria-container[data-categoria-id]');
        
        categoriaContainers.forEach(container => {
            const categoriaId = container.getAttribute('data-categoria-id');
            if (!categoriaId) return;
            
            fetch(`/${instancia}/verificar-limite-categoria?categoria_id=${categoriaId}&numero_contrato=${encodeURIComponent(numeroContrato)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && !data.permitido) {
                        // Encontrar o card da categoria dentro do container
                        const card = container.querySelector('.border-2.rounded-lg');
                        if (card) {
                            // Desabilitar visualmente a categoria
                            card.classList.add('opacity-60', 'cursor-not-allowed', 'bg-gray-50', 'border-gray-300');
                            card.classList.remove('hover:border-blue-300', 'cursor-pointer');
                            card.style.cursor = 'not-allowed';
                            
                            // Adicionar ícone de bloqueio visual
                            const iconContainer = card.querySelector('.flex.items-center');
                            if (iconContainer && !iconContainer.querySelector('.fa-lock')) {
                                const lockIcon = document.createElement('i');
                                lockIcon.className = 'fas fa-lock text-gray-400 mr-2';
                                iconContainer.insertBefore(lockIcon, iconContainer.firstChild);
                            }
                            
                            // Adicionar atributo para identificar como desabilitada
                            card.setAttribute('data-limite-atingido', 'true');
                            card.setAttribute('data-total-atual', data.total_atual);
                            card.setAttribute('data-limite', data.limite);
                        }
                        
                        // Desabilitar o radio
                        const radio = container.querySelector(`.categoria-radio[value="${categoriaId}"]`);
                        if (radio) {
                            radio.disabled = true;
                            radio.setAttribute('data-limite-atingido', 'true');
                            radio.setAttribute('data-total-atual', data.total_atual);
                            radio.setAttribute('data-limite', data.limite);
                        }
                        
                        // Desabilitar o botão de toggle se existir
                        const toggleBtn = container.querySelector('.btn-toggle-categoria');
                        if (toggleBtn) {
                            toggleBtn.style.pointerEvents = 'none';
                            toggleBtn.style.opacity = '0.5';
                        }
                    }
                })
                .catch(error => {
                    console.error('Erro ao verificar limite da categoria:', error);
                });
        });
        
        // Também verificar categorias pai (se houver filhas)
        const categoriaPaiContainers = document.querySelectorAll('.categoria-pai-container[data-categoria-pai-id]');
        categoriaPaiContainers.forEach(container => {
            const categoriaPaiId = container.getAttribute('data-categoria-pai-id');
            if (!categoriaPaiId) return;
            
            fetch(`/${instancia}/verificar-limite-categoria?categoria_id=${categoriaPaiId}&numero_contrato=${encodeURIComponent(numeroContrato)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && !data.permitido) {
                        // Desabilitar visualmente a categoria pai
                        const card = container.querySelector('.border-2.rounded-lg');
                        if (card) {
                            card.classList.add('opacity-60', 'cursor-not-allowed', 'bg-gray-50', 'border-gray-300');
                            card.style.cursor = 'not-allowed';
                            
                            // Desabilitar botão de toggle
                            const toggleBtn = container.querySelector('.btn-toggle-categoria-pai');
                            if (toggleBtn) {
                                toggleBtn.style.pointerEvents = 'none';
                                toggleBtn.style.opacity = '0.5';
                            }
                        }
                    }
                })
                .catch(error => {
                    console.error('Erro ao verificar limite da categoria pai:', error);
                });
        });
    }
    
    // Verificar limites quando a página carregar
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', verificarLimitesCategorias);
    } else {
        verificarLimitesCategorias();
    }
    
    // Verificar limites quando mudar o endereço selecionado
    document.querySelectorAll('input[name="endereco_selecionado"]').forEach(radio => {
        radio.addEventListener('change', function() {
            // Reabilitar todas as categorias primeiro (nova estrutura)
            const categoriaContainers = document.querySelectorAll('.categoria-container[data-categoria-id]');
            categoriaContainers.forEach(container => {
                const card = container.querySelector('.border-2.rounded-lg');
                if (card) {
                    card.classList.remove('opacity-60', 'cursor-not-allowed', 'bg-gray-50', 'border-gray-300', 'pointer-events-none');
                    card.style.pointerEvents = '';
                    card.style.cursor = '';
                    card.removeAttribute('data-limite-atingido');
                    
                    // Remover ícone de bloqueio se existir
                    const lockIcon = card.querySelector('.fa-lock');
                    if (lockIcon) {
                        lockIcon.remove();
                    }
                }
                
                const categoriaId = container.getAttribute('data-categoria-id');
                const radioInput = container.querySelector(`.categoria-radio[value="${categoriaId}"]`);
                if (radioInput) {
                    radioInput.disabled = false;
                    radioInput.removeAttribute('data-limite-atingido');
                    radioInput.removeAttribute('data-total-atual');
                    radioInput.removeAttribute('data-limite');
                }
                
                // Reabilitar botão de toggle
                const toggleBtn = container.querySelector('.btn-toggle-categoria');
                if (toggleBtn) {
                    toggleBtn.style.pointerEvents = '';
                    toggleBtn.style.opacity = '';
                }
            });
            
            // Reabilitar categorias pai também
            const categoriaPaiContainers = document.querySelectorAll('.categoria-pai-container[data-categoria-pai-id]');
            categoriaPaiContainers.forEach(container => {
                const card = container.querySelector('.border-2.rounded-lg');
                if (card) {
                    card.classList.remove('opacity-60', 'cursor-not-allowed', 'bg-gray-50', 'border-gray-300');
                    card.style.cursor = '';
                    
                    const toggleBtn = container.querySelector('.btn-toggle-categoria-pai');
                    if (toggleBtn) {
                        toggleBtn.style.pointerEvents = '';
                        toggleBtn.style.opacity = '';
                    }
                }
            });
            
            // Verificar limites novamente
            setTimeout(verificarLimitesCategorias, 100);
        });
    });
    
    // Lógica de seleção de categoria será gerenciada pela seleção de subcategoria
    // (conforme implementado em solicitacao-manual.php)
    
    
    // === ETAPA 2: Função para seleção múltipla de subcategorias (Manutenção e Prevenção) ===
    window.toggleSubcategoriaManual = function(categoriaPaiId, categoriaFilhaId, subcategoriaId, checked) {
        // Buscar checkbox usando múltiplos seletores para garantir que encontre
        let checkbox = document.querySelector(`input[data-subcategoria="${subcategoriaId}"][data-categoria-pai="${categoriaPaiId}"].subcategoria-checkbox-manutencao`);
        if (!checkbox) {
            checkbox = document.querySelector(`input[data-subcategoria="${subcategoriaId}"].subcategoria-checkbox-manutencao`);
        }
        
        if (!checkbox) {
            console.error('Checkbox não encontrado para subcategoria:', subcategoriaId);
            return;
        }
        
        // NÃO alterar o estado do checkbox aqui, pois ele já foi alterado pelo clique
        // Apenas atualizar o visual baseado no estado atual
        const estadoAtual = checkbox.checked;
        
        const wrapper = checkbox.closest('.subcategoria-item-wrapper');
        if (!wrapper) {
            console.error('Wrapper não encontrado para subcategoria:', subcategoriaId);
            return;
        }
        
        const card = wrapper.querySelector('.subcategoria-card-manutencao');
        const checkIcon = wrapper.querySelector('.subcategoria-check-manutencao');
        const contador = document.getElementById('contador-subcategorias-' + categoriaPaiId);
        
        // Contar subcategorias marcadas no total (todas as categorias filhas)
        const todasSubcategoriasMarcadas = document.querySelectorAll(`input[data-categoria-pai="${categoriaPaiId}"].subcategoria-checkbox-manutencao:checked`);
        const totalMarcadas = todasSubcategoriasMarcadas.length;
        
        // Limitar a 3 seleções (verificar antes de atualizar visual)
        if (estadoAtual && totalMarcadas > 3) {
            checkbox.checked = false;
            alert('Você pode selecionar no máximo 3 subcategorias.');
            return;
        }
        
        // Atualizar estilo visual baseado no estado atual do checkbox
        if (card && checkIcon) {
            if (estadoAtual) {
                card.classList.add('border-blue-500', 'bg-blue-50');
                card.classList.remove('border-gray-200');
                checkIcon.classList.add('border-blue-500', 'bg-blue-500');
                checkIcon.classList.remove('border-gray-300');
                checkIcon.innerHTML = '<i class="fas fa-check text-white text-xs"></i>';
            } else {
                card.classList.remove('border-blue-500', 'bg-blue-50');
                card.classList.add('border-gray-200');
                checkIcon.classList.remove('border-blue-500', 'bg-blue-500');
                checkIcon.classList.add('border-gray-300');
                checkIcon.innerHTML = '';
            }
        } else {
            console.warn('Card ou checkIcon não encontrado para subcategoria:', subcategoriaId);
        }
        
        // Atualizar contador
        if (contador) {
            const totalAtual = document.querySelectorAll(`input[data-categoria-pai="${categoriaPaiId}"].subcategoria-checkbox-manutencao:checked`).length;
            contador.textContent = totalAtual;
        }
    };
    
    // Função para atualizar contador de subcategorias
    window.atualizarContadorSubcategorias = function(categoriaPaiId) {
        const contador = document.getElementById('contador-subcategorias-' + categoriaPaiId);
        if (contador) {
            const totalAtual = document.querySelectorAll(`input[data-categoria-pai="${categoriaPaiId}"].subcategoria-checkbox-manutencao:checked`).length;
            contador.textContent = totalAtual;
        }
    };
    
    // Função para validar seleção de subcategorias antes de enviar
    window.validarSelecaoSubcategorias = function() {
        // Verificar se há container de seleção múltipla de subcategorias
        const containers = document.querySelectorAll('[data-tipo-selecao-multipla-subcategorias="true"]');
            
        if (containers.length === 0) {
            // Se não há container de seleção múltipla, não precisa validar
            return true;
        }
        
        for (const container of containers) {
            const categoriaPaiId = container.dataset.categoriaPaiId;
            const subcategoriasMarcadas = container.querySelectorAll(`input[data-categoria-pai="${categoriaPaiId}"].subcategoria-checkbox-manutencao:checked`);
            
            // Verificar se o container está visível (expandido)
            const filhasContainer = document.getElementById('filhas-' + categoriaPaiId);
            if (filhasContainer && (filhasContainer.classList.contains('hidden') || filhasContainer.style.display === 'none')) {
                // Se o container não está expandido, não precisa validar
                continue;
            }
            
            if (subcategoriasMarcadas.length === 0) {
                alert('Por favor, selecione pelo menos uma subcategoria.');
                return false;
            }
            
            if (subcategoriasMarcadas.length > 3) {
                alert('Você pode selecionar no máximo 3 subcategorias.');
                return false;
            }
        }
        
        return true;
    };
    
    // Adicionar listener direto nos checkboxes para garantir que funcione
    // Usar event delegation para elementos que podem ser adicionados dinamicamente
    // IMPORTANTE: Fora do DOMContentLoaded para garantir que seja anexado imediatamente
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('subcategoria-checkbox-manutencao')) {
            const checkbox = e.target;
            const categoriaPaiId = checkbox.dataset.categoriaPai;
            const categoriaFilhaId = checkbox.dataset.categoriaFilha;
            const subcategoriaId = checkbox.dataset.subcategoria;
            
            // Debug
            console.log('Checkbox change detectado:', {
                categoriaPaiId,
                categoriaFilhaId,
                subcategoriaId,
                checked: checkbox.checked
            });
            
            if (categoriaPaiId && categoriaFilhaId && subcategoriaId) {
                // Chamar função imediatamente
                if (typeof toggleSubcategoriaManual === 'function') {
                    toggleSubcategoriaManual(categoriaPaiId, categoriaFilhaId, subcategoriaId, checkbox.checked);
                } else {
                    console.error('toggleSubcategoriaManual não está definida!');
                }
            } else {
                console.warn('Dados do checkbox incompletos:', {
                    categoriaPaiId,
                    categoriaFilhaId,
                    subcategoriaId
                });
            }
        }
    }, true); // Usar capture phase para garantir que seja executado
    
    // Checkbox agora é clicável apenas diretamente, não precisa de código adicional
    
    // Inicializar contadores e estados ao carregar
    document.addEventListener('DOMContentLoaded', function() {
        // Contar subcategorias já marcadas para seleção múltipla de subcategorias
        document.querySelectorAll('[data-tipo-selecao-multipla-subcategorias="true"]').forEach(container => {
            const categoriaPaiId = container.dataset.categoriaPaiId;
            
            // Atualizar contador
            atualizarContadorSubcategorias(categoriaPaiId);
            
            // Atualizar estado visual das subcategorias já selecionadas
            const checkboxesMarcados = container.querySelectorAll('.subcategoria-checkbox-manutencao:checked');
            checkboxesMarcados.forEach(checkbox => {
                const categoriaFilhaId = checkbox.dataset.categoriaFilha;
                const subcategoriaId = checkbox.dataset.subcategoria;
                const wrapper = checkbox.closest('.subcategoria-item-wrapper');
                const card = wrapper?.querySelector('.subcategoria-card-manutencao');
                const checkIcon = wrapper?.querySelector('.subcategoria-check-manutencao');
                
                if (card && checkIcon) {
                    card.classList.add('border-blue-500', 'bg-blue-50');
                    card.classList.remove('border-gray-200');
                    checkIcon.classList.add('border-blue-500', 'bg-blue-500');
                    checkIcon.classList.remove('border-gray-300');
                    checkIcon.innerHTML = '<i class="fas fa-check text-white text-xs"></i>';
                }
            });
        });
        
        // Adicionar validação ao formulário antes de enviar
        const form = document.querySelector('form[action*="etapa/2"]');
        if (form) {
            form.addEventListener('submit', function(e) {
                if (!validarSelecaoSubcategorias()) {
                    e.preventDefault();
                    return false;
            }
        });
        }
    });
    
    // Preview de fotos
    // Funções para modal de foto
    window.abrirModalFoto = function() {
        const modal = document.getElementById('modal-foto');
        if (modal) {
            modal.classList.remove('hidden');
        }
    };
    
    window.fecharModalFoto = function() {
        const modal = document.getElementById('modal-foto');
        if (modal) {
            modal.classList.add('hidden');
        }
    };
    
    window.escolherCamera = function() {
        const inputCamera = document.getElementById('fotos-camera');
        if (inputCamera) {
            inputCamera.click();
        }
        fecharModalFoto();
    };
    
    window.escolherArquivo = function() {
        console.log('📂 escolherArquivo chamado');
        const inputArquivo = document.getElementById('fotos');
        if (inputArquivo) {
            console.log('✅ Input arquivo encontrado, clicando...');
            // Não fechar o modal imediatamente, deixar o evento change fazer isso
            inputArquivo.click();
        } else {
            console.error('❌ Input arquivo não encontrado!');
            fecharModalFoto();
        }
    };
    
    // Armazenar referências dos arquivos para poder remover corretamente (deve ser global)
    let fotosArmazenadas = [];
    
    // Combinar fotos armazenadas antes de enviar o formulário
    window.combinarFotosAntesEnvio = function(event) {
        const inputArquivo = document.getElementById('fotos');
        
        console.log('🔍 combinarFotosAntesEnvio - fotosArmazenadas:', fotosArmazenadas);
        console.log('🔍 combinarFotosAntesEnvio - fotosArmazenadas.length:', fotosArmazenadas ? fotosArmazenadas.length : 'undefined');
        
        if (inputArquivo && fotosArmazenadas && fotosArmazenadas.length > 0) {
            try {
                const dt = new DataTransfer();
                
                // Adicionar todas as fotos armazenadas ao DataTransfer
                fotosArmazenadas.forEach(foto => {
                    if (foto && foto.file) {
                        dt.items.add(foto.file);
                    }
                });
                
                // Atualizar o input principal com todos os arquivos
                inputArquivo.files = dt.files;
                
                console.log(`✅ ${fotosArmazenadas.length} foto(s) preparada(s) para envio`);
                console.log('✅ inputArquivo.files.length:', inputArquivo.files.length);
            } catch (error) {
                console.error('❌ Erro ao combinar fotos:', error);
            }
        } else {
            console.log('ℹ️ Nenhuma foto para enviar');
            if (!inputArquivo) {
                console.error('❌ Input de fotos não encontrado!');
            }
        }
    };
    
    // Fechar modal ao clicar fora
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('modal-foto');
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    fecharModalFoto();
                }
            });
        }
        
        // Fechar modal quando os inputs mudarem (onchange inline já chama previewPhotos)
        const inputFotos = document.getElementById('fotos');
        const inputCamera = document.getElementById('fotos-camera');
        
        if (inputFotos && !inputFotos.dataset.listenerAdicionado) {
            console.log('✅ Adicionando listener para fechar modal no inputFotos');
            inputFotos.addEventListener('change', function(e) {
                console.log('📁 Evento change disparado no inputFotos', e.target.files);
                fecharModalFoto(); // Fechar modal após seleção
            });
            inputFotos.dataset.listenerAdicionado = 'true';
        }
        
        if (inputCamera && !inputCamera.dataset.listenerAdicionado) {
            console.log('✅ Adicionando listener para fechar modal no inputCamera');
            inputCamera.addEventListener('change', function(e) {
                console.log('📷 Evento change disparado no inputCamera', e.target.files);
                fecharModalFoto(); // Fechar modal após seleção
            });
            inputCamera.dataset.listenerAdicionado = 'true';
        }
    });
    
    window.previewPhotos = function(input) {
        const preview = document.getElementById('fotos-preview');
        const loadingOverlay = document.getElementById('fotos-loading');
        const inputArquivo = document.getElementById('fotos');
        const inputCamera = document.getElementById('fotos-camera');
        
        // Combinar arquivos de ambos os inputs
        let allFiles = [];
        if (inputArquivo && inputArquivo.files) {
            allFiles = Array.from(inputArquivo.files);
        }
        if (inputCamera && inputCamera.files) {
            allFiles = allFiles.concat(Array.from(inputCamera.files));
        }
        
        // Filtrar apenas imagens
        const todasFotos = allFiles.filter(f => f.type.startsWith('image/'));
        
        // Detectar apenas as NOVAS fotos (comparando nome, tamanho e data de modificação)
        const novasFotos = todasFotos.filter(novaFoto => {
            // Verificar se esta foto já está armazenada
            return !fotosArmazenadas.some(fotoArmazenada => {
                return fotoArmazenada.file.name === novaFoto.name && 
                       fotoArmazenada.file.size === novaFoto.size &&
                       fotoArmazenada.file.lastModified === novaFoto.lastModified;
            });
        });
        
        if (novasFotos.length > 0) {
            // Verificar limite de 5 fotos
            const totalAposAdicao = fotosArmazenadas.length + novasFotos.length;
            const fotosParaAdicionar = totalAposAdicao > 5 
                ? novasFotos.slice(0, 5 - fotosArmazenadas.length)
                : novasFotos;
            
            if (fotosParaAdicionar.length === 0) {
                alert('Você já adicionou o máximo de 5 fotos');
                return;
            }
            
            preview.classList.remove('hidden');
            
            let fotosProcessadas = 0;
            const totalFotos = fotosParaAdicionar.length;
            let loadingTimeout = null;
            let loadingMostrado = false;
            
            // Mostrar loading apenas se demorar mais de 500ms
            loadingTimeout = setTimeout(() => {
                if (fotosProcessadas < totalFotos && loadingOverlay) {
                    loadingOverlay.classList.remove('hidden');
                    loadingMostrado = true;
                    console.log('Loading mostrado (demorou mais de 500ms)');
                }
            }, 500);
            
            // Função para verificar se todas as fotos foram processadas
            const verificarConclusao = function() {
                if (fotosProcessadas === totalFotos) {
                    // Cancelar timeout se ainda não foi executado
                    if (loadingTimeout) {
                        clearTimeout(loadingTimeout);
                    }
                    
                    // Esconder loading se foi mostrado
                    if (loadingMostrado && loadingOverlay) {
                        loadingOverlay.classList.add('hidden');
                        console.log('Loading escondido');
                    }
                    
                    // Limpar inputs após processar para permitir adicionar mais fotos
                    if (input === inputArquivo && inputArquivo) {
                        inputArquivo.value = '';
                    }
                    if (input === inputCamera && inputCamera) {
                        inputCamera.value = '';
                    }
                }
            };
            
            fotosParaAdicionar.forEach((file) => {
                // Criar ID único para a foto
                const fotoId = Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                
                // Armazenar referência do arquivo
                fotosArmazenadas.push({
                    id: fotoId,
                    file: file,
                    input: input === inputArquivo ? 'arquivo' : 'camera'
                });
                
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const div = document.createElement('div');
                        div.className = 'relative';
                    div.setAttribute('data-foto-id', fotoId);
                        div.innerHTML = `
                            <img src="${e.target.result}" class="w-full h-24 object-cover rounded-lg border border-gray-200">
                        <button type="button" onclick="removePhoto('${fotoId}')" 
                                    class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600">
                                ×
                            </button>
                        `;
                        preview.appendChild(div);
                    
                    fotosProcessadas++;
                    verificarConclusao();
                };
                reader.onerror = function() {
                    // Remover da lista se houver erro
                    fotosArmazenadas = fotosArmazenadas.filter(f => f.id !== fotoId);
                    fotosProcessadas++;
                    verificarConclusao();
                    };
                    reader.readAsDataURL(file);
            });
        } else {
            // Se não houver novas fotos, verificar se ainda há fotos no preview
            if (fotosArmazenadas.length === 0) {
            preview.classList.add('hidden');
            }
            if (loadingOverlay) {
                loadingOverlay.classList.add('hidden');
            }
        }
    };
    
    window.removePhoto = function(fotoId) {
        // Remover da lista de fotos armazenadas
        const fotoIndex = fotosArmazenadas.findIndex(f => f.id === fotoId);
        if (fotoIndex === -1) return;
        
        fotosArmazenadas.splice(fotoIndex, 1);
        
        // Remover do DOM
        const fotoElement = document.querySelector(`[data-foto-id="${fotoId}"]`);
        if (fotoElement) {
            fotoElement.remove();
        }
        
        // Atualizar os inputs de arquivo (limpar para permitir adicionar novas)
        const input = document.getElementById('fotos');
        const inputCamera = document.getElementById('fotos-camera');
        
        if (input) {
            input.value = '';
        }
        if (inputCamera) {
            inputCamera.value = '';
        }
        
        // Se não houver mais fotos, esconder preview
        if (fotosArmazenadas.length === 0) {
            const preview = document.getElementById('fotos-preview');
            if (preview) {
                preview.classList.add('hidden');
            }
        }
    };
    
    // Sistema de agendamento para emergencial
    const tipoAtendimentoRadios = document.querySelectorAll('.tipo-atendimento-radio');
    const secaoAgendamentoEmergencial = document.getElementById('secao-agendamento-emergencial');
    const boxAtendimentoEmergencial = document.getElementById('box-atendimento-emergencial');
    const btnContinuar = document.getElementById('btn-continuar');
    
    // Função para atualizar visual e exibição baseado no tipo selecionado
    function atualizarTipoAtendimento(tipoSelecionado) {
        console.log('🔄 Atualizando tipo de atendimento para:', tipoSelecionado);
        const radio = document.querySelector(`.tipo-atendimento-radio[value="${tipoSelecionado}"]`);
        if (!radio) {
            console.error('❌ Radio não encontrado para:', tipoSelecionado);
            return;
        }
        
        // Garantir que o radio está marcado
        radio.checked = true;
        
        const card = radio.closest('label')?.querySelector('.tipo-atendimento-card');
        const check = card ? card.querySelector('.tipo-atendimento-check') : null;
        
        console.log('📦 Card encontrado:', card ? 'Sim' : 'Não');
        console.log('✅ Check encontrado:', check ? 'Sim' : 'Não');
        
        // Atualizar visual de todos os cards (limpar seleção anterior)
        document.querySelectorAll('.tipo-atendimento-card').forEach(c => {
            c.classList.remove('border-green-500', 'bg-green-50', 'border-blue-500', 'bg-blue-50');
            c.classList.add('border-gray-200', 'bg-white');
            const chk = c.querySelector('.tipo-atendimento-check');
            if (chk) {
                chk.classList.remove('bg-green-600', 'border-green-600', 'bg-blue-500', 'border-blue-500', 'flex', 'items-center', 'justify-center');
                chk.classList.add('border-gray-300', 'rounded-full');
                chk.innerHTML = '';
                chk.style.backgroundColor = '';
                chk.style.display = 'block';
            }
        });
        
        // Atualizar card selecionado
        if (tipoSelecionado === '120_minutos') {
            if (card) {
                card.classList.remove('border-gray-200', 'bg-white');
                card.classList.add('border-green-500', 'bg-green-50');
                console.log('✅ Card 120 minutos atualizado');
            }
            if (check) {
                check.classList.remove('border-gray-300');
                check.classList.add('bg-green-600', 'border-green-600', 'flex', 'items-center', 'justify-center', 'rounded-full');
                check.innerHTML = '<i class="fas fa-check text-white text-xs"></i>';
                check.style.display = 'flex';
                console.log('✅ Check 120 minutos atualizado');
            }
            // Mostrar box de atendimento emergencial
            if (boxAtendimentoEmergencial) {
                boxAtendimentoEmergencial.classList.remove('hidden');
                boxAtendimentoEmergencial.style.display = 'block';
            }
            // Ocultar seção de agendamento
            if (secaoAgendamentoEmergencial) {
                secaoAgendamentoEmergencial.classList.add('hidden');
                secaoAgendamentoEmergencial.style.display = 'none';
            }
            // Habilitar botão continuar
            if (btnContinuar) {
                btnContinuar.disabled = false;
                btnContinuar.classList.remove('bg-gray-400', 'cursor-not-allowed');
                btnContinuar.classList.add('bg-green-600', 'hover:bg-green-700');
            }
        } else if (tipoSelecionado === 'agendar') {
            if (card) {
                card.classList.remove('border-gray-200', 'bg-white');
                card.classList.add('border-blue-500', 'bg-blue-50');
                console.log('✅ Card Agendar atualizado');
            }
            if (check) {
                check.classList.remove('border-gray-300');
                check.classList.add('bg-blue-500', 'border-blue-500', 'flex', 'items-center', 'justify-center', 'rounded-full');
                check.innerHTML = '<i class="fas fa-check text-white text-xs"></i>';
                check.style.display = 'flex';
                console.log('✅ Check Agendar atualizado');
            }
            // Ocultar box de atendimento emergencial
            if (boxAtendimentoEmergencial) {
                boxAtendimentoEmergencial.classList.add('hidden');
                boxAtendimentoEmergencial.style.display = 'none';
            }
            // Mostrar seção de agendamento
            if (secaoAgendamentoEmergencial) {
                secaoAgendamentoEmergencial.classList.remove('hidden');
                secaoAgendamentoEmergencial.style.display = 'block';
                console.log('✅ Seção de agendamento emergencial exibida');
            } else {
                console.error('❌ Seção de agendamento emergencial não encontrada!');
                const secao = document.getElementById('secao-agendamento-emergencial');
                if (secao) {
                    secao.classList.remove('hidden');
                    secao.style.display = 'block';
                }
            }
            // Desabilitar botão continuar até selecionar horários
            if (btnContinuar) {
                btnContinuar.disabled = true;
                btnContinuar.classList.add('bg-gray-400', 'cursor-not-allowed');
                btnContinuar.classList.remove('bg-green-600', 'hover:bg-green-700');
            }
        }
    }
    
    // Controlar exibição do calendário quando selecionar tipo de atendimento
    if (tipoAtendimentoRadios.length > 0) {
        tipoAtendimentoRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                atualizarTipoAtendimento(this.value);
            });
        });
        
        // Adicionar evento de clique nos labels e cards
        document.querySelectorAll('label').forEach(label => {
            if (label.querySelector('.tipo-atendimento-card')) {
                label.addEventListener('click', function(e) {
                    const radio = this.querySelector('.tipo-atendimento-radio');
                    if (radio) {
                        console.log('🖱️ Label clicado, selecionando:', radio.value);
                        // Forçar seleção do radio
                        radio.checked = true;
                        // Atualizar visual imediatamente
                        atualizarTipoAtendimento(radio.value);
                        // Disparar evento change
                        radio.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
            }
        });
        
        // Adicionar evento diretamente nos cards também (para garantir que funcione)
        document.querySelectorAll('.tipo-atendimento-card').forEach(card => {
            card.style.cursor = 'pointer';
            card.addEventListener('click', function(e) {
                const label = this.closest('label');
                if (!label) return;
                
                const radio = label.querySelector('.tipo-atendimento-radio');
                if (radio) {
                    console.log('🖱️ Card clicado, selecionando:', radio.value);
                    // Forçar seleção do radio
                    radio.checked = true;
                    // Atualizar visual imediatamente
                    atualizarTipoAtendimento(radio.value);
                    // Disparar evento change
                    radio.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        });
        
        // Verificar se a seção de agendamento existe
        console.log('🔍 Verificando seção de agendamento emergencial:', secaoAgendamentoEmergencial);
        if (secaoAgendamentoEmergencial) {
            console.log('✅ Seção encontrada no DOM');
        } else {
            console.error('❌ Seção de agendamento emergencial NÃO encontrada no DOM!');
        }
        
        // Inicializar estado inicial - garantir que a seção esteja oculta se "120 minutos" estiver selecionado
        const radio120Minutos = document.getElementById('opcao_120_minutos');
        const radioAgendar = document.getElementById('opcao_agendar');
        
        if (radio120Minutos && radio120Minutos.checked) {
            // Se "120 minutos" está selecionado, mostrar box de emergência e ocultar seção de agendamento
            if (boxAtendimentoEmergencial) {
                boxAtendimentoEmergencial.classList.remove('hidden');
                boxAtendimentoEmergencial.style.display = 'block';
            }
            if (secaoAgendamentoEmergencial) {
                secaoAgendamentoEmergencial.classList.add('hidden');
                secaoAgendamentoEmergencial.style.display = 'none';
            }
            atualizarTipoAtendimento('120_minutos');
        } else if (radioAgendar && radioAgendar.checked) {
            // Se "Agendar" está selecionado, ocultar box de emergência e mostrar seção de agendamento
            if (boxAtendimentoEmergencial) {
                boxAtendimentoEmergencial.classList.add('hidden');
                boxAtendimentoEmergencial.style.display = 'none';
            }
            if (secaoAgendamentoEmergencial) {
                secaoAgendamentoEmergencial.classList.remove('hidden');
                secaoAgendamentoEmergencial.style.display = 'block';
            }
            atualizarTipoAtendimento('agendar');
        } else {
            // Por padrão, se nenhum estiver selecionado, ocultar ambos
            if (boxAtendimentoEmergencial) {
                boxAtendimentoEmergencial.classList.add('hidden');
                boxAtendimentoEmergencial.style.display = 'none';
            }
            if (secaoAgendamentoEmergencial) {
                secaoAgendamentoEmergencial.classList.add('hidden');
                secaoAgendamentoEmergencial.style.display = 'none';
            }
        }
    } else {
        console.warn('⚠️ Nenhum radio de tipo de atendimento encontrado');
    }
    
    // Sistema de agendamento para emergencial (quando selecionar "Agendar")
    const horarioRadiosEmergencial = document.querySelectorAll('.horario-radio-emergencial');
    const horarioCardsEmergencial = document.querySelectorAll('.horario-card-emergencial');
    const horariosSelecionadosEmergencial = document.getElementById('horarios-selecionados-emergencial');
    const listaHorariosEmergencial = document.getElementById('lista-horarios-emergencial');
    const contadorHorariosEmergencial = document.getElementById('contador-horarios-emergencial');
    
    let horariosEscolhidosEmergencial = [];
    
    horarioRadiosEmergencial.forEach(radio => {
        radio.addEventListener('change', function() {
            const data = document.getElementById('data_selecionada_emergencial')?.value;
            const horario = this.value;
            
            if (data && horario) {
                const horarioCompleto = `${formatarData(data)} - ${horario}`;
                
                if (!horariosEscolhidosEmergencial.includes(horarioCompleto) && horariosEscolhidosEmergencial.length < 3) {
                    horariosEscolhidosEmergencial.push(horarioCompleto);
                    horariosEscolhidosEmergencial = ordenarHorarios(horariosEscolhidosEmergencial);
                    atualizarListaHorariosEmergencial();
                    
                    // Atualizar visual do card selecionado
                    const label = this.closest('label');
                    const card = label ? label.querySelector('.horario-card-emergencial') : null;
                    if (card) {
                        // Remover seleção de todos os cards primeiro
                        horarioCardsEmergencial.forEach(c => {
                            c.classList.remove('border-green-500', 'bg-green-50');
                            c.classList.add('border-gray-200', 'bg-white');
                        });
                        // Destacar o card selecionado
                        card.classList.remove('border-gray-200', 'bg-white');
                        card.classList.add('border-green-500', 'bg-green-50');
                    }
                    
                    // Resetar botão de data após selecionar horário
                    resetarBotaoDataEmergencial();
                }
            }
        });
    });
    
    // Click no card de horário emergencial também seleciona o radio
    horarioCardsEmergencial.forEach(card => {
        card.addEventListener('click', function() {
            const label = this.closest('label');
            const radio = label ? label.querySelector('.horario-radio-emergencial') : null;
            if (radio) {
                radio.checked = true;
                radio.dispatchEvent(new Event('change'));
            }
        });
    });
    
    function atualizarListaHorariosEmergencial() {
        if (horariosEscolhidosEmergencial.length > 0) {
            if (horariosSelecionadosEmergencial) {
                horariosSelecionadosEmergencial.classList.remove('hidden');
            }
            if (contadorHorariosEmergencial) {
                contadorHorariosEmergencial.textContent = horariosEscolhidosEmergencial.length;
            }
            
            if (listaHorariosEmergencial) {
                listaHorariosEmergencial.innerHTML = '';
                horariosEscolhidosEmergencial.forEach((horario, index) => {
                    const div = document.createElement('div');
                    div.className = 'flex items-center justify-between bg-green-50 border border-green-200 rounded-lg p-3';
                    div.innerHTML = `
                        <div class="flex items-center">
                            <i class="fas fa-clock text-green-600 mr-2"></i>
                            <span class="text-sm text-green-800">${horario}</span>
                        </div>
                        <button type="button" onclick="removerHorarioEmergencial(${index})" 
                                class="text-red-500 hover:text-red-700">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                    listaHorariosEmergencial.appendChild(div);
                });
            }
            
            // Habilitar botão continuar se tiver pelo menos 1 horário
            if (btnContinuar && horariosEscolhidosEmergencial.length > 0) {
                btnContinuar.disabled = false;
                btnContinuar.classList.remove('bg-gray-400', 'cursor-not-allowed');
                btnContinuar.classList.add('bg-green-600', 'hover:bg-green-700');
            }
        } else {
            if (horariosSelecionadosEmergencial) {
                horariosSelecionadosEmergencial.classList.add('hidden');
            }
            // Só desabilitar se estiver na opção "Agendar"
            const opcaoAgendar = document.getElementById('opcao_agendar');
            if (btnContinuar && opcaoAgendar && opcaoAgendar.checked) {
                btnContinuar.disabled = true;
                btnContinuar.classList.add('bg-gray-400', 'cursor-not-allowed');
                btnContinuar.classList.remove('bg-green-600', 'hover:bg-green-700');
            }
        }
    }
    
    window.removerHorarioEmergencial = function(index) {
        horariosEscolhidosEmergencial.splice(index, 1);
        atualizarListaHorariosEmergencial();
    };
    
    // Sistema de agendamento (normal - não emergencial)
    const horarioRadios = document.querySelectorAll('.horario-radio');
    const horarioCards = document.querySelectorAll('.horario-card');
    const horariosSelecionados = document.getElementById('horarios-selecionados');
    const listaHorarios = document.getElementById('lista-horarios');
    const contadorHorarios = document.getElementById('contador-horarios');
    
    let horariosEscolhidos = [];
    
    horarioRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            const data = document.getElementById('data_selecionada').value;
            const horario = this.value;
            
            if (data && horario) {
                const horarioCompleto = `${formatarData(data)} - ${horario}`;
                
                if (!horariosEscolhidos.includes(horarioCompleto) && horariosEscolhidos.length < 3) {
                    horariosEscolhidos.push(horarioCompleto);
                    horariosEscolhidos = ordenarHorarios(horariosEscolhidos);
                    atualizarListaHorarios();
                    
                    // Resetar botão de data após selecionar horário
                    resetarBotaoData();
                }
            }
        });
    });

    // Click no card de horário também seleciona o radio
    horarioCards.forEach(card => {
        card.addEventListener('click', function() {
            // O radio está no label pai, não dentro do card
            const label = this.closest('label');
            const radio = label ? label.querySelector('.horario-radio') : null;
            if (radio) {
                console.log('⏰ Horário clicado:', radio.value);
                radio.checked = true;
                radio.dispatchEvent(new Event('change'));
            }
        });
    });
    
    function atualizarListaHorarios() {
        if (horariosEscolhidos.length > 0) {
            horariosSelecionados.classList.remove('hidden');
            contadorHorarios.textContent = horariosEscolhidos.length;
            
            listaHorarios.innerHTML = '';
            horariosEscolhidos.forEach((horario, index) => {
                const div = document.createElement('div');
                div.className = 'flex items-center justify-between bg-green-50 border border-green-200 rounded-lg p-3';
                div.innerHTML = `
                    <div class="flex items-center">
                        <i class="fas fa-clock text-green-600 mr-2"></i>
                        <span class="text-sm text-green-800">${horario}</span>
                    </div>
                    <button type="button" onclick="removerHorario(${index})" 
                            class="text-red-500 hover:text-red-700">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                listaHorarios.appendChild(div);
            });
            
            // Habilitar botão continuar se tiver pelo menos 1 horário
            if (horariosEscolhidos.length > 0) {
                btnContinuar.disabled = false;
                btnContinuar.classList.remove('bg-gray-400', 'cursor-not-allowed');
                btnContinuar.classList.add('bg-green-600', 'hover:bg-green-700');
            }
        } else {
            horariosSelecionados.classList.add('hidden');
            btnContinuar.disabled = true;
            btnContinuar.classList.add('bg-gray-400', 'cursor-not-allowed');
            btnContinuar.classList.remove('bg-green-600', 'hover:bg-green-700');
        }
    }
    
    window.removerHorario = function(index) {
        horariosEscolhidos.splice(index, 1);
        atualizarListaHorarios();
    };
    
    function formatarData(data) {
        const [ano, mes, dia] = data.split('-');
        return `${dia}/${mes}/${ano}`;
    }
    
    // Função para ordenar horários por data (crescente) e hora (crescente)
    function ordenarHorarios(horarios) {
        return horarios.sort((a, b) => {
            // Formato: "DD/MM/YYYY - HH:MM-HH:MM"
            const [dataA, faixaA] = a.split(' - ');
            const [dataB, faixaB] = b.split(' - ');
            
            // Converter datas para formato comparável
            const [diaA, mesA, anoA] = dataA.split('/');
            const [diaB, mesB, anoB] = dataB.split('/');
            
            const dateA = new Date(anoA, mesA - 1, diaA);
            const dateB = new Date(anoB, mesB - 1, diaB);
            
            // Comparar datas primeiro
            if (dateA.getTime() !== dateB.getTime()) {
                return dateA.getTime() - dateB.getTime();
            }
            
            // Se datas iguais, comparar horários
            const horaInicioA = faixaA ? faixaA.split('-')[0] : '00:00';
            const horaInicioB = faixaB ? faixaB.split('-')[0] : '00:00';
            
            return horaInicioA.localeCompare(horaInicioB);
        });
    }
    
    // Salvar horários no formulário antes de enviar
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Verificar se é emergencial e qual opção foi selecionada
            const tipoAtendimento = document.querySelector('.tipo-atendimento-radio:checked')?.value;
            
            if (tipoAtendimento === '120_minutos') {
                // Enviar campo indicando atendimento em 120 minutos
                const inputTipo = document.createElement('input');
                inputTipo.type = 'hidden';
                inputTipo.name = 'tipo_atendimento_emergencial';
                inputTipo.value = '120_minutos';
                form.appendChild(inputTipo);
            } else if (tipoAtendimento === 'agendar') {
                // Converter horários emergenciais: "29/10/2025 - 08:00-11:00" → "2025-10-29 08:00:00-11:00:00"
                const horariosFormatados = horariosEscolhidosEmergencial.map(horario => {
                    const [dataStr, faixaHorario] = horario.split(' - ');
                    const [dia, mes, ano] = dataStr.split('/');
                    const [horarioInicial, horarioFinal] = faixaHorario.split('-');
                    // Formato: "2025-10-29 08:00:00-11:00:00"
                    return `${ano}-${mes}-${dia} ${horarioInicial.trim()}:00-${horarioFinal.trim()}:00`;
                });
                
                // Enviar como JSON
                const inputHorarios = document.createElement('input');
                inputHorarios.type = 'hidden';
                inputHorarios.name = 'horarios_opcoes';
                inputHorarios.value = JSON.stringify(horariosFormatados);
                form.appendChild(inputHorarios);
                
                const inputTipo = document.createElement('input');
                inputTipo.type = 'hidden';
                inputTipo.name = 'tipo_atendimento_emergencial';
                inputTipo.value = 'agendar';
                form.appendChild(inputTipo);
            } else {
                // Normal (não emergencial): Converter: "29/10/2025 - 08:00-11:00" → "2025-10-29 08:00:00-11:00:00"
                const horariosFormatados = horariosEscolhidos.map(horario => {
                    const [dataStr, faixaHorario] = horario.split(' - ');
                    const [dia, mes, ano] = dataStr.split('/');
                    const [horarioInicial, horarioFinal] = faixaHorario.split('-');
                    // Formato: "2025-10-29 08:00:00-11:00:00"
                    return `${ano}-${mes}-${dia} ${horarioInicial.trim()}:00-${horarioFinal.trim()}:00`;
                });
                
                // Enviar como JSON
                const inputHorarios = document.createElement('input');
                inputHorarios.type = 'hidden';
                inputHorarios.name = 'horarios_opcoes';
                inputHorarios.value = JSON.stringify(horariosFormatados);
                form.appendChild(inputHorarios);
            }
        });
    }
    
    // Controlar visibilidade do campo "Tipo de Imóvel" baseado em "Finalidade da Locação"
    const finalidadeSelect = document.getElementById('finalidade_locacao');
    const tipoImovelContainer = document.getElementById('tipo_imovel_container');
    
    if (finalidadeSelect && tipoImovelContainer) {
        // Criar campo hidden para tipo_imovel quando for Comercial
        let hiddenTipoImovel = document.getElementById('hidden_tipo_imovel');
        if (!hiddenTipoImovel) {
            hiddenTipoImovel = document.createElement('input');
            hiddenTipoImovel.type = 'hidden';
            hiddenTipoImovel.name = 'tipo_imovel';
            hiddenTipoImovel.id = 'hidden_tipo_imovel';
            tipoImovelContainer.parentNode.insertBefore(hiddenTipoImovel, tipoImovelContainer);
        }
        
        function toggleTipoImovel() {
            if (finalidadeSelect.value === 'COMERCIAL') {
                tipoImovelContainer.style.display = 'none';
                // Limpar seleção dos radio buttons e desabilitar para não enviar
                const radioButtons = tipoImovelContainer.querySelectorAll('input[type="radio"]');
                radioButtons.forEach(radio => {
                    radio.checked = false;
                    radio.removeAttribute('required');
                    radio.disabled = true; // Desabilitar para não enviar
                });
                // Definir valor padrão para Comercial no campo hidden
                hiddenTipoImovel.value = 'COMERCIAL';
                hiddenTipoImovel.disabled = false; // Garantir que está habilitado
            } else {
                tipoImovelContainer.style.display = 'block';
                // Habilitar radio buttons novamente
                const radioButtons = tipoImovelContainer.querySelectorAll('input[type="radio"]');
                radioButtons.forEach(radio => {
                    radio.disabled = false;
                });
                // Restaurar seleção padrão (Casa)
                const radioCasa = tipoImovelContainer.querySelector('input[value="CASA"]');
                if (radioCasa) {
                    radioCasa.checked = true;
                }
                // Desabilitar o campo hidden para não enviar (os radio buttons vão enviar o valor)
                hiddenTipoImovel.disabled = true;
                hiddenTipoImovel.value = '';
            }
        }
        
        // Executar na carga da página
        toggleTipoImovel();
        
        // Executar quando mudar a seleção
        finalidadeSelect.addEventListener('change', toggleTipoImovel);
        
        // Garantir que quando os radio buttons mudarem, o hidden seja limpo
        const radioButtons = tipoImovelContainer.querySelectorAll('input[type="radio"]');
        radioButtons.forEach(radio => {
            radio.addEventListener('change', function() {
                if (finalidadeSelect.value === 'RESIDENCIAL') {
                    hiddenTipoImovel.value = '';
                }
            });
        });
    }
});
</script>

<!-- Modal de Condições Gerais -->
<div id="modal-condicoes-gerais" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-2xl w-full max-h-[80vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900" id="modal-condicoes-titulo"></h3>
            <button onclick="fecharModalCondicoesGerais()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="px-6 py-4">
            <div class="text-sm text-gray-700" id="modal-condicoes-conteudo"></div>
        </div>
        <div class="border-t border-gray-200 px-6 py-4">
            <button onclick="fecharModalCondicoesGerais()" 
                    class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                Fechar
            </button>
        </div>
    </div>
</div>

<!-- Modal de Limite Atingido -->
<div id="modal-limite-atingido" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
        <div class="p-6">
            <!-- Header do Modal -->
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">Limite Atingido</h3>
                </div>
                <button type="button" id="fechar-modal-limite" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <!-- Conteúdo do Modal -->
            <div class="mb-6">
                <p class="text-sm text-gray-700 mb-4" id="modal-limite-mensagem">
                    <!-- Mensagem será inserida aqui via JavaScript -->
                </p>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                    <p class="text-xs text-yellow-800">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Importante:</strong> O limite é calculado com base nas solicitações dos últimos 12 meses para esta categoria.
                    </p>
                </div>
            </div>
            
            <!-- Botão de Fechar -->
            <div class="flex justify-end">
                <button type="button" id="btn-fechar-modal-limite" class="px-6 py-2 bg-gray-600 text-white font-medium rounded-lg hover:bg-gray-700 transition-colors">
                    Entendi
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Função para fechar modal
function fecharModalLimite() {
    const modal = document.getElementById('modal-limite-atingido');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
    }
}

// Event listeners para fechar modal (aguardar DOM estar pronto)
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('fechar-modal-limite')?.addEventListener('click', fecharModalLimite);
        document.getElementById('btn-fechar-modal-limite')?.addEventListener('click', fecharModalLimite);
        
        // Fechar modal ao clicar fora dele
        const modal = document.getElementById('modal-limite-atingido');
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    fecharModalLimite();
                }
            });
        }
    });
} else {
    document.getElementById('fechar-modal-limite')?.addEventListener('click', fecharModalLimite);
    document.getElementById('btn-fechar-modal-limite')?.addEventListener('click', fecharModalLimite);
    
    // Fechar modal ao clicar fora dele
    const modal = document.getElementById('modal-limite-atingido');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModalLimite();
            }
        });
    }
}

// Fechar modal com ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('modal-limite-atingido');
        if (modal && !modal.classList.contains('hidden')) {
            fecharModalLimite();
        }
    }
});

// === Toggle Resumo das Etapas Anteriores ===
function toggleResumoEtapas() {
    const conteudo = document.getElementById('resumo-conteudo');
    const chevron = document.getElementById('resumo-chevron');
    
    if (conteudo && chevron) {
        conteudo.classList.toggle('hidden');
        chevron.classList.toggle('rotate-180');
    }
}

// === Modal de Condições Gerais ===
// Função para decodificar base64 preservando UTF-8
function base64DecodeUTF8(str) {
    try {
        // Decodificar base64
        const decoded = atob(str);
        // Converter bytes para string UTF-8
        const bytes = new Uint8Array(decoded.length);
        for (let i = 0; i < decoded.length; i++) {
            bytes[i] = decoded.charCodeAt(i);
        }
        // Usar TextDecoder para converter para UTF-8
        const decoder = new TextDecoder('utf-8');
        return decoder.decode(bytes);
    } catch (e) {
        // Se TextDecoder não estiver disponível, usar método alternativo
        try {
            const decoded = atob(str);
            return decodeURIComponent(
                decoded.split('').map(function(c) {
                    return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
                }).join('')
            );
        } catch (e2) {
            // Última tentativa: decodificação simples
            try {
                return atob(str);
            } catch (e3) {
                return str;
            }
        }
    }
}

window.abrirModalCondicoesGerais = function(nome, condicoesGerais) {
    document.getElementById('modal-condicoes-titulo').textContent = nome;
    const conteudo = document.getElementById('modal-condicoes-conteudo');
    let html = condicoesGerais;
    
    // Se for base64, decodificar preservando UTF-8
    if (typeof condicoesGerais === 'string' && condicoesGerais.length > 0) {
        try {
            // Tentar decodificar base64 com suporte UTF-8
            html = base64DecodeUTF8(condicoesGerais);
        } catch (e) {
            // Se não for base64, usar como está
            html = condicoesGerais;
        }
    }
    
    conteudo.innerHTML = html || '';
    document.getElementById('modal-condicoes-gerais').classList.remove('hidden');
};

window.fecharModalCondicoesGerais = function() {
    document.getElementById('modal-condicoes-gerais').classList.add('hidden');
};

// Event listener para botões de condições gerais
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-condicoes-gerais');
    if (btn) {
        e.stopPropagation();
        const nome = btn.getAttribute('data-nome');
        const condicoes = btn.getAttribute('data-condicoes');
        if (nome && condicoes) {
            abrirModalCondicoesGerais(nome, condicoes);
        }
    }
});

// Fechar modal de condições gerais ao clicar fora dele
document.addEventListener('click', function(e) {
    const modal = document.getElementById('modal-condicoes-gerais');
    if (e.target === modal) {
        fecharModalCondicoesGerais();
    }
});

// Fechar modal de condições gerais com ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('modal-condicoes-gerais');
        if (modal && !modal.classList.contains('hidden')) {
            fecharModalCondicoesGerais();
        }
    }
});
</script>

<?php
$content = ob_get_clean();
include 'app/Views/layouts/locatario.php';
?>