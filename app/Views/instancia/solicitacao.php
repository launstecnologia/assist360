<?php
/**
 * View: Detalhes da Solicitação na Instância (com autenticação)
 */
$title = 'Detalhes da Solicitação - ' . ($imobiliaria['nome'] ?? 'Assistência 360°');
$currentPage = 'instancia-solicitacao';
ob_start();

// Preparar dados da solicitação
$solicitacao = $solicitacao ?? [];
$fotos = $solicitacao['fotos'] ?? [];
$historicoStatus = $solicitacao['historico_status'] ?? [];

// Função para processar observações e exibir imagens dos anexos
function processarObservacoesComImagens($texto) {
    if (empty($texto)) {
        return '';
    }
    
    // Escapar HTML primeiro
    $texto = htmlspecialchars($texto);
    
    // Padrão para encontrar "Anexos: " seguido de links
    // Exemplo: "Anexos: uploads/solicitacoes/123/anexos/anexo_123.jpg, uploads/solicitacoes/123/anexos/anexo_456.png"
    $texto = preg_replace_callback(
        '/(Anexos:\s*)([^\n]+)/i',
        function($matches) {
            $prefixo = $matches[1];
            $anexos = trim($matches[2]);
            
            // Separar anexos por vírgula
            $listaAnexos = preg_split('/,\s*/', $anexos);
            $html = $prefixo . '<br>';
            $html .= '<div class="mt-2 grid grid-cols-2 md:grid-cols-3 gap-3">';
            
            foreach ($listaAnexos as $anexo) {
                $anexo = trim($anexo);
                if (empty($anexo)) continue;
                
                // Construir URL completa
                $urlAnexo = url('Public/' . $anexo);
                
                // Verificar se é imagem (extensões comuns)
                $extensao = strtolower(pathinfo($anexo, PATHINFO_EXTENSION));
                $ehImagem = in_array($extensao, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                
                if ($ehImagem) {
                    // Exibir como imagem
                    $nomeArquivo = htmlspecialchars(basename($anexo));
                    $html .= '<div class="relative">';
                    $html .= '<img src="' . htmlspecialchars($urlAnexo) . '" ';
                    $html .= 'alt="' . $nomeArquivo . '" ';
                    $html .= 'class="w-full h-32 object-cover rounded-lg cursor-pointer hover:opacity-75 transition-opacity" ';
                    $html .= 'onclick="abrirModalFoto(\'' . htmlspecialchars($urlAnexo) . '\')" ';
                    $html .= 'onerror="this.parentElement.style.display=\'none\';">';
                    $html .= '</div>';
                } else {
                    // Exibir como link para PDF/Word
                    $nomeArquivo = htmlspecialchars(basename($anexo));
                    $html .= '<div class="flex items-center p-2 bg-gray-100 rounded-lg">';
                    $html .= '<i class="fas fa-file-alt text-gray-600 mr-2"></i>';
                    $html .= '<a href="' . htmlspecialchars($urlAnexo) . '" target="_blank" class="text-sm text-blue-600 hover:text-blue-800 truncate">';
                    $html .= $nomeArquivo;
                    $html .= '</a>';
                    $html .= '</div>';
                }
            }
            
            $html .= '</div>';
            return $html;
        },
        $texto
    );
    
    // Converter quebras de linha para <br>
    $texto = nl2br($texto);
    
    return $texto;
}
?>

<!-- Header -->
<div class="mb-8">
    <!-- Botão Voltar -->
    <a href="<?= url($imobiliaria['instancia'] . '/solicitacoes') ?>" 
       class="inline-flex items-center text-gray-600 hover:text-gray-900 mb-4 transition-colors">
        <i class="fas fa-arrow-left mr-2"></i>
        <span class="text-sm font-medium">Voltar para Solicitações</span>
    </a>
    
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-file-alt mr-2"></i>
                Detalhes da Solicitação
            </h1>
            <p class="text-gray-600 mt-1">
                Protocolo: <?= htmlspecialchars($solicitacao['protocolo_seguradora'] ?? ($solicitacao['numero_solicitacao'] ?? 'KSS' . $solicitacao['id'])) ?>
                <?php if (!empty($solicitacao['numero_contrato'])): ?>
                    | Contrato: <?= htmlspecialchars($solicitacao['numero_contrato']) ?>
                <?php endif; ?>
            </p>
        </div>
    </div>
</div>

<!-- STATUS E TIMELINE -->
<div class="bg-white rounded-lg p-5 shadow-sm mb-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">
        <i class="fas fa-info-circle mr-2 text-blue-600"></i>
        Status e Timeline
    </h3>
    
    <div class="space-y-4">
        <!-- Status Atual -->
        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
            <div class="flex items-center gap-3 mb-2">
                <span class="text-sm font-medium text-gray-700">Status Atual:</span>
                <span class="status-badge status-<?= strtolower(str_replace([' ', '(', ')'], ['-', '', ''], $solicitacao['status_nome'] ?? '')) ?>">
                    <?= htmlspecialchars($solicitacao['status_nome'] ?? 'N/A') ?>
                </span>
            </div>
        </div>
        
        <!-- Timeline -->
        <div class="mt-6">
            <h4 class="text-sm font-semibold text-gray-700 mb-4">Timeline do Processo</h4>
            <?php
            // Mapear status para estágios da timeline
            $statusNome = $solicitacao['status_nome'] ?? '';
            $condicaoNome = $solicitacao['condicao_nome'] ?? '';
            $observacoes = $solicitacao['observacoes'] ?? '';
            
            // Definir estágios da timeline
            $estagios = [
                [
                    'nome' => 'Abertura da Solicitação',
                    'icone' => 'fa-file-alt',
                    'ativo' => false,
                    'completo' => true,
                    'data' => $solicitacao['created_at'] ?? null
                ],
                [
                    'nome' => 'Análise da Solicitação',
                    'icone' => 'fa-search',
                    'ativo' => false,
                    'completo' => false,
                    'data' => null
                ],
                [
                    'nome' => 'Buscando Prestador',
                    'icone' => 'fa-user-tie',
                    'ativo' => false,
                    'completo' => false,
                    'data' => null
                ],
                [
                    'nome' => 'Serviço Agendado',
                    'icone' => 'fa-calendar-check',
                    'ativo' => false,
                    'completo' => false,
                    'data' => null
                ],
                [
                    'nome' => 'Execução de Serviço',
                    'icone' => 'fa-tools',
                    'ativo' => false,
                    'completo' => false,
                    'data' => null
                ],
                [
                    'nome' => 'Concluído/Avaliação do Serviço',
                    'icone' => 'fa-check-circle',
                    'ativo' => false,
                    'completo' => false,
                    'data' => null
                ]
            ];
            
            // Verificar anormalidades
            $anormalidades = [];
            if (stripos($observacoes, 'comprar peças') !== false || stripos($observacoes, 'comprar pecas') !== false || stripos($condicaoNome, 'comprar peças') !== false) {
                $anormalidades[] = [
                    'nome' => 'Comprar Peças',
                    'icone' => 'fa-shopping-cart',
                    'cor' => 'blue'
                ];
            }
            if (stripos($observacoes, 'prestador não compareceu') !== false || stripos($observacoes, 'prestador nao compareceu') !== false || stripos($condicaoNome, 'prestador não compareceu') !== false) {
                $anormalidades[] = [
                    'nome' => 'Prestador Não Compareceu',
                    'icone' => 'fa-user-times',
                    'cor' => 'red'
                ];
            }
            if (stripos($condicaoNome, 'ausentou') !== false || stripos($condicaoNome, 'ausente') !== false) {
                $anormalidades[] = [
                    'nome' => 'Locatário se Ausentou',
                    'icone' => 'fa-user-slash',
                    'cor' => 'orange'
                ];
            }
            
            // Determinar qual estágio está ativo baseado no status
            if (stripos($statusNome, 'Nova Solicitação') !== false) {
                $estagios[1]['ativo'] = true;
            } elseif (stripos($statusNome, 'Aguardando Prestador') !== false || stripos($statusNome, 'Buscando Prestador') !== false) {
                $estagios[1]['completo'] = true;
                $estagios[1]['ativo'] = false;
                $estagios[2]['ativo'] = true;
            } elseif (stripos($statusNome, 'Serviço Agendado') !== false || stripos($statusNome, 'Servico Agendado') !== false) {
                $estagios[1]['completo'] = true;
                $estagios[1]['ativo'] = false;
                $estagios[2]['completo'] = true;
                $estagios[2]['ativo'] = false;
                $estagios[3]['ativo'] = true;
                $estagios[3]['data'] = $solicitacao['data_agendamento'] ?? null;
            } elseif (stripos($statusNome, 'Em Andamento') !== false) {
                $estagios[1]['completo'] = true;
                $estagios[1]['ativo'] = false;
                $estagios[2]['completo'] = true;
                $estagios[2]['ativo'] = false;
                $estagios[3]['completo'] = true;
                $estagios[3]['ativo'] = false;
                $estagios[4]['ativo'] = true;
            } elseif (stripos($statusNome, 'Concluído') !== false || stripos($statusNome, 'Concluido') !== false) {
                $estagios[1]['completo'] = true;
                $estagios[1]['ativo'] = false;
                $estagios[2]['completo'] = true;
                $estagios[2]['ativo'] = false;
                $estagios[3]['completo'] = true;
                $estagios[3]['ativo'] = false;
                $estagios[4]['completo'] = true;
                $estagios[4]['ativo'] = false;
                $estagios[5]['ativo'] = true;
                $estagios[5]['completo'] = true;
                $estagios[5]['data'] = $solicitacao['updated_at'] ?? null;
            }
            
            // Buscar histórico para determinar datas dos estágios
            $historico = $historicoStatus ?? [];
            if (!empty($historico) && is_array($historico)) {
                foreach ($historico as $item) {
                    $histStatusNome = $item['status_nome'] ?? '';
                    $histData = $item['created_at'] ?? null;
                    
                    if (stripos($histStatusNome, 'Nova Solicitação') !== false && !$estagios[1]['data']) {
                        $estagios[1]['data'] = $histData;
                    } elseif ((stripos($histStatusNome, 'Aguardando Prestador') !== false || stripos($histStatusNome, 'Buscando Prestador') !== false) && !$estagios[2]['data']) {
                        $estagios[2]['data'] = $histData;
                    } elseif ((stripos($histStatusNome, 'Serviço Agendado') !== false || stripos($histStatusNome, 'Servico Agendado') !== false) && !$estagios[3]['data']) {
                        $estagios[3]['data'] = $histData;
                    } elseif (stripos($histStatusNome, 'Em Andamento') !== false && !$estagios[4]['data']) {
                        $estagios[4]['data'] = $histData;
                    } elseif ((stripos($histStatusNome, 'Concluído') !== false || stripos($histStatusNome, 'Concluido') !== false) && !$estagios[5]['data']) {
                        $estagios[5]['data'] = $histData;
                    }
                }
            }
            ?>
            
            <div class="relative">
                <!-- Linha vertical da timeline -->
                <div class="absolute left-4 top-0 w-0.5 bg-gray-200 timeline-line"></div>
                
                <div class="space-y-6">
                    <?php 
                    $totalEstagios = count($estagios);
                    $temAnormalidades = !empty($anormalidades);
                    foreach ($estagios as $index => $estagio): 
                        $ehUltimo = ($index === $totalEstagios - 1) && !$temAnormalidades;
                    ?>
                        <div class="relative flex items-start <?= $ehUltimo ? 'timeline-last-item' : '' ?>">
                            <!-- Ícone do estágio -->
                            <div class="relative z-10 flex-shrink-0">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center
                                    <?php if ($estagio['completo']): ?>
                                        bg-green-500 text-white
                                    <?php elseif ($estagio['ativo']): ?>
                                        bg-blue-500 text-white
                                    <?php else: ?>
                                        bg-gray-200 text-gray-400
                                    <?php endif; ?>">
                                    <i class="fas <?= $estagio['completo'] ? 'fa-check' : $estagio['icone'] ?> text-xs"></i>
                                </div>
                            </div>
                            
                            <!-- Conteúdo do estágio -->
                            <div class="ml-4 flex-1 <?= $ehUltimo ? '' : 'pb-6' ?>">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <p class="text-sm font-medium <?= $estagio['ativo'] || $estagio['completo'] ? 'text-gray-900' : 'text-gray-400' ?>">
                                                <?= htmlspecialchars($estagio['nome']) ?>
                                            </p>
                                            <?php if ($estagio['ativo'] && !$estagio['completo']): ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                                    <i class="fas fa-map-marker-alt mr-1"></i>
                                                    Você está nessa etapa
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($estagio['data']): ?>
                                            <p class="text-xs text-gray-500 mt-1">
                                                <?= date('d/m/Y \à\s H:i', strtotime($estagio['data'])) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($estagio['ativo'] && !$estagio['completo']): ?>
                                        <div class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Anormalidades -->
                    <?php if (!empty($anormalidades)): ?>
                        <?php foreach ($anormalidades as $anormalidade): ?>
                            <?php
                            $corClasses = [
                                'blue' => ['bg' => 'bg-blue-500', 'bg-light' => 'bg-blue-50', 'border' => 'border-blue-200', 'text' => 'text-blue-900'],
                                'red' => ['bg' => 'bg-red-500', 'bg-light' => 'bg-red-50', 'border' => 'border-red-200', 'text' => 'text-red-900'],
                                'orange' => ['bg' => 'bg-orange-500', 'bg-light' => 'bg-orange-50', 'border' => 'border-orange-200', 'text' => 'text-orange-900'],
                            ];
                            $cores = $corClasses[$anormalidade['cor']] ?? $corClasses['blue'];
                            ?>
                            <div class="relative flex items-start">
                                <!-- Ícone da anormalidade -->
                                <div class="relative z-10 flex-shrink-0">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center <?= $cores['bg'] ?> text-white">
                                        <i class="fas <?= $anormalidade['icone'] ?> text-xs"></i>
                                    </div>
                                </div>
                                
                                <!-- Conteúdo da anormalidade -->
                                <div class="ml-4 flex-1">
                                    <div class="<?= $cores['bg-light'] ?> border <?= $cores['border'] ?> rounded-lg p-3">
                                        <p class="text-sm font-medium <?= $cores['text'] ?>">
                                            <i class="fas <?= $anormalidade['icone'] ?> mr-2"></i>
                                            <?= htmlspecialchars($anormalidade['nome']) ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Informações do Serviço -->
<div class="bg-white rounded-lg p-5 shadow-sm mt-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">
        <i class="fas fa-info-circle mr-2 text-blue-600"></i>
        Informações do Serviço
    </h3>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <div class="space-y-3">
                <?php
                $localManutencao = trim($solicitacao['local_manutencao'] ?? '');
                $tipoImovel = trim($solicitacao['tipo_imovel'] ?? '');
                $descricaoProblema = trim($solicitacao['descricao_problema'] ?? '');
                
                if (!empty($localManutencao)) {
                    $localManutencao = preg_replace('/^Validação:\s*/i', '', $localManutencao);
                    $localManutencao = trim($localManutencao);
                }
                ?>
                
                <div>
                    <span class="text-sm font-semibold text-gray-900">Local da Manutenção:</span>
                    <p class="text-sm text-gray-700 mt-1"><?= !empty($localManutencao) ? htmlspecialchars($localManutencao) : '-' ?></p>
                </div>
                
                <div>
                    <span class="text-sm font-semibold text-gray-900">Tipo do Imóvel:</span>
                    <p class="text-sm text-gray-700 mt-1"><?= !empty($tipoImovel) ? htmlspecialchars($tipoImovel) : '-' ?></p>
                </div>
                
                <div>
                    <span class="text-sm font-semibold text-gray-900">Descrição do Problema:</span>
                    <div class="bg-gray-50 rounded-lg p-3 mt-1">
                        <p class="text-sm text-gray-700 text-left whitespace-pre-wrap"><?= !empty($descricaoProblema) ? nl2br(htmlspecialchars($descricaoProblema)) : 'Nenhuma descrição fornecida.' ?></p>
                    </div>
                </div>
                
                <div class="pt-2">
                    <div>
                        <span class="text-sm font-semibold text-gray-900">Categoria:</span>
                        <p class="text-sm text-gray-700"><?= htmlspecialchars($solicitacao['categoria_nome'] ?? '-') ?></p>
                    </div>
                    <?php if (!empty($solicitacao['subcategoria_nome'])): ?>
                    <div class="mt-2">
                        <span class="text-sm font-semibold text-gray-900">Tipo de Serviço:</span>
                        <p class="text-sm text-gray-700"><?= htmlspecialchars($solicitacao['subcategoria_nome']) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div>
            <h4 class="text-sm font-medium text-gray-700 mb-3">Informações do Cliente</h4>
            <div class="space-y-3">
                <div>
                    <span class="text-sm text-gray-500">Nome:</span>
                    <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($solicitacao['locatario_nome'] ?? '-') ?></p>
                </div>
                <?php if (!empty($solicitacao['locatario_cpf'])): ?>
                <div>
                    <span class="text-sm text-gray-500">CPF:</span>
                    <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($solicitacao['locatario_cpf']) ?></p>
                </div>
                <?php endif; ?>
                <?php if (!empty($solicitacao['locatario_telefone'])): ?>
                <div>
                    <span class="text-sm text-gray-500">Telefone:</span>
                    <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($solicitacao['locatario_telefone']) ?></p>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($solicitacao['imovel_endereco'])): ?>
            <div class="mt-4">
                <h4 class="text-sm font-medium text-gray-700 mb-3">
                    <i class="fas fa-map-marker-alt mr-2 text-gray-400"></i>
                    Endereço
                </h4>
                <div class="space-y-2">
                    <p class="text-sm text-gray-900">
                        <?= htmlspecialchars($solicitacao['imovel_endereco']) ?>
                        <?php if (!empty($solicitacao['imovel_numero'])): ?>
                            , <?= htmlspecialchars($solicitacao['imovel_numero']) ?>
                        <?php endif; ?>
                        <?php if (!empty($solicitacao['imovel_complemento'])): ?>
                            - <?= htmlspecialchars($solicitacao['imovel_complemento']) ?>
                        <?php endif; ?>
                    </p>
                    <p class="text-sm text-gray-600">
                        <?= htmlspecialchars($solicitacao['imovel_bairro'] ?? '') ?> - 
                        <?= htmlspecialchars($solicitacao['imovel_cidade'] ?? '') ?>/<?= htmlspecialchars($solicitacao['imovel_estado'] ?? '') ?>
                    </p>
                    <?php if (!empty($solicitacao['imovel_cep'])): ?>
                    <p class="text-sm text-gray-600">CEP: <?= htmlspecialchars($solicitacao['imovel_cep']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Fotos Enviadas -->
    <?php if (!empty($fotos) && count($fotos) > 0): ?>
    <div class="mt-6">
        <h4 class="text-sm font-medium text-gray-700 mb-3">
            <i class="fas fa-camera mr-2 text-gray-400"></i>
            Fotos Enviadas (<?= count($fotos) ?>)
        </h4>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <?php foreach ($fotos as $foto): ?>
            <?php 
                $nomeArquivo = $foto['nome_arquivo'] ?? null;
                if (!$nomeArquivo && !empty($foto['url_arquivo'])) {
                    $nomeArquivo = basename($foto['url_arquivo']);
                }
                
                if (empty($nomeArquivo)) {
                    continue;
                }
                
                $urlFoto = url('Public/uploads/solicitacoes/' . htmlspecialchars($nomeArquivo));
            ?>
            <div class="relative">
                <img src="<?= $urlFoto ?>" 
                     alt="Foto da solicitação" 
                     class="w-full h-32 object-cover rounded-lg cursor-pointer hover:opacity-75 transition-opacity"
                     onclick="abrirModalFoto('<?= $urlFoto ?>')"
                     onerror="console.error('Erro ao carregar foto: <?= htmlspecialchars($nomeArquivo) ?>'); this.parentElement.style.display='none';">
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Histórico de Status -->
<?php if (!empty($historicoStatus) && is_array($historicoStatus)): ?>
<div class="bg-white rounded-lg shadow-sm mt-6">
    <div class="px-4 sm:px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">
            <i class="fas fa-history mr-2 text-blue-600"></i>
            Histórico de Status
        </h3>
    </div>
    <div class="px-4 sm:px-6 py-4">
        <div class="space-y-4">
            <?php foreach ($historicoStatus as $item): ?>
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <div class="w-3 h-3 rounded-full bg-blue-600 mt-1"></div>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-sm font-medium text-gray-900">
                            <?= htmlspecialchars($item['status_nome'] ?? 'Status') ?>
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                            <?= date('d/m/Y H:i', strtotime($item['created_at'])) ?>
                            <span class="ml-2 text-gray-400">
                                <?= htmlspecialchars($item['executado_por'] ?? 'Por Sistema') ?>
                            </span>
                        </p>
                        <?php if (!empty($item['observacoes'])): ?>
                            <div class="text-xs text-gray-600 mt-1">
                                <?= processarObservacoesComImagens($item['observacoes']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal para visualizar foto em tamanho maior -->
<div id="modalFoto" class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center p-4">
    <div class="relative max-w-4xl max-h-full">
        <button onclick="fecharModalFoto()" class="absolute top-2 right-2 text-white bg-black bg-opacity-50 rounded-full p-2 hover:bg-opacity-75">
            <i class="fas fa-times"></i>
        </button>
        <img id="fotoModal" src="" alt="Foto" class="max-w-full max-h-[90vh] rounded-lg">
    </div>
</div>

<style>
.timeline-line {
    height: calc(100% - 1rem);
}

.timeline-last-item + .timeline-line {
    height: 0;
}
</style>

<script>
function abrirModalFoto(url) {
    document.getElementById('fotoModal').src = url;
    document.getElementById('modalFoto').classList.remove('hidden');
}

function fecharModalFoto() {
    document.getElementById('modalFoto').classList.add('hidden');
}
</script>

<?php
$content = ob_get_clean();
include 'app/Views/layouts/locatario.php';
?>

