<?php
/**
 * View: Rastreamento Público de Solicitação (sem login)
 * Permite que pessoas vejam o status da solicitação usando o token enviado no WhatsApp
 */
$title = 'Rastreamento da Solicitação - Assistência 360°';
$currentPage = 'rastreamento-publico';
ob_start();

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
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-search-location mr-2"></i>
                Rastreamento da Solicitação
            </h1>
            <p class="text-gray-600 mt-1">
                Protocolo: <?= htmlspecialchars($solicitacao['protocolo_seguradora'] ?? ($solicitacao['numero_solicitacao'] ?? 'KSS' . $solicitacao['id'])) ?>
                <?php 
                // Buscar numero_contrato - verificar diferentes possibilidades
                $numeroContrato = '';
                if (!empty($solicitacao['numero_contrato'])) {
                    $numeroContrato = trim($solicitacao['numero_contrato']);
                }
                // Se ainda estiver vazio, pode estar em outro campo relacionado
                if (empty($numeroContrato) && !empty($solicitacao['locatario_id'])) {
                    // Tentar buscar do locatário se necessário
                }
                if (!empty($numeroContrato)): ?>
                    | Contrato imobiliária: <?= htmlspecialchars($numeroContrato) ?>
                <?php endif; ?>
            </p>
        </div>
    </div>
</div>

<!-- STATUS (PRIMEIRO - NO TOPO) -->
<div class="bg-white rounded-lg p-5 shadow-sm mb-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">
        <i class="fas fa-info-circle mr-2 text-blue-600"></i>
        Status
    </h3>
    
    <div class="space-y-4">
        <!-- Status Atual -->
        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
            <div class="flex items-center gap-3 mb-2">
                <span class="text-sm font-medium text-gray-700">Status Atual:</span>
                <span class="status-badge status-<?= strtolower(str_replace([' ', '(', ')'], ['-', '', ''], $solicitacao['status_nome'])) ?>">
                <?= htmlspecialchars($solicitacao['status_nome']) ?>
            </span>
        </div>
            <p class="text-sm text-gray-600 mt-2">
                <?php
                $statusNome = $solicitacao['status_nome'] ?? '';
                $mensagemStatus = '';
                
                if (stripos($statusNome, 'Nova Solicitação') !== false) {
                    $mensagemStatus = 'Sua solicitação foi recebida e está aguardando análise pela equipe. Em breve entraremos em contato.';
                } elseif (stripos($statusNome, 'Aguardando Prestador') !== false || stripos($statusNome, 'Buscando Prestador') !== false) {
                    $mensagemStatus = 'Estamos buscando um prestador disponível para atender sua solicitação. Você será notificado assim que encontrarmos alguém.';
                } elseif (stripos($statusNome, 'Serviço Agendado') !== false || stripos($statusNome, 'Servico Agendado') !== false) {
                    $mensagemStatus = 'Seu serviço está agendado. Por favor, esteja disponível no horário combinado.';
                } elseif (stripos($statusNome, 'Em Andamento') !== false) {
                    $mensagemStatus = 'O prestador está a caminho ou realizando o serviço.';
                } elseif (stripos($statusNome, 'Concluído') !== false || stripos($statusNome, 'Concluido') !== false) {
                    $mensagemStatus = 'Seu serviço foi concluído com sucesso. Obrigado por utilizar nossos serviços!';
                } elseif (stripos($statusNome, 'Cancelado') !== false) {
                    $mensagemStatus = 'Esta solicitação foi cancelada.';
                } else {
                    $mensagemStatus = 'Sua solicitação está sendo processada.';
                }
                ?>
                <i class="fas fa-info-circle mr-2 text-blue-500"></i>
                <?= htmlspecialchars($mensagemStatus) ?>
            </p>
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
            // Importante: apenas um estágio pode estar ativo por vez, e estágios completos não podem estar ativos
            if (stripos($statusNome, 'Nova Solicitação') !== false) {
                // Abertura já está completa, Análise está ativa
                $estagios[1]['ativo'] = true;
            } elseif (stripos($statusNome, 'Aguardando Prestador') !== false || stripos($statusNome, 'Buscando Prestador') !== false) {
                $estagios[1]['completo'] = true;
                $estagios[1]['ativo'] = false; // Garantir que não está ativo
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
            
            <!-- Ações Disponíveis -->
            <div class="mt-6 pt-6 border-t border-gray-200">
        <h3 class="text-sm font-medium text-gray-700 mb-3">Ações Disponíveis</h3>
        <div class="flex flex-wrap gap-2 sm:gap-3">
            <?php
            $statusNome = $solicitacao['status_nome'] ?? '';
            $condicaoNome = $solicitacao['condicao_nome'] ?? '';
            $dataAgendamento = $solicitacao['data_agendamento'] ?? null;
            
            // Verificar se pode cancelar (até 1 dia antes da data agendada)
            $podeCancelar = false;
            if ($dataAgendamento) {
                $dataAgendamentoObj = new \DateTime($dataAgendamento);
                $hoje = new \DateTime();
                $diferenca = $hoje->diff($dataAgendamentoObj);
                // Pode cancelar se a data agendada for pelo menos 1 dia no futuro
                $podeCancelar = $diferenca->days >= 1 && $dataAgendamentoObj > $hoje;
            }
            
            // Status: Nova Solicitação
            if ($statusNome === 'Nova Solicitação' || stripos($statusNome, 'Nova Solicitação') !== false) {
                // Botão Cancelar
                ?>
                <button onclick="executarAcao(<?= $solicitacao['id'] ?>, 'cancelado')" 
                        class="flex-1 sm:flex-none px-3 sm:px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                    <i class="fas fa-times-circle mr-2"></i>
                    <span class="hidden sm:inline">Cancelar</span>
                    <span class="sm:hidden">Cancelar</span>
                </button>
                <?php
                // Botão Reagendar
                ?>
                <button onclick="executarAcao(<?= $solicitacao['id'] ?>, 'reagendar')" 
                        class="flex-1 sm:flex-none px-3 sm:px-4 py-2 bg-yellow-600 text-white text-sm font-medium rounded-lg hover:bg-yellow-700 transition-colors">
                    <i class="fas fa-calendar-alt mr-2"></i>
                    <span class="hidden sm:inline">Reagendar</span>
                    <span class="sm:hidden">Reagendar</span>
                </button>
                <?php
            }
            // Status: Aguardando Prestador / Buscando Prestador
            elseif (stripos($statusNome, 'Aguardando Prestador') !== false || 
                    stripos($statusNome, 'Buscando Prestador') !== false ||
                    stripos($statusNome, 'Aguardando prestador') !== false) {
                // Botão Cancelar
                ?>
                <button onclick="executarAcao(<?= $solicitacao['id'] ?>, 'cancelado')" 
                        class="flex-1 sm:flex-none px-3 sm:px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                    <i class="fas fa-times-circle mr-2"></i>
                    <span class="hidden sm:inline">Cancelar</span>
                    <span class="sm:hidden">Cancelar</span>
                </button>
                <?php
                // Botão Reagendar (se condição permitir)
                if (stripos($condicaoNome, 'reagendar') !== false || 
                    stripos($condicaoNome, 'Reagendar') !== false) {
                    ?>
                    <button onclick="executarAcao(<?= $solicitacao['id'] ?>, 'reagendar')" 
                            class="flex-1 sm:flex-none px-3 sm:px-4 py-2 bg-yellow-600 text-white text-sm font-medium rounded-lg hover:bg-yellow-700 transition-colors">
                        <i class="fas fa-calendar-alt mr-2"></i>
                        <span class="hidden sm:inline">Reagendar</span>
                        <span class="sm:hidden">Reagendar</span>
                    </button>
                    <?php
                }
            }
            // Status: Serviço Agendado
            elseif (stripos($statusNome, 'Serviço Agendado') !== false || 
                    stripos($statusNome, 'Servico Agendado') !== false) {
                // Botão Cancelar (até 1 dia antes)
                if ($podeCancelar) {
                    ?>
                    <button onclick="executarAcao(<?= $solicitacao['id'] ?>, 'cancelado')" 
                            class="flex-1 sm:flex-none px-3 sm:px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                        <i class="fas fa-times-circle mr-2"></i>
                        <span class="hidden sm:inline">Cancelar</span>
                        <span class="sm:hidden">Cancelar</span>
                    </button>
                    <?php
                }
                // Botão Concluído
                ?>
                <button onclick="executarAcao(<?= $solicitacao['id'] ?>, 'concluido')" 
                        class="flex-1 sm:flex-none px-3 sm:px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span class="hidden sm:inline">Concluído</span>
                    <span class="sm:hidden">Concluído</span>
                </button>
                
                <!-- Serviço não realizado -->
                <button onclick="executarAcao(<?= $solicitacao['id'] ?>, 'servico_nao_realizado')" 
                        class="flex-1 sm:flex-none px-3 sm:px-4 py-2 bg-orange-600 text-white text-sm font-medium rounded-lg hover:bg-orange-700 transition-colors">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <span class="hidden sm:inline">Serviço não realizado</span>
                    <span class="sm:hidden">Não realizado</span>
                </button>
                
                <!-- Comprar peças -->
                <button onclick="executarAcao(<?= $solicitacao['id'] ?>, 'comprar_pecas')" 
                        class="flex-1 sm:flex-none px-3 sm:px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-shopping-cart mr-2"></i>
                    <span class="hidden sm:inline">Comprar peças</span>
                    <span class="sm:hidden">Peças</span>
                </button>
                
                <!-- Precisei me ausentar -->
                <button onclick="executarAcao(<?= $solicitacao['id'] ?>, 'ausente')" 
                        class="flex-1 sm:flex-none px-3 sm:px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                    <i class="fas fa-user-times mr-2"></i>
                    <span class="hidden sm:inline">Precisei me ausentar</span>
                    <span class="sm:hidden">Ausente</span>
                </button>
                
                <!-- Outros -->
                <button onclick="abrirModal('modalOutros')" 
                        class="flex-1 sm:flex-none px-3 sm:px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-ellipsis-h mr-2"></i>
                    <span class="hidden sm:inline">Outros</span>
                    <span class="sm:hidden">Outros</span>
                </button>
                
                <?php
            }
            ?>
        </div>
        </div>
    </div>
        </div>
    </div>
    
<!-- Bloco 1: Descrição do Problema, Informação do Serviço, Obs do Segurado e Fotos -->
<div class="bg-white rounded-lg p-5 shadow-sm mt-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">
        <i class="fas fa-info-circle mr-2 text-blue-600"></i>
        Informações do Serviço
    </h3>
    
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Informações do Serviço -->
        <div>
            <div>
                <div class="space-y-3">
                    <?php
                    // Usar campos diretos do banco - SEMPRE priorizar campos diretos
                    $localManutencao = trim($solicitacao['local_manutencao'] ?? '');
                    $finalidade = trim($solicitacao['finalidade_locacao'] ?? '');
                    $tipoImovel = trim($solicitacao['tipo_imovel'] ?? '');
                    $descricaoProblema = trim($solicitacao['descricao_problema'] ?? '');
                    
                    // Limpar local da manutenção - remover "Validação:" se existir
                    if (!empty($localManutencao)) {
                        $localManutencao = preg_replace('/^Validação:\s*/i', '', $localManutencao);
                        $localManutencao = trim($localManutencao);
                    }
                    
                    // Se os campos diretos estiverem vazios, tentar extrair de descricao_card e observacoes
                    if (empty($localManutencao) || empty($tipoImovel)) {
                        // Tentar de descricao_card primeiro
                        $descricaoCard = $solicitacao['descricao_card'] ?? '';
                        if (!empty($descricaoCard)) {
                            $linhas = explode("\n", $descricaoCard);
                            foreach ($linhas as $linha) {
                                $linha = trim($linha);
                                if (empty($linha)) continue;
                                
                                // Ignorar linhas que começam com "Validação:"
                                if (stripos($linha, 'Validação:') === 0) {
                                    continue;
                                }
                                
                                // Buscar Finalidade
                                if (stripos($linha, 'Finalidade:') !== false) {
                                    if (empty($finalidade)) {
                                    $finalidade = trim(str_replace('Finalidade:', '', $linha));
                                    }
                                    continue;
                                }
                                
                                // Buscar Tipo
                                if (stripos($linha, 'Tipo:') !== false || stripos($linha, 'Tipo do Imóvel:') !== false || stripos($linha, 'Tipo do imóvel:') !== false) {
                                    if (empty($tipoImovel)) {
                                        $tipoImovel = trim(str_replace(['Tipo:', 'Tipo do Imóvel:', 'Tipo do imóvel:'], '', $linha));
                                        // Limpar valores comuns que podem estar no início
                                        $tipoImovel = preg_replace('/^(RESIDENCIAL|COMERCIAL|CASA|APARTAMENTO)\s*/i', '', $tipoImovel);
                                        $tipoImovel = trim($tipoImovel);
                                        // Se ainda estiver vazio, tentar pegar o valor após "Tipo:"
                                        if (empty($tipoImovel) && preg_match('/Tipo[^:]*:\s*(.+)/i', $linha, $matches)) {
                                            $tipoImovel = trim($matches[1]);
                                        }
                                    }
                                    continue;
                                }
                                
                                // Se for a primeira linha útil e não for Finalidade ou Tipo, pode ser local_manutencao
                                if (empty($localManutencao) && stripos($linha, 'Finalidade:') === false && stripos($linha, 'Tipo:') === false) {
                                    // Não deve ser muito longa (provavelmente não é local)
                                    if (strlen($linha) < 100) {
                                        $localManutencao = $linha;
                                    }
                                }
                            }
                        }
                        
                        // Se ainda estiver vazio, tentar de observacoes
                        if (empty($localManutencao) || empty($tipoImovel)) {
                            $observacoes = $solicitacao['observacoes'] ?? '';
                            if (!empty($observacoes)) {
                                $linhas = explode("\n", $observacoes);
                                foreach ($linhas as $linha) {
                                    $linha = trim($linha);
                                    if (empty($linha)) continue;
                                    
                                    // Ignorar linhas que começam com "Validação:"
                                    if (stripos($linha, 'Validação:') === 0) {
                                        continue;
                                    }
                                    
                                    // Buscar Tipo
                                    if (stripos($linha, 'Tipo:') !== false || stripos($linha, 'Tipo do Imóvel:') !== false || stripos($linha, 'Tipo do imóvel:') !== false) {
                                        if (empty($tipoImovel)) {
                                            $tipoImovel = trim(str_replace(['Tipo:', 'Tipo do Imóvel:', 'Tipo do imóvel:'], '', $linha));
                                            $tipoImovel = preg_replace('/^(RESIDENCIAL|COMERCIAL|CASA|APARTAMENTO)\s*/i', '', $tipoImovel);
                                            $tipoImovel = trim($tipoImovel);
                                            if (empty($tipoImovel) && preg_match('/Tipo[^:]*:\s*(.+)/i', $linha, $matches)) {
                                                $tipoImovel = trim($matches[1]);
                            }
                        }
                                        continue;
                                    }
                                    
                                    // Buscar local (primeira linha útil que não seja Tipo ou Finalidade)
                                    if (empty($localManutencao) && stripos($linha, 'Finalidade:') === false && stripos($linha, 'Tipo:') === false) {
                                        // Não deve ser muito longa ou parecer uma observação real
                                        if (strlen($linha) < 100 && !preg_match('/[.!?]{2,}/', $linha)) {
                                            $localManutencao = $linha;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    
                    // Limpar valores finais
                    $localManutencao = trim($localManutencao);
                    $tipoImovel = trim($tipoImovel);
                    ?>
                    
                    <!-- 1. Local da Manutenção (PRIMEIRO) -->
                    <div>
                        <span class="text-sm font-semibold text-gray-900">Local da Manutenção:</span>
                        <p class="text-sm text-gray-700 mt-1"><?= !empty($localManutencao) ? htmlspecialchars($localManutencao) : '-' ?></p>
                    </div>
                    
                    <!-- 2. Tipo do Imóvel (SEGUNDO) -->
                    <div>
                        <span class="text-sm font-semibold text-gray-900">Tipo do Imóvel:</span>
                        <p class="text-sm text-gray-700 mt-1"><?= !empty($tipoImovel) ? htmlspecialchars($tipoImovel) : '-' ?></p>
                    </div>
                    
                    <!-- 3. Descrição do Problema -->
                    <div>
                        <span class="text-sm font-semibold text-gray-900">Descrição do Problema:</span>
                        <div class="bg-gray-50 rounded-lg p-3 mt-1">
                            <p class="text-sm text-gray-700 text-left whitespace-pre-wrap"><?= !empty($descricaoProblema) ? nl2br(htmlspecialchars($descricaoProblema)) : 'Nenhuma descrição fornecida.' ?></p>
                        </div>
                    </div>
                    
                    <!-- Informações adicionais (não editáveis) -->
                    <div class="pt-2">
                        <div>
                            <span class="text-sm font-semibold text-gray-900">Categoria:</span>
                            <p class="text-sm text-gray-700"><?= htmlspecialchars($solicitacao['categoria_nome']) ?></p>
                        </div>
                        <?php 
                        $subcategorias = getSubcategorias($solicitacao);
                        $temMultiplas = count($subcategorias) > 1;
                        if ($temMultiplas || !empty($subcategorias) || !empty($solicitacao['subcategoria_nome'])): ?>
                        <div class="mt-2">
                            <span class="text-sm font-semibold text-gray-900"><?= $temMultiplas ? 'Serviços:' : 'Tipo de Serviço:' ?></span>
                            <?php if ($temMultiplas): ?>
                                <ul class="list-disc list-inside space-y-0.5 text-sm text-gray-700 mt-1">
                                    <?php foreach ($subcategorias as $nome): ?>
                                        <li><?= htmlspecialchars($nome) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php elseif (!empty($subcategorias)): ?>
                                <p class="text-sm text-gray-700"><?= htmlspecialchars($subcategorias[0]) ?></p>
                            <?php else: ?>
                            <p class="text-sm text-gray-700"><?= htmlspecialchars($solicitacao['subcategoria_nome']) ?></p>
                            <?php endif; ?>
                        </div>
            <?php endif; ?>
                        <?php if (!empty($finalidade)): ?>
                        <div class="mt-2">
                            <span class="text-sm font-semibold text-gray-900">Finalidade:</span>
                            <p class="text-sm text-gray-700"><?= htmlspecialchars($finalidade) ?></p>
                        </div>
            <?php endif; ?>
                        <div class="mt-2">
                            <span class="text-sm font-semibold text-gray-900">Prioridade:</span>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                <?= ($solicitacao['prioridade'] ?? 'NORMAL') === 'ALTA' ? 'bg-red-100 text-red-800' : 
                                   (($solicitacao['prioridade'] ?? 'NORMAL') === 'MEDIA' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') ?>">
                                <?= htmlspecialchars($solicitacao['prioridade'] ?? 'NORMAL') ?>
                            </span>
                        </div>
                        <div class="mt-2">
                            <span class="text-sm font-semibold text-gray-900">Data de Criação:</span>
                            <p class="text-sm text-gray-700">
                                <?= date('d/m/Y \à\s H:i', strtotime($solicitacao['created_at'])) ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Observações do Segurado -->
        <div>
            <h4 class="text-sm font-medium text-gray-700 mb-3">
                <i class="fas fa-comment-alt mr-2 text-yellow-600"></i>
                Observações do Segurado
            </h4>
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <textarea id="observacoes-segurado" 
                          class="w-full bg-white border border-yellow-300 rounded p-3 text-sm text-gray-900 min-h-[120px] resize-none" 
                          placeholder="Descreva qualquer situação adicional (ex: prestador não compareceu, precisa comprar peças, etc.)"><?php
                    // Limpar observações - remover informações de tipo, validação e local da manutenção
                    $observacoesLimpa = $solicitacao['observacoes'] ?? '';
                    if (!empty($observacoesLimpa)) {
                        // Remover linhas que começam com "Tipo:", "Validação:" ou "Finalidade:"
                        // Também remover o local_manutencao caso esteja no início das observações (para dados antigos)
                        $linhas = explode("\n", $observacoesLimpa);
                        $linhasLimpa = [];
                        $primeiraLinha = true;
                        foreach ($linhas as $linha) {
                            $linha = trim($linha);
                            // Ignorar linhas que começam com "Tipo:", "Validação:" ou "Finalidade:"
                            if (stripos($linha, 'Tipo:') === 0 || stripos($linha, 'Validação:') === 0 || stripos($linha, 'Finalidade:') === 0) {
                                continue;
                            }
                            // Se for a primeira linha e parecer ser um local da manutenção (curto, sem pontuação), remover
                            if ($primeiraLinha && strlen($linha) < 100 && !preg_match('/[.!?]{2,}/', $linha) && stripos($linha, 'Local') === false) {
                                // Verificar se não é uma observação real (se não contém palavras comuns de observações)
                                $palavrasObservacao = ['prestador', 'compareceu', 'peças', 'material', 'situação', 'adicional'];
                                $ehObservacao = false;
                                foreach ($palavrasObservacao as $palavra) {
                                    if (stripos($linha, $palavra) !== false) {
                                        $ehObservacao = true;
                                        break;
                                    }
                                }
                                if (!$ehObservacao) {
                                    $primeiraLinha = false;
                                    continue;
                                }
                            }
                            $linhasLimpa[] = $linha;
                            $primeiraLinha = false;
                        }
                        $observacoesLimpa = implode("\n", $linhasLimpa);
                    }
                    echo htmlspecialchars($observacoesLimpa);
                ?></textarea>
                <button onclick="salvarObservacoes(<?= $solicitacao['id'] ?>)" 
                        class="mt-3 w-full px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>
                    Salvar Observações
                </button>
            </div>
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
                // Usar nome_arquivo se existir, caso contrário tentar url_arquivo
                $nomeArquivo = $foto['nome_arquivo'] ?? null;
                if (!$nomeArquivo && !empty($foto['url_arquivo'])) {
                    // Extrair nome do arquivo da URL se necessário
                    $nomeArquivo = basename($foto['url_arquivo']);
                }
                
                // Se ainda não tiver nome, pular esta foto
                if (empty($nomeArquivo)) {
                    continue;
                }
                
                // Construir URL da foto
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
    <?php else: ?>
    <div class="mt-6">
        <h4 class="text-sm font-medium text-gray-700 mb-3">
            <i class="fas fa-camera mr-2 text-gray-400"></i>
            Fotos Enviadas (0)
        </h4>
        <p class="text-sm text-gray-500">Nenhuma foto foi enviada</p>
    </div>
            <?php endif; ?>
</div>

<!-- Bloco 2: Informações do Cliente e Endereço -->
<div class="bg-white rounded-lg p-5 shadow-sm mt-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">
        <i class="fas fa-user mr-2 text-blue-600"></i>
        Informações do Cliente e Endereço
    </h3>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Informações do Cliente -->
    <div>
            <h4 class="text-sm font-medium text-gray-700 mb-3">Informações do Cliente</h4>
            <div class="space-y-3">
            <div>
                    <span class="text-sm font-semibold text-gray-900">Nome:</span>
                    <p class="text-sm text-gray-700"><?= htmlspecialchars($solicitacao['locatario_nome']) ?></p>
            </div>
            <?php if (!empty($solicitacao['locatario_cpf'])): ?>
            <div>
                    <span class="text-sm font-semibold text-gray-900">CPF/CNPJ:</span>
                <p class="text-sm text-gray-700">
            <?php 
                    $cpfLimpo = preg_replace('/[^0-9]/', '', $solicitacao['locatario_cpf']);
                    if (strlen($cpfLimpo) == 11) {
                        // Formatar como CPF: 000.000.000-00
                        echo htmlspecialchars(preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpfLimpo));
                    } elseif (strlen($cpfLimpo) == 14) {
                        // Formatar como CNPJ: 00.000.000/0000-00
                        echo htmlspecialchars(preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cpfLimpo));
                } else {
                        echo htmlspecialchars($solicitacao['locatario_cpf']);
            }
            ?>
        </p>
    </div>
    <?php endif; ?>
            <?php if (!empty($solicitacao['locatario_telefone'])): ?>
            <div>
                    <span class="text-sm font-semibold text-gray-900">Telefone:</span>
                    <p class="text-sm text-gray-700">
                        <?php if (!empty($solicitacao['locatario_telefone'])): ?>
                        <a href="https://wa.me/55<?= preg_replace('/[^0-9]/', '', $solicitacao['locatario_telefone']) ?>" 
                           target="_blank" class="text-green-600 hover:text-green-800">
                            <i class="fab fa-whatsapp mr-1"></i>
                            <?= htmlspecialchars($solicitacao['locatario_telefone']) ?>
                        </a>
                        <?php else: ?>
                        Não informado
                        <?php endif; ?>
        </p>
    </div>
    <?php endif; ?>
            <?php if (!empty($solicitacao['imobiliaria_nome'])): ?>
            <div>
                    <span class="text-sm font-semibold text-gray-900">Imobiliária:</span>
                <p class="text-sm text-gray-700"><?= htmlspecialchars($solicitacao['imobiliaria_nome']) ?></p>
            </div>
    <?php endif; ?>
        </div>
    </div>
    
    <!-- Endereço -->
    <?php if (!empty($solicitacao['imovel_endereco'])): ?>
        <div>
            <h4 class="text-sm font-semibold text-gray-900 mb-3">
                <i class="fas fa-map-marker-alt mr-2 text-gray-400"></i>
                Endereço do Imóvel
            </h4>
            <div class="space-y-2">
        <p class="text-sm text-gray-700">
            <?= htmlspecialchars($solicitacao['imovel_endereco']) ?>
            <?php if (!empty($solicitacao['imovel_numero'])): ?>
                , <?= htmlspecialchars($solicitacao['imovel_numero']) ?>
            <?php endif; ?>
            <?php 
            // Mostrar complemento (que contém a unidade quando vem do bolsão)
            if (!empty($solicitacao['imovel_complemento'])): ?>
                - <?= htmlspecialchars($solicitacao['imovel_complemento']) ?>
            <?php endif; ?>
                </p>
                <p class="text-sm text-gray-700">
                    <?= htmlspecialchars($solicitacao['imovel_bairro']) ?> - 
                    <?= htmlspecialchars($solicitacao['imovel_cidade']) ?>/<?= htmlspecialchars($solicitacao['imovel_estado']) ?>
                </p>
                <?php if (!empty($solicitacao['imovel_cep'])): ?>
                <p class="text-sm text-gray-700"><span class="font-semibold">CEP:</span> <?= htmlspecialchars($solicitacao['imovel_cep']) ?></p>
                <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    </div>
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
                        <?php if (!empty($item['observacoes']) && !str_contains($item['observacoes'], 'pelo Locatário') && !str_contains($item['observacoes'], 'Locatário')): ?>
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

<!-- Info Footer -->
<div class="mt-6 bg-blue-50 rounded-lg p-4">
    <p class="text-sm text-blue-800">
        <i class="fas fa-info-circle mr-2"></i>
        Você está visualizando esta solicitação através de um link público. Para mais informações, entre em contato com a imobiliária.
    </p>
</div>

<!-- Modal para visualizar foto em tamanho maior -->
<div id="modalFoto" class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center p-4">
    <div class="relative max-w-4xl max-h-full">
        <button onclick="fecharModalFoto()" class="absolute top-2 right-2 text-white bg-black bg-opacity-50 rounded-full p-2 hover:bg-opacity-75">
            <i class="fas fa-times"></i>
        </button>
        <img id="fotoModal" src="" alt="Foto" class="max-w-full max-h-[90vh] rounded-lg">
    </div>
</div>

<!-- Modal Concluído -->
<div id="modalConcluido" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Confirmar Conclusão</h3>
        <form id="formConcluido" onsubmit="processarConcluido(event)" enctype="multipart/form-data">
            <input type="hidden" name="solicitacao_id" value="<?= $solicitacao['id'] ?>">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Observação (opcional)</label>
                <textarea name="observacao" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent" placeholder="Adicione uma observação sobre a conclusão do serviço..."></textarea>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Anexos (opcional)</label>
                <input type="file" name="anexos[]" multiple accept="image/*,.pdf,.doc,.docx" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                <p class="text-xs text-gray-500 mt-1">Você pode selecionar múltiplos arquivos (imagens, PDF, Word)</p>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="fecharModal('modalConcluido')" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    Confirmar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Cancelando -->
<div id="modalCancelando" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Cancelar Solicitação</h3>
        <form id="formCancelando" onsubmit="processarCancelando(event)" enctype="multipart/form-data">
            <input type="hidden" name="solicitacao_id" value="<?= $solicitacao['id'] ?>">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Observação <span class="text-red-500">*</span></label>
                <textarea name="observacao" rows="4" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent" placeholder="Informe o motivo do cancelamento..."></textarea>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Anexos (opcional)</label>
                <input type="file" name="anexos[]" multiple accept="image/*,.pdf,.doc,.docx" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                <p class="text-xs text-gray-500 mt-1">Você pode selecionar múltiplos arquivos (imagens, PDF, Word)</p>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="fecharModal('modalCancelando')" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    Confirmar Cancelamento
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Serviço não realizado -->
<div id="modalServicoNaoRealizado" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Serviço não realizado</h3>
        <form id="formServicoNaoRealizado" onsubmit="processarServicoNaoRealizado(event)" enctype="multipart/form-data">
            <input type="hidden" name="solicitacao_id" value="<?= $solicitacao['id'] ?>">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Observação</label>
                <textarea name="observacao" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent" placeholder="Informe o motivo pelo qual o serviço não foi realizado..."></textarea>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Anexos</label>
                <input type="file" name="anexos[]" multiple accept="image/*,.pdf,.doc,.docx" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent">
                <p class="text-xs text-gray-500 mt-1">Você pode selecionar múltiplos arquivos (imagens, PDF, Word)</p>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="fecharModal('modalServicoNaoRealizado')" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700">
                    Confirmar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Comprar peças -->
<div id="modalComprarPecas" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Comprar peças</h3>
        <form id="formComprarPecas" onsubmit="processarComprarPecas(event)" enctype="multipart/form-data">
            <input type="hidden" name="solicitacao_id" value="<?= $solicitacao['id'] ?>">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Observação</label>
                <textarea name="observacao" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Informe quais peças são necessárias..."></textarea>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Anexos</label>
                <input type="file" name="anexos[]" multiple accept="image/*,.pdf,.doc,.docx" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <p class="text-xs text-gray-500 mt-1">Você pode selecionar múltiplos arquivos (imagens, PDF, Word)</p>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="fecharModal('modalComprarPecas')" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Confirmar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Reagendar -->
<div id="modalReagendar" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4 my-8">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Reagendar Serviço</h3>
        <p class="text-sm text-gray-600 mb-6">Selecione novas datas e horários preferenciais para reagendamento</p>
        
        <form id="formReagendamento" class="space-y-6">
            <!-- Seleção de Data -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-3">
                    Selecione uma Data
                </label>
                <div class="relative cursor-pointer">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-calendar-alt text-gray-400"></i>
                    </div>
                    <input type="date" id="data_selecionada" name="data_selecionada" 
                           class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 text-sm text-gray-700 cursor-pointer transition-colors"
                           placeholder="dd/mm/2025"
                           min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                           max="<?= date('Y-m-d', strtotime('+30 days')) ?>">
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
                        <input type="radio" name="horario_selecionado" value="08:00-11:00" class="sr-only horario-radio">
                        <div class="border-2 border-gray-200 rounded-lg p-3 text-center cursor-pointer hover:border-yellow-300 transition-colors horario-card">
                            <div class="text-sm font-medium text-gray-900">08h00 às 11h00</div>
                        </div>
                    </label>
                    
                    <label class="relative">
                        <input type="radio" name="horario_selecionado" value="11:00-14:00" class="sr-only horario-radio">
                        <div class="border-2 border-gray-200 rounded-lg p-3 text-center cursor-pointer hover:border-yellow-300 transition-colors horario-card">
                            <div class="text-sm font-medium text-gray-900">11h00 às 14h00</div>
                        </div>
                    </label>
                    
                    <label class="relative">
                        <input type="radio" name="horario_selecionado" value="14:00-17:00" class="sr-only horario-radio">
                        <div class="border-2 border-gray-200 rounded-lg p-3 text-center cursor-pointer hover:border-yellow-300 transition-colors horario-card">
                            <div class="text-sm font-medium text-gray-900">14h00 às 17h00</div>
                        </div>
                    </label>
                    
                    <label class="relative">
                        <input type="radio" name="horario_selecionado" value="17:00-20:00" class="sr-only horario-radio">
                        <div class="border-2 border-gray-200 rounded-lg p-3 text-center cursor-pointer hover:border-yellow-300 transition-colors horario-card">
                            <div class="text-sm font-medium text-gray-900">17h00 às 20h00</div>
                        </div>
                    </label>
                </div>
                <p class="text-xs text-gray-500 mt-3 flex items-center gap-2">
                    <i class="fas fa-info-circle text-blue-500"></i>
                    Selecione uma data e um horário e clique em <strong>Adicionar Horário</strong>. Você pode informar até 3 opções.
                </p>
                <button type="button" id="btn-adicionar-horario"
                        class="mt-4 inline-flex items-center px-4 py-2 bg-yellow-600 text-white text-sm font-medium rounded-lg shadow-sm hover:bg-yellow-700 transition-colors disabled:bg-gray-300 disabled:text-gray-600 disabled:cursor-not-allowed"
                        disabled>
                    <i class="fas fa-plus mr-2 text-xs"></i>Adicionar Horário
                </button>
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

            <input type="hidden" name="novas_datas" id="novas_datas" value="[]">
            
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                <button type="button" onclick="fecharModal('modalReagendar')" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit" id="btn-confirmar-reagendamento" disabled
                        class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 disabled:bg-gray-300 disabled:text-gray-600 disabled:cursor-not-allowed">
                    Confirmar Reagendamento
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Reembolso -->
<div id="modalReembolso" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Solicitar Reembolso</h3>
        <form id="formReembolso" onsubmit="processarReembolso(event)" enctype="multipart/form-data">
            <input type="hidden" name="solicitacao_id" value="<?= $solicitacao['id'] ?>">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Justificativa <span class="text-red-500">*</span></label>
                <textarea name="observacao" rows="4" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent" placeholder="Justifique o motivo do reembolso..."></textarea>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Valor do Reembolso <span class="text-red-500">*</span></label>
                <div class="relative">
                    <span class="absolute left-3 top-2 text-gray-500">R$</span>
                    <input type="number" name="valor_reembolso" step="0.01" min="0" required class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent" placeholder="0,00">
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Anexos</label>
                <input type="file" name="anexos[]" multiple accept="image/*,.pdf,.doc,.docx" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                <p class="text-xs text-gray-500 mt-1">Você pode selecionar múltiplos arquivos (imagens, PDF, Word)</p>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="fecharModal('modalReembolso')" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                    Solicitar Reembolso
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Ausente -->
<div id="modalAusente" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Precisei me ausentar</h3>
        <form id="formAusente" onsubmit="processarAusente(event)" enctype="multipart/form-data">
            <input type="hidden" name="solicitacao_id" value="<?= $solicitacao['id'] ?>">
            <div class="mb-4">
                <p class="text-sm text-gray-600 mb-4">
                    Ao confirmar, sua solicitação será cancelada. Você poderá criar uma nova solicitação quando estiver disponível.
                </p>
                <label class="block text-sm font-medium text-gray-700 mb-2">Observação (opcional)</label>
                <textarea name="observacao" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="Adicione uma observação sobre sua ausência..."></textarea>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Anexos</label>
                <input type="file" name="anexos[]" multiple accept="image/*,.pdf,.doc,.docx" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <p class="text-xs text-gray-500 mt-1">Você pode selecionar múltiplos arquivos (imagens, PDF, Word)</p>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="fecharModal('modalAusente')" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    Confirmar Ausência
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Outros -->
<div id="modalOutros" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Outros</h3>
        <form id="formOutros" onsubmit="processarOutros(event)" enctype="multipart/form-data">
            <input type="hidden" name="solicitacao_id" value="<?= $solicitacao['id'] ?>">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Descreva o motivo <span class="text-red-500">*</span></label>
                <textarea name="observacao" rows="4" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-transparent" placeholder="Descreva o motivo ou situação..."></textarea>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Anexos</label>
                <input type="file" name="anexos[]" multiple accept="image/*,.pdf,.doc,.docx" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-transparent">
                <p class="text-xs text-gray-500 mt-1">Você pode selecionar múltiplos arquivos (imagens, PDF, Word)</p>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="fecharModal('modalOutros')" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                    Enviar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const token = '<?= $token ?>';
let solicitacaoIdAtual = <?= $solicitacao['id'] ?>;

function abrirModal(modalId) {
    document.getElementById(modalId).classList.remove('hidden');
}

function fecharModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
    // Limpar formulários
    const form = document.querySelector('#' + modalId + ' form');
    if (form) {
        form.reset();
    }
}

function abrirModalFoto(url) {
    document.getElementById('fotoModal').src = url;
    document.getElementById('modalFoto').classList.remove('hidden');
}

function fecharModalFoto() {
    document.getElementById('modalFoto').classList.add('hidden');
}

function executarAcao(solicitacaoId, acao) {
    solicitacaoIdAtual = solicitacaoId;
    
    const modais = {
        'concluido': 'modalConcluido',
        'cancelado': 'modalCancelando',
        'servico_nao_realizado': 'modalServicoNaoRealizado',
        'comprar_pecas': 'modalComprarPecas',
        'reembolso': 'modalReembolso',
        'reagendar': 'modalReagendar',
        'ausente': 'modalAusente',
        'outros': 'modalOutros'
    };
    
    const modalId = modais[acao];
    if (modalId) {
        abrirModal(modalId);
    } else {
        alert('Ação não reconhecida');
    }
}

function processarConcluido(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    formData.append('acao', 'concluido');
    
    fetch('<?= url('rastreamento/' . $token . '/acao') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            fecharModal('modalConcluido');
            alert(data.message || 'Solicitação marcada como concluída com sucesso!');
            location.reload();
        } else {
            alert('Erro: ' + (data.message || 'Erro ao processar'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao processar. Tente novamente.');
    });
}

function processarCancelando(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    formData.append('acao', 'cancelado');
    
    fetch('<?= url('rastreamento/' . $token . '/acao') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            fecharModal('modalCancelando');
            alert(data.message || 'Solicitação cancelada com sucesso!');
            location.reload();
        } else {
            alert('Erro: ' + (data.message || 'Erro ao processar'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao processar. Tente novamente.');
    });
}

function processarServicoNaoRealizado(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    formData.append('acao', 'servico_nao_realizado');
    
    fetch('<?= url('rastreamento/' . $token . '/acao') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            fecharModal('modalServicoNaoRealizado');
            alert(data.message || 'Informação registrada com sucesso!');
            location.reload();
        } else {
            alert('Erro: ' + (data.message || 'Erro ao processar'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao processar. Tente novamente.');
    });
}

function processarComprarPecas(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    formData.append('acao', 'comprar_pecas');
    
    fetch('<?= url('rastreamento/' . $token . '/acao') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            fecharModal('modalComprarPecas');
            alert(data.message || 'Solicitação de compra de peças registrada com sucesso!');
            location.reload();
        } else {
            alert('Erro: ' + (data.message || 'Erro ao processar'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao processar. Tente novamente.');
    });
}

function processarReembolso(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    formData.append('acao', 'reembolso');
    
    fetch('<?= url('rastreamento/' . $token . '/acao') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            fecharModal('modalReembolso');
            alert(data.message || 'Solicitação de reembolso registrada com sucesso!');
            location.reload();
        } else {
            alert('Erro: ' + (data.message || 'Erro ao processar'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao processar. Tente novamente.');
    });
}

function processarAusente(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    formData.append('acao', 'ausente');
    
    fetch('<?= url('rastreamento/' . $token . '/acao') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            fecharModal('modalAusente');
            alert(data.message || 'Sua solicitação foi cancelada. Você pode criar uma nova quando estiver disponível.');
            location.reload();
        } else {
            alert('Erro: ' + (data.message || 'Erro ao processar'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao processar. Tente novamente.');
    });
}

function processarOutros(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    formData.append('acao', 'outros');
    
    const observacao = form.querySelector('[name="observacao"]').value.trim();
    if (!observacao) {
        alert('Por favor, descreva o motivo');
        return;
    }
    
    fetch('<?= url('rastreamento/' . $token . '/acao') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            fecharModal('modalOutros');
            alert(data.message || 'Sua mensagem foi registrada. Entraremos em contato em breve.');
            location.reload();
        } else {
            alert('Erro: ' + (data.message || 'Erro ao processar'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao processar. Tente novamente.');
    });
}

// Sistema de reagendamento
let horariosSelecionados = [];

document.addEventListener('DOMContentLoaded', function() {
    const dataInput = document.getElementById('data_selecionada');
    const horarioRadios = document.querySelectorAll('input[name="horario_selecionado"]');
    const btnAdicionar = document.getElementById('btn-adicionar-horario');
    const btnConfirmar = document.getElementById('btn-confirmar-reagendamento');
    const listaHorarios = document.getElementById('lista-horarios');
    const contadorHorarios = document.getElementById('contador-horarios');
    const divHorariosSelecionados = document.getElementById('horarios-selecionados');
    const novasDatasInput = document.getElementById('novas_datas');
    
    // Verificar se data e horário estão selecionados
    function verificarSelecao() {
        const dataSelecionada = dataInput.value;
        const horarioSelecionado = document.querySelector('input[name="horario_selecionado"]:checked');
        
        if (dataSelecionada && horarioSelecionado) {
            btnAdicionar.disabled = false;
        } else {
            btnAdicionar.disabled = true;
        }
    }
    
    // Adicionar horário à lista
    btnAdicionar.addEventListener('click', function() {
        const dataSelecionada = dataInput.value;
        const horarioSelecionado = document.querySelector('input[name="horario_selecionado"]:checked');
        
        if (!dataSelecionada || !horarioSelecionado) {
            alert('Por favor, selecione uma data e um horário');
            return;
        }
        
        if (horariosSelecionados.length >= 3) {
            alert('Você pode selecionar no máximo 3 horários');
            return;
        }
        
        // Converter data para formato brasileiro
        const dataObj = new Date(dataSelecionada + 'T00:00:00');
        const dia = String(dataObj.getDate()).padStart(2, '0');
        const mes = String(dataObj.getMonth() + 1).padStart(2, '0');
        const ano = dataObj.getFullYear();
        const dataFormatada = `${dia}/${mes}/${ano}`;
        
        const horario = horarioSelecionado.value;
        const [horaInicio, horaFim] = horario.split('-');
        const horarioFormatado = `${dataFormatada} - ${horaInicio}-${horaFim}`;
        
        // Verificar se já existe
        if (horariosSelecionados.includes(horarioFormatado)) {
            alert('Este horário já foi adicionado');
            return;
        }
        
        horariosSelecionados.push(horarioFormatado);
        atualizarListaHorarios();
        
        // Limpar seleção
        dataInput.value = '';
        horarioRadios.forEach(radio => radio.checked = false);
        verificarSelecao();
    });
    
    // Atualizar lista de horários
    function atualizarListaHorarios() {
        listaHorarios.innerHTML = '';
        contadorHorarios.textContent = horariosSelecionados.length;
        
        if (horariosSelecionados.length > 0) {
            divHorariosSelecionados.classList.remove('hidden');
            btnConfirmar.disabled = false;
            
            horariosSelecionados.forEach((horario, index) => {
                const div = document.createElement('div');
                div.className = 'flex items-center justify-between p-3 bg-yellow-50 border border-yellow-200 rounded-lg';
                div.innerHTML = `
                    <span class="text-sm font-medium text-gray-900">${horario}</span>
                    <button type="button" onclick="removerHorario(${index})" class="text-red-600 hover:text-red-800">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                listaHorarios.appendChild(div);
            });
            
            novasDatasInput.value = JSON.stringify(horariosSelecionados);
        } else {
            divHorariosSelecionados.classList.add('hidden');
            btnConfirmar.disabled = true;
            novasDatasInput.value = '[]';
        }
    }
    
    // Remover horário
    window.removerHorario = function(index) {
        horariosSelecionados.splice(index, 1);
        atualizarListaHorarios();
    };
    
    // Event listeners
    dataInput.addEventListener('change', verificarSelecao);
    horarioRadios.forEach(radio => {
        radio.addEventListener('change', verificarSelecao);
    });
    
    // Estilo para horários selecionados
    horarioRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            horarioRadios.forEach(r => {
                const card = r.closest('label').querySelector('.horario-card');
                if (r.checked) {
                    card.classList.add('border-yellow-500', 'bg-yellow-50');
                } else {
                    card.classList.remove('border-yellow-500', 'bg-yellow-50');
                }
            });
        });
    });
    
    // Processar formulário de reagendamento
    const formReagendamento = document.getElementById('formReagendamento');
    if (formReagendamento) {
        formReagendamento.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (horariosSelecionados.length === 0) {
                alert('Por favor, adicione pelo menos um horário');
                return;
            }
            
            // Primeiro, buscar ou criar token de reagendamento via AJAX
            fetch('<?= url('rastreamento/' . $token . '/reagendar') ?>', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.token) {
                    const tokenReagendamento = data.token;
                    // Enviar dados de reagendamento
                    return fetch('<?= url('reagendamento-horario') ?>?token=' + encodeURIComponent(tokenReagendamento), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            novas_datas: JSON.stringify(horariosSelecionados)
                        }),
                        redirect: 'follow'
                    });
                } else {
                    throw new Error(data.message || 'Erro ao obter token de reagendamento');
                }
            })
            .then(response => {
                if (response.redirected || response.url) {
                    // Sucesso - redirecionar para página de sucesso ou recarregar
                    const successUrl = response.url || response.headers.get('Location');
                    if (successUrl && successUrl.includes('success')) {
                        window.location.href = successUrl;
                    } else {
                        fecharModal('modalReagendar');
                        alert('Reagendamento solicitado com sucesso! Entraremos em contato para confirmar.');
                        location.reload();
                    }
                } else {
                    return response.text().then(text => {
                        // Tentar parsear como JSON
                        try {
                            return JSON.parse(text);
                        } catch {
                            // Se não for JSON, verificar se é HTML de sucesso
                            if (text.includes('sucesso') || text.includes('success')) {
                                fecharModal('modalReagendar');
                                alert('Reagendamento solicitado com sucesso! Entraremos em contato para confirmar.');
                                location.reload();
                            } else {
                                throw new Error('Resposta inválida do servidor');
                            }
                        }
                    });
                }
            })
            .then(data => {
                if (data) {
                    if (data.success !== false) {
                        fecharModal('modalReagendar');
                        alert(data.message || 'Reagendamento solicitado com sucesso! Entraremos em contato para confirmar.');
                        location.reload();
                    } else {
                        alert('Erro: ' + (data.message || 'Erro ao processar reagendamento'));
                    }
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao processar reagendamento. Tente novamente ou entre em contato com a imobiliária.');
            });
        });
    }
    
    // Limpar ao fechar modal
    const modalReagendar = document.getElementById('modalReagendar');
    if (modalReagendar) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.target.classList.contains('hidden')) {
                    // Resetar formulário
                    horariosSelecionados = [];
                    atualizarListaHorarios();
                    dataInput.value = '';
                    horarioRadios.forEach(radio => {
                        radio.checked = false;
                        const card = radio.closest('label').querySelector('.horario-card');
                        card.classList.remove('border-yellow-500', 'bg-yellow-50');
                    });
                }
            });
        });
        observer.observe(modalReagendar, { attributes: true, attributeFilter: ['class'] });
    }
});

// Fechar modal ao clicar fora
document.addEventListener('click', function(event) {
    if (event.target.classList.contains('bg-opacity-50')) {
        const modals = document.querySelectorAll('.fixed.inset-0.bg-black');
        modals.forEach(modal => {
            if (event.target === modal) {
                modal.classList.add('hidden');
}
        });
    }
});

// Função para salvar observações do segurado
function salvarObservacoes(solicitacaoId) {
    const observacoes = document.getElementById('observacoes-segurado').value;
    const token = new URLSearchParams(window.location.search).get('token');
    
    if (!token) {
        alert('Token não encontrado. Recarregue a página e tente novamente.');
        return;
    }
    
    const button = event?.target || document.querySelector('button[onclick*="salvarObservacoes"]');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Salvando...';
    button.disabled = true;
    
    // Enviar token tanto na query string quanto no body JSON para garantir
    fetch(`<?= url('atualizar-observacoes') ?>?token=${encodeURIComponent(token)}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            token: token,
            observacoes: observacoes
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Observações salvas com sucesso!');
        } else {
            alert('Erro: ' + (data.message || 'Erro ao salvar observações'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao salvar observações. Tente novamente.');
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}
</script>

<style>
/* Estilo para horários selecionados */
.horario-card {
    transition: all 0.2s;
}

.horario-card.border-yellow-500 {
    border-color: #eab308;
    background-color: #fef9c3;
}

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

/* Timeline - linha vertical completa */
.timeline-container .timeline-line {
    height: 100%;
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/locatario.php';
?>

