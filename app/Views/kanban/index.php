<?php
$title = 'Kanban Board';
$currentPage = 'kanban';
$pageTitle = 'Kanban - Gerenciamento de Solicitações';
ob_start();
?>

<style>
@keyframes blink-border {
    0%, 100% {
        border-color: #FCD34D;
        box-shadow: 0 0 0 0 rgba(252, 211, 77, 0.7);
    }
    50% {
        border-color: #FBBF24;
        box-shadow: 0 0 0 4px rgba(252, 211, 77, 0.3);
    }
}

.kanban-card-mensagem-nao-lida {
    border-color: #FCD34D !important;
    animation: blink-border 1.5s ease-in-out infinite;
}
</style>

<!-- Filtros -->
<div class="mb-6 bg-white rounded-lg shadow-sm p-4">
    <div class="flex items-center justify-between">
        <h3 class="text-lg font-medium text-gray-900">Filtros</h3>
        <form method="GET" action="<?= url('admin/kanban') ?>" class="flex gap-3">
            <select name="imobiliaria_id" class="px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent" onchange="this.form.submit()">
                <option value="">Todas as Imobiliárias</option>
                <?php foreach ($imobiliarias as $imob): ?>
                <option value="<?= $imob['id'] ?>" <?= $imobiliariaId == $imob['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($imob['nome']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            
            <?php if ($imobiliariaId): ?>
            <a href="<?= url('admin/kanban') ?>" class="px-3 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
                <i class="fas fa-times mr-1"></i> Limpar Filtros
            </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Kanban Board -->
<div class="flex gap-4 overflow-x-auto pb-4">
    <?php foreach ($statusKanban as $status): ?>
    <div class="kanban-column flex-shrink-0 w-80 bg-gray-50 rounded-lg p-4">
        <!-- Header da Coluna -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center">
                <div class="w-3 h-3 rounded-full mr-2" style="background-color: <?= $status['cor'] ?>"></div>
                <h3 class="font-medium text-gray-900"><?= htmlspecialchars($status['nome']) ?></h3>
            </div>
            <span class="bg-gray-200 text-gray-700 text-xs px-2 py-1 rounded-full">
                <?= count($solicitacoesPorStatus[$status['id']] ?? []) ?>
            </span>
        </div>
        
        <!-- Cards da Coluna -->
        <div class="kanban-cards space-y-3 min-h-32" data-status-id="<?= $status['id'] ?>">
            <?php 
            $solicitacoes = $solicitacoesPorStatus[$status['id']] ?? [];
            if (empty($solicitacoes)): 
            ?>
            <div class="text-center py-8 text-gray-400 text-sm">
                <i class="fas fa-inbox text-2xl mb-2 block"></i>
                Nenhuma solicitação
            </div>
            <?php else: ?>
                <?php foreach ($solicitacoes as $solicitacao): ?>
                <div class="kanban-card bg-white rounded-lg shadow-sm p-4 cursor-pointer hover:shadow-md transition-shadow border-l-4" 
                     style="border-color: <?= $status['cor'] ?>"
                     data-solicitacao-id="<?= $solicitacao['id'] ?>"
                     data-status-id="<?= $solicitacao['status_id'] ?>"
                     id="kanban-card-<?= $solicitacao['id'] ?>">
                    
                    <!-- Header do Card -->
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <h4 class="font-semibold text-gray-900 text-sm">
                                    <?= htmlspecialchars($solicitacao['numero_solicitacao'] ?? 'KSS' . $solicitacao['id']) ?>
                                </h4>
                                <span class="chat-badge-<?= $solicitacao['id'] ?> hidden ml-1 px-1.5 py-0.5 text-xs font-bold text-white bg-red-500 rounded-full" title="Mensagens não lidas"></span>
                                <?php if (!empty($solicitacao['chat_whatsapp_instance_id']) && !empty($solicitacao['chat_atendimento_ativo'])): ?>
                                    <i class="fab fa-whatsapp text-green-500 text-sm" 
                                       title="WhatsApp ativo: <?= htmlspecialchars($solicitacao['whatsapp_instance_nome'] ?? 'Instância ativa') ?>"></i>
                                <?php endif; ?>
                                <?php if (!empty($solicitacao['numero_contrato'])): ?>
                                    <span class="text-xs text-gray-500">
                                        Contrato: <?= htmlspecialchars($solicitacao['numero_contrato']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="flex items-center text-xs text-gray-600 mt-1">
                                <i class="fas fa-wrench w-3 mr-1 text-gray-400"></i>
                                <span class="truncate"><?= htmlspecialchars($solicitacao['categoria_nome'] ?? 'Sem categoria') ?></span>
                            </div>
                        </div>
                        <div class="flex flex-col items-end gap-1">
                            <?php if (!empty($solicitacao['imobiliaria_logo'])): ?>
                                <img src="<?= url('Public/uploads/logos/' . $solicitacao['imobiliaria_logo']) ?>" 
                                     alt="<?= htmlspecialchars($solicitacao['imobiliaria_nome'] ?? 'Imobiliária') ?>" 
                                     class="h-7 w-auto"
                                     onerror="this.style.display='none';">
                            <?php endif; ?>
                            <?php if (!empty($solicitacao['condicao_nome'])): ?>
                                <span class="inline-block px-2 py-0.5 rounded-md text-xs font-medium" 
                                      style="background-color: <?= htmlspecialchars($solicitacao['condicao_cor'] ?? '#6B7280') ?>20; color: <?= htmlspecialchars($solicitacao['condicao_cor'] ?? '#6B7280') ?>">
                                    <?= htmlspecialchars($solicitacao['condicao_nome']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Informações do Card -->
                    <div class="space-y-1 text-xs text-gray-600">
                        
                        <?php 
                        // Verificar se há múltiplas subcategorias nas observações
                        $subcategoriasAdicionais = [];
                        if (!empty($solicitacao['observacoes'])) {
                            // Tentar extrair IDs das subcategorias do formato [SUBCATEGORIAS_IDS: [...]]
                            if (preg_match('/\[SUBCATEGORIAS_IDS:\s*(\[[^\]]+\])\]/', $solicitacao['observacoes'], $matches)) {
                                $subcategoriasIds = json_decode($matches[1], true);
                                if (is_array($subcategoriasIds) && count($subcategoriasIds) > 1) {
                                    $subcategoriaModel = new \App\Models\Subcategoria();
                                    foreach ($subcategoriasIds as $subId) {
                                        $sub = $subcategoriaModel->find($subId);
                                        if ($sub && !empty($sub['nome'])) {
                                            $subcategoriasAdicionais[] = $sub['nome'];
                                        }
                                    }
                                }
                            }
                            // Alternativa: extrair da lista formatada "Serviços solicitados (X):"
                            elseif (preg_match('/Serviços solicitados \(\d+\):\s*\n((?:\d+\.\s*[^\n]+\n?)+)/', $solicitacao['observacoes'], $matches)) {
                                $linhas = explode("\n", trim($matches[1]));
                                foreach ($linhas as $linha) {
                                    if (preg_match('/^\d+\.\s*(.+)$/', trim($linha), $linhaMatch)) {
                                        $subcategoriasAdicionais[] = trim($linhaMatch[1]);
                                    }
                                }
                            }
                        }
                        
                        if (!empty($subcategoriasAdicionais) && count($subcategoriasAdicionais) > 1): 
                        ?>
                        <div class="flex items-start">
                            <i class="fas fa-list w-4 mr-1 text-gray-400 mt-0.5 flex-shrink-0"></i>
                            <div class="flex-1 min-w-0">
                                <span class="text-xs font-medium text-gray-700 block mb-1">Serviços:</span>
                                <ul class="list-disc list-inside text-xs text-gray-600 space-y-0.5">
                                    <?php foreach ($subcategoriasAdicionais as $nome): ?>
                                        <li class="truncate"><?= htmlspecialchars($nome) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                        <?php elseif (!empty($solicitacao['subcategoria_nome'])): ?>
                        <div class="flex items-center">
                            <i class="fas fa-list w-4 mr-1 text-gray-400"></i>
                            <span class="truncate"><?= htmlspecialchars($solicitacao['subcategoria_nome']) ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Protocolo da Seguradora -->
                        <?php if (!empty($solicitacao['protocolo_seguradora'])): ?>
                        <div class="flex items-center">
                            <i class="fas fa-hashtag w-4 mr-1 text-gray-400"></i>
                            <span class="truncate text-xs text-gray-500">
                                Protocolo: <?= htmlspecialchars($solicitacao['protocolo_seguradora']) ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="flex items-center">
                            <i class="fas fa-user w-4 mr-1 text-gray-400"></i>
                            <span class="truncate"><?= htmlspecialchars($solicitacao['locatario_nome'] ?? 'Não informado') ?></span>
                        </div>
                        
                        <div class="flex items-center">
                            <i class="fas fa-map-marker-alt w-4 mr-1 text-gray-400"></i>
                            <span class="truncate">
                                <?php 
                                $endereco = '';
                                if (!empty($solicitacao['imovel_endereco'])) {
                                    $endereco = $solicitacao['imovel_endereco'];
                                    if (!empty($solicitacao['imovel_numero'])) {
                                        $endereco .= ', ' . $solicitacao['imovel_numero'];
                                    }
                                }
                                echo htmlspecialchars($endereco ?: 'Endereço não informado');
                                ?>
                            </span>
                        </div>
                        
                        <div class="flex items-center">
                            <i class="fas fa-calendar w-4 mr-1 text-gray-400"></i>
                            <span title="Data e Hora de Registro"><?= date('d/m/Y H:i', strtotime($solicitacao['created_at'])) ?></span>
                        </div>
                        
                        <!-- Datas e Horários Selecionados pelo Locatário -->
                        <?php
                        $horariosExibir = [];
                        
                        // Se houver horário confirmado, mostrar apenas esse
                        if (!empty($solicitacao['horario_confirmado_raw'])) {
                            // Formato esperado: "13/11/2025 - 08:00-11:00"
                            $horariosExibir[] = $solicitacao['horario_confirmado_raw'];
                        } elseif (!empty($solicitacao['confirmed_schedules'])) {
                            // Se houver confirmed_schedules, usar o raw de cada um
                            $confirmed = json_decode($solicitacao['confirmed_schedules'], true);
                            if (is_array($confirmed)) {
                                foreach ($confirmed as $conf) {
                                    if (!empty($conf['raw'])) {
                                        $horariosExibir[] = $conf['raw'];
                                    }
                                }
                            }
                        } else {
                            // Se não houver confirmado, buscar horários do locatário
                            $horariosLocatario = [];
                            
                            // Verificar se horarios_indisponiveis = 1 (horários originais em datas_opcoes)
                            if (!empty($solicitacao['horarios_indisponiveis']) && !empty($solicitacao['datas_opcoes'])) {
                                $horariosLocatario = json_decode($solicitacao['datas_opcoes'], true);
                            } elseif (!empty($solicitacao['horarios_opcoes'])) {
                                $horariosLocatario = json_decode($solicitacao['horarios_opcoes'], true);
                            }
                            
                            // Processar horários para formato de exibição
                            if (is_array($horariosLocatario)) {
                                foreach ($horariosLocatario as $horario) {
                                    if (is_string($horario)) {
                                        // Formato já esperado: "13/11/2025 - 08:00-11:00"
                                        if (strpos($horario, ' - ') !== false) {
                                            $horariosExibir[] = $horario;
                                        } 
                                        // Formato timestamp: "2025-11-13 08:00:00"
                                        elseif (preg_match('/^(\d{4})-(\d{2})-(\d{2})\s+(\d{2}):(\d{2}):\d{2}$/', $horario, $matches)) {
                                            $data = $matches[3] . '/' . $matches[2] . '/' . $matches[1];
                                            
                                            // Determinar faixa de horário baseado na hora
                                            $horaInt = (int)$matches[4];
                                            if ($horaInt >= 8 && $horaInt < 11) {
                                                $faixa = '08:00-11:00';
                                            } elseif ($horaInt >= 11 && $horaInt < 14) {
                                                $faixa = '11:00-14:00';
                                            } elseif ($horaInt >= 14 && $horaInt < 17) {
                                                $faixa = '14:00-17:00';
                                            } elseif ($horaInt >= 17 && $horaInt < 20) {
                                                $faixa = '17:00-20:00';
                                            } else {
                                                $faixa = $matches[4] . ':' . $matches[5] . '-20:00';
                                            }
                                            
                                            $horariosExibir[] = $data . ' - ' . $faixa;
                                        }
                                    }
                                }
                            }
                        }
                        
                        // Limitar a 3 horários
                        $horariosExibir = array_slice($horariosExibir, 0, 3);
                        
                        if (!empty($horariosExibir)):
                            $isAgendado = !empty($solicitacao['horario_confirmado_raw']) || !empty($solicitacao['confirmed_schedules']);
                        ?>
                        <div class="mt-2 pt-2 border-t border-gray-200">
                            <?php if ($isAgendado): ?>
                                <!-- Quando já foi agendado -->
                                <div class="text-xs">
                                    <div class="text-gray-500 mb-1">Serviço agendado em:</div>
                                    <?php foreach ($horariosExibir as $horario): ?>
                                        <?php
                                        $partes = explode(' - ', $horario);
                                        $data = $partes[0] ?? '';
                                        $horarioTexto = $partes[1] ?? '';
                                        
                                        // Converter formato de horário se necessário
                                        if (preg_match('/^(\d{2}):(\d{2})-(\d{2}):(\d{2})$/', $horarioTexto, $matches)) {
                                            $horarioTexto = $matches[1] . 'h' . $matches[2] . ' às ' . $matches[3] . 'h' . $matches[4];
                                        }
                                        ?>
                                        <div class="font-medium text-gray-900"><?= htmlspecialchars($data) ?></div>
                                        <div class="text-gray-600"><?= htmlspecialchars($horarioTexto) ?></div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <!-- Quando ainda não foi agendado (múltiplas opções) -->
                                <!-- Linha de Datas -->
                                <div class="grid grid-cols-3 gap-2 text-xs mb-1">
                                    <?php foreach ($horariosExibir as $index => $horario): ?>
                                        <?php
                                        $partes = explode(' - ', $horario);
                                        $data = $partes[0] ?? '';
                                        ?>
                                        <div class="font-medium text-gray-900 text-center"><?= htmlspecialchars($data) ?></div>
                                    <?php endforeach; ?>
                                    <?php for ($i = count($horariosExibir); $i < 3; $i++): ?>
                                        <div></div>
                                    <?php endfor; ?>
                                </div>
                                <!-- Linha de Horários -->
                                <div class="grid grid-cols-3 gap-2 text-xs">
                                    <?php foreach ($horariosExibir as $index => $horario): ?>
                                        <?php
                                        $partes = explode(' - ', $horario);
                                        $horarioTexto = $partes[1] ?? '';
                                        
                                        // Converter formato de horário se necessário (08:00-11:00 -> 08h00 às 11h00)
                                        if (preg_match('/^(\d{2}):(\d{2})-(\d{2}):(\d{2})$/', $horarioTexto, $matches)) {
                                            $horarioTexto = $matches[1] . 'h' . $matches[2] . ' às ' . $matches[3] . 'h' . $matches[4];
                                        }
                                        ?>
                                        <div class="text-gray-600 text-center whitespace-nowrap truncate" title="<?= htmlspecialchars($horarioTexto) ?>"><?= htmlspecialchars($horarioTexto) ?></div>
                                    <?php endforeach; ?>
                                    <?php for ($i = count($horariosExibir); $i < 3; $i++): ?>
                                        <div></div>
                                    <?php endfor; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Prioridade / Emergencial -->
                    <?php 
                    $isEmergencial = !empty($solicitacao['subcategoria_is_emergencial']) || !empty($solicitacao['is_emergencial']);
                    $isEmergencialForaHorario = !empty($solicitacao['is_emergencial_fora_horario']);
                    $mostrarPrioridade = false;
                    $textoPrioridade = '';
                    $corPrioridade = '';
                    
                    if ($isEmergencial) {
                        $mostrarPrioridade = true;
                        $textoPrioridade = 'Emergencial';
                        $corPrioridade = 'bg-red-100 text-red-800';
                    } elseif (isset($solicitacao['prioridade']) && $solicitacao['prioridade'] !== 'NORMAL') {
                        $mostrarPrioridade = true;
                        $textoPrioridade = $solicitacao['prioridade'];
                        $corPrioridade = $solicitacao['prioridade'] === 'ALTA' ? 'bg-red-100 text-red-800' : 
                                       ($solicitacao['prioridade'] === 'MEDIA' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800');
                    }
                    ?>
                    <!-- Badge de Validação (tipo_qualificacao) -->
                    <?php if (!empty($solicitacao['tipo_qualificacao'])): 
                        $tipoQual = $solicitacao['tipo_qualificacao'];
                        $textoQual = '';
                        $corQual = '';
                        $iconeQual = '';
                        
                        switch($tipoQual) {
                            case 'BOLSAO':
                                $textoQual = 'Bolsão';
                                $corQual = 'bg-blue-100 text-blue-800';
                                $iconeQual = 'fa-check-circle';
                                break;
                            case 'CORTESIA':
                                $textoQual = 'Cortesia';
                                $corQual = 'bg-green-100 text-green-800';
                                $iconeQual = 'fa-gift';
                                break;
                            case 'REGRA_2':
                                $textoQual = 'Dt assinatura';
                                $corQual = 'bg-purple-100 text-purple-800';
                                $iconeQual = 'fa-calendar-check';
                                break;
                            default:
                                $textoQual = '';
                        }
                    ?>
                    <?php if (!empty($textoQual)): ?>
                    <div class="mt-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $corQual ?>">
                            <i class="fas <?= $iconeQual ?> mr-1"></i>
                            <?= htmlspecialchars($textoQual) ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php if ($mostrarPrioridade): ?>
                    <div class="mt-3 flex items-center gap-2 flex-wrap">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?= $corPrioridade ?>">
                            <i class="fas fa-<?= $isEmergencial ? 'exclamation-triangle' : 'exclamation-circle' ?> mr-1"></i>
                            <?= htmlspecialchars($textoPrioridade) ?>
                        </span>
                        <?php if ($isEmergencialForaHorario): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                <i class="fas fa-phone mr-1"></i>
                                0800
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Offcanvas para Detalhes -->
<div id="detalhesOffcanvas" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 transition-opacity" onclick="fecharDetalhes()"></div>
    <div id="offcanvasPanel" class="fixed right-0 top-0 h-full w-full md:w-[90%] lg:w-[900px] bg-gray-50 shadow-xl transform translate-x-full transition-transform duration-300 ease-in-out overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-gray-200 z-10">
            <div class="px-6 py-4">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-file-alt text-gray-600"></i>
                        <h2 class="text-xl font-bold text-gray-900">Detalhes da Solicitação</h2>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <button onclick="toggleMenuCopiar()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors flex items-center">
                                <i class="fas fa-copy mr-2"></i>
                                Copiar Informações
                                <i class="fas fa-chevron-down ml-2 text-xs"></i>
                            </button>
                            <div id="menuCopiar" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                                <button onclick="copiarInformacoes()" class="w-full text-left px-4 py-2 hover:bg-gray-100 text-gray-700 text-sm flex items-center">
                                    <i class="fas fa-copy mr-2"></i>
                                    Copiar
                                </button>
                                <button onclick="enviarInformacoesNoChat()" id="btnEnviarNoChat" class="w-full text-left px-4 py-2 hover:bg-gray-100 text-gray-700 text-sm flex items-center border-t border-gray-200">
                                    <i class="fab fa-whatsapp mr-2 text-green-500"></i>
                                    Enviar no Chat
                                </button>
                            </div>
                        </div>
                        <button onclick="abrirLinksAcoes()" class="px-4 py-2 bg-blue-100 hover:bg-blue-200 text-blue-700 text-sm font-medium rounded-lg transition-colors">
                            <i class="fas fa-link mr-2"></i>
                            Links de Ações
                        </button>
                        <button onclick="abrirChatDireto()" class="px-4 py-2 bg-green-100 hover:bg-green-200 text-green-700 text-sm font-medium rounded-lg transition-colors relative">
                            <i class="fab fa-whatsapp mr-2"></i>
                            Chat WhatsApp
                            <span id="chatBadgeHeader" class="hidden ml-2 px-2 py-0.5 text-xs font-bold text-white bg-red-500 rounded-full">0</span>
                        </button>
                        <button onclick="fecharDetalhes()" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                </div>
                <!-- Abas: Detalhes e Chat -->
                <div class="flex border-b border-gray-200">
                    <button id="tabDetalhes" onclick="mostrarAba('detalhes')" class="px-4 py-2 font-medium text-sm border-b-2 border-blue-600 text-blue-600">
                        <i class="fas fa-info-circle mr-2"></i>
                        Detalhes
                    </button>
                    <button id="tabChat" onclick="mostrarAba('chat')" class="px-4 py-2 font-medium text-sm border-b-2 border-transparent text-gray-600 hover:text-gray-900 relative">
                        <i class="fab fa-whatsapp mr-2"></i>
                        Chat WhatsApp
                        <span id="chatBadge" class="hidden ml-2 px-2 py-0.5 text-xs font-bold text-white bg-red-500 rounded-full">0</span>
                    </button>
                </div>
            </div>
        </div>
        <div id="offcanvasContent" class="p-6">
            <div id="loadingContent" class="flex items-center justify-center py-12">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin text-4xl text-blue-600 mb-4"></i>
                    <p class="text-gray-600">Carregando detalhes...</p>
                </div>
            </div>
            <div id="detalhesContent" class="hidden"></div>
            <!-- Conteúdo do Chat -->
            <div id="chatContent" class="hidden flex flex-col" style="height: calc(100vh - 200px); max-height: calc(100vh - 200px);">
                <!-- Seleção de Instância WhatsApp -->
                <div id="chatInstanceSelector" class="flex-shrink-0 mb-4 p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-gray-700">
                            <i class="fab fa-whatsapp mr-2 text-green-500"></i>
                            Instância WhatsApp <span class="text-red-500">*</span>
                        </label>
                        <div class="flex items-center gap-2">
                            <button onclick="abrirModalHistorico()" class="text-xs px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors">
                                <i class="fas fa-history mr-1"></i>Histórico
                            </button>
                            <button id="btnEncerrarAtendimento" class="hidden text-xs px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 transition-colors">
                                <i class="fas fa-times-circle mr-1"></i>Encerrar Atendimento
                            </button>
                        </div>
                    </div>
                    <select id="chatWhatsappInstance" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 bg-gray-100" disabled>
                        <option value="">Instância selecionada no modal</option>
                    </select>
                    <p id="chatInstanceInfo" class="text-xs text-gray-500 mt-2">Instância selecionada no modal de seleção</p>
                </div>
                <!-- Container para mensagens e empty state -->
                <div class="flex-1 flex flex-col min-h-0 overflow-hidden" style="min-height: 400px;">
                    <!-- Área de Mensagens -->
                    <div id="chatMessages" class="flex-1 overflow-y-auto mb-6 p-4 bg-white rounded-lg border border-gray-200 space-y-3 hidden" style="min-height: 400px; max-height: calc(100vh - 400px);">
                        <div class="text-center text-gray-500 py-8">
                            <i class="fab fa-whatsapp text-4xl mb-2 text-gray-300"></i>
                            <p>Carregando mensagens...</p>
                        </div>
                    </div>
                    <!-- Mensagem quando não há conversa -->
                    <div id="chatEmptyState" class="flex-1 flex items-center justify-center mb-6 p-8 bg-white rounded-lg border border-gray-200 overflow-y-auto" style="min-height: 400px;">
                        <div class="text-center">
                            <i class="fab fa-whatsapp text-6xl mb-4 text-gray-300"></i>
                            <p class="text-gray-600 mb-2">Nenhuma conversa iniciada</p>
                            <p class="text-sm text-gray-500">Selecione uma instância WhatsApp acima para começar a conversar</p>
                        </div>
                    </div>
                </div>
                <!-- Input de Mensagem -->
                <div id="chatInputContainer" class="flex-shrink-0 flex gap-2 mt-6 mb-4 hidden">
                    <textarea id="chatMessageInput" placeholder="Digite sua mensagem... (Enter para enviar, Shift+Enter para nova linha)" 
                           class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 resize-none"
                           rows="1"
                           style="min-height: 42px; max-height: 120px;"
                           onkeydown="handleChatInputKeydown(event)"
                           oninput="this.style.height = 'auto'; this.style.height = Math.min(this.scrollHeight, 120) + 'px';"></textarea>
                    <button onclick="enviarMensagemChat()" 
                            class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors self-end">
                        <i class="fab fa-whatsapp mr-2"></i>
                        Enviar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Seleção de Instância WhatsApp -->
<div id="modalSelecionarInstancia" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 transition-opacity" onclick="fecharModalInstancia()"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <!-- Header do Modal -->
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class="fab fa-whatsapp text-green-500 text-xl"></i>
                    <h3 class="text-lg font-semibold text-gray-900">Selecionar Instância WhatsApp</h3>
                </div>
                <button onclick="fecharModalInstancia()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <!-- Conteúdo do Modal -->
            <div class="px-6 py-4">
                <p class="text-sm text-gray-600 mb-4">Selecione uma instância WhatsApp disponível para iniciar o atendimento:</p>
                <div id="instanciasDisponiveisList" class="space-y-2 max-h-96 overflow-y-auto">
                    <div class="text-center py-8">
                        <i class="fas fa-spinner fa-spin text-3xl text-gray-400 mb-3"></i>
                        <p class="text-gray-600">Carregando instâncias...</p>
                    </div>
                </div>
            </div>
            
            <!-- Footer do Modal -->
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                <button onclick="fecharModalInstancia()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Histórico de Atendimentos -->
<div id="modalHistoricoAtendimentos" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 transition-opacity" onclick="fecharModalHistorico()"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-hidden flex flex-col">
            <!-- Header do Modal -->
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class="fas fa-history text-blue-600 text-xl"></i>
                    <h3 class="text-lg font-semibold text-gray-900">Histórico de Atendimentos</h3>
                </div>
                <button onclick="fecharModalHistorico()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <!-- Conteúdo do Modal -->
            <div class="flex-1 overflow-y-auto px-6 py-4">
                <div id="historicoAtendimentosContent" class="space-y-3">
                    <div class="text-center py-8">
                        <i class="fas fa-spinner fa-spin text-3xl text-gray-400 mb-3"></i>
                        <p class="text-gray-600">Carregando histórico...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Links de Ações -->
<div id="modalLinksAcoes" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 transition-opacity" onclick="fecharModalLinksAcoes()"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full max-h-[90vh] overflow-hidden flex flex-col">
            <!-- Header do Modal -->
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class="fas fa-link text-blue-600 text-xl"></i>
                    <h3 class="text-lg font-semibold text-gray-900">Links de Ações</h3>
                </div>
                <button onclick="fecharModalLinksAcoes()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <!-- Conteúdo do Modal -->
            <div class="flex-1 overflow-y-auto px-6 py-4">
                <div id="linksAcoesContent" class="space-y-3">
                    <div class="text-center py-8">
                        <i class="fas fa-spinner fa-spin text-3xl text-gray-400 mb-3"></i>
                        <p class="text-gray-600">Carregando links...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'app/Views/layouts/admin.php';
?>

<script>
// Permitir drag visual mas fazer voltar e mostrar mensagem
document.querySelectorAll('.kanban-card').forEach(card => {
    // Adicionar cursor pointer para indicar que é clicável
    card.style.cursor = 'pointer';
    
    // Permitir drag visual
    card.setAttribute('draggable', 'true');
    
    let originalColumn = null;
    let originalIndex = -1;
    let isDragging = false;
    
    // Quando começar a arrastar
    card.addEventListener('dragstart', function(e) {
        isDragging = true;
        originalColumn = this.parentElement;
        
        // Guardar a posição original (índice entre os irmãos)
        const cards = Array.from(originalColumn.children);
        originalIndex = cards.indexOf(this);
        
        // Adicionar classe visual de arrastando
        this.style.opacity = '0.5';
        this.style.cursor = 'grabbing';
        
        // Armazenar dados do drag
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/html', this.outerHTML);
    });
    
    // Quando terminar de arrastar
    card.addEventListener('dragend', function(e) {
        isDragging = false;
        this.style.opacity = '1';
        this.style.cursor = 'pointer';
        
        // Sempre fazer o card voltar para a posição original com animação
        if (originalColumn && originalIndex >= 0) {
            const currentColumn = this.parentElement;
            const currentCards = Array.from(currentColumn.children);
            const originalCards = Array.from(originalColumn.children);
            
            // Verificar se o card foi movido
            const wasMoved = currentColumn !== originalColumn || 
                           (currentColumn === originalColumn && currentCards.indexOf(this) !== originalIndex);
            
            if (wasMoved) {
                this.style.transition = 'all 0.3s ease-in-out';
                
                // Voltar para a posição original
                // Remover o card de onde está agora (se estiver em outra coluna)
                if (currentColumn !== originalColumn && currentColumn.contains(this)) {
                    this.remove();
                }
                
                // Obter lista atualizada de cards da coluna original (sem o card que está sendo movido)
                const cardsInOriginal = Array.from(originalColumn.children).filter(c => c !== this);
                
                // Inserir na posição original
                if (originalIndex < cardsInOriginal.length) {
                    // Inserir antes do card que está na posição original
                    originalColumn.insertBefore(this, cardsInOriginal[originalIndex]);
                } else {
                    // Se o índice é maior que o número de cards, adicionar no final
                    originalColumn.appendChild(this);
                }
                
                // Remover transição após animação
                setTimeout(() => {
                    this.style.transition = '';
                }, 300);
            }
        }
        
        // Mostrar mensagem informativa
        mostrarNotificacao('Para alterar o status, clique no card e altere pelo modal de detalhes', 'info');
    });
});

// Permitir visualmente o drag sobre as colunas, mas não fazer nada
document.querySelectorAll('.kanban-cards').forEach(column => {
    column.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        
        // Adicionar feedback visual de que está sobre a coluna
        this.style.backgroundColor = 'rgba(59, 130, 246, 0.1)';
        return false;
    });
    
    column.addEventListener('dragleave', function(e) {
        // Remover feedback visual
        this.style.backgroundColor = '';
    });
    
    column.addEventListener('drop', function(e) {
        e.preventDefault();
        
        // Remover feedback visual
        this.style.backgroundColor = '';
        
        // Não fazer nada - o dragend já vai fazer o card voltar
        // Mas garantir que o card não seja inserido aqui
        return false;
    });
    
    // Prevenir que o card seja inserido durante o drag
    column.addEventListener('dragenter', function(e) {
        e.preventDefault();
        return false;
    });
});

// Clique para abrir detalhes (mas não se foi um drag)
document.querySelectorAll('.kanban-card').forEach(card => {
    let wasDragging = false;
    
    card.addEventListener('dragstart', function() {
        wasDragging = true;
    });
    
    card.addEventListener('dragend', function() {
        // Resetar flag após um pequeno delay
        setTimeout(() => {
            wasDragging = false;
        }, 100);
    });
    
    card.addEventListener('click', function(e) {
        // Se acabou de arrastar, não abrir detalhes
        if (wasDragging) {
            return;
        }
        
        const solicitacaoId = this.getAttribute('data-solicitacao-id');
        if (solicitacaoId) {
            abrirDetalhes(parseInt(solicitacaoId));
        }
    });
});

function atualizarContadores() {
    document.querySelectorAll('.kanban-column').forEach(column => {
        const cardsContainer = column.querySelector('.kanban-cards');
        const contador = column.querySelector('.bg-gray-200');
        const numCards = cardsContainer.querySelectorAll('.kanban-card').length;
        contador.textContent = numCards;
    });
}

function mostrarNotificacao(mensagem, tipo = 'info') {
    const cor = tipo === 'success' ? 'green' : tipo === 'error' ? 'red' : 'blue';
    const notificacao = document.createElement('div');
    notificacao.className = `fixed top-4 right-4 bg-${cor}-50 border border-${cor}-200 text-${cor}-700 px-4 py-3 rounded-lg shadow-lg z-50 transition-all`;
    notificacao.innerHTML = `
        <div class="flex items-center">
            <i class="fas fa-${tipo === 'success' ? 'check-circle' : tipo === 'error' ? 'exclamation-circle' : 'info-circle'} mr-2"></i>
            <span>${mensagem}</span>
        </div>
    `;
    document.body.appendChild(notificacao);
    
    setTimeout(() => {
        notificacao.style.opacity = '0';
        setTimeout(() => notificacao.remove(), 300);
    }, 3000);
}

// ✅ Variáveis globais para rastrear mudanças não salvas
let hasUnsavedChanges = false;
let offcanvasSolicitacaoId = null;
let whatsappHistoricoGlobal = [];

// Helper functions JavaScript para processamento de horários
function getHorariosLocatarioJS(solicitacao) {
    let horarios = [];
    
    // Quando horarios_indisponiveis = 1, horários originais do locatário estão em datas_opcoes
    if (solicitacao.horarios_indisponiveis) {
        if (solicitacao.datas_opcoes) {
            try {
                horarios = typeof solicitacao.datas_opcoes === 'string' 
                    ? JSON.parse(solicitacao.datas_opcoes) 
                    : solicitacao.datas_opcoes;
                if (!Array.isArray(horarios)) {
                    horarios = [];
                }
            } catch (e) {
                horarios = [];
            }
        } else {
            // Fallback: se datas_opcoes não existir, tentar buscar de horarios_opcoes (caso antigo)
            if (solicitacao.horarios_opcoes) {
                try {
                    horarios = typeof solicitacao.horarios_opcoes === 'string' 
                        ? JSON.parse(solicitacao.horarios_opcoes) 
                        : solicitacao.horarios_opcoes;
                    if (!Array.isArray(horarios)) {
                        horarios = [];
                    }
                } catch (e) {
                    horarios = [];
                }
            }
        }
    } else {
        // Quando horarios_indisponiveis = 0, horários do locatário estão em horarios_opcoes
        if (solicitacao.horarios_opcoes) {
            try {
                horarios = typeof solicitacao.horarios_opcoes === 'string' 
                    ? JSON.parse(solicitacao.horarios_opcoes) 
                    : solicitacao.horarios_opcoes;
                if (!Array.isArray(horarios)) {
                    horarios = [];
                }
            } catch (e) {
                horarios = [];
            }
        }
    }
    
    return horarios;
}

function getHorariosPrestadorJS(solicitacao) {
    let horarios = [];
    
    // Horários do prestador/seguradora só existem quando horarios_indisponiveis = 1
    // Normalizar para boolean (pode vir como "1", "0", 1, 0, true, false do banco)
    const indisponiveis = solicitacao.horarios_indisponiveis == 1 || 
                          solicitacao.horarios_indisponiveis === true || 
                          solicitacao.horarios_indisponiveis === '1';
    
    if (indisponiveis && solicitacao.horarios_opcoes) {
        try {
            horarios = typeof solicitacao.horarios_opcoes === 'string' 
                ? JSON.parse(solicitacao.horarios_opcoes) 
                : solicitacao.horarios_opcoes;
            if (!Array.isArray(horarios)) {
                horarios = [];
            }
            console.log('🎯 [DEBUG] getHorariosPrestadorJS: Encontrados', horarios.length, 'horários da seguradora');
        } catch (e) {
            console.error('🎯 [DEBUG] getHorariosPrestadorJS: Erro ao parsear horarios_opcoes:', e);
            horarios = [];
        }
    }
    
    return horarios;
}

function normalizarHorarioJS(horario) {
    if (!horario) return null;
    
    const horarioStr = String(horario).trim();
    
    // Se já está no formato esperado "dd/mm/yyyy - HH:00-HH:00", retornar
    if (/^\d{2}\/\d{2}\/\d{4}\s*-\s*\d{2}:\d{2}-\d{2}:\d{2}$/.test(horarioStr)) {
        return horarioStr;
    }
    
    // Converter "às" para "-"
    let horarioNorm = horarioStr.replace(/\s+às\s+/g, '-');
    
    // Formato 1: "dd/mm/yyyy - HH:00-HH:00" ou "dd/mm/yyyy HH:00-HH:00"
    const match1 = horarioNorm.match(/(\d{2})\/(\d{2})\/(\d{4})[ -](\d{2}):(\d{2})/);
    if (match1) {
        try {
            const dt = new Date(`${match1[3]}-${match1[2]}-${match1[1]}T${match1[4]}:${match1[5]}:00`);
            if (!isNaN(dt.getTime())) {
                const hora = parseInt(match1[4]);
                const horaFim = Math.min(hora + 3, 23);
                return `${match1[1]}/${match1[2]}/${match1[3]} - ${String(hora).padStart(2, '0')}:00-${String(horaFim).padStart(2, '0')}:00`;
            }
        } catch (e) {
            // Continuar tentando outros formatos
        }
    }
    
    // Formato 2: ISO "2025-11-25 08:00:00" ou "2025-11-25T08:00:00"
    const match2 = horarioNorm.match(/(\d{4}-\d{2}-\d{2})[T ](\d{2}):(\d{2})/);
    if (match2) {
        try {
            const dt = new Date(match2[1] + 'T' + match2[2] + ':' + match2[3] + ':00');
            if (!isNaN(dt.getTime())) {
                const dia = String(dt.getDate()).padStart(2, '0');
                const mes = String(dt.getMonth() + 1).padStart(2, '0');
                const ano = dt.getFullYear();
                const hora = dt.getHours();
                const horaFim = Math.min(hora + 3, 23);
                return `${dia}/${mes}/${ano} - ${String(hora).padStart(2, '0')}:00-${String(horaFim).padStart(2, '0')}:00`;
            }
        } catch (e) {
            // Continuar tentando outros formatos
        }
    }
    
    // Formato 3: Timestamp ou string que Date aceita
    try {
        const dt = new Date(horarioStr);
        if (!isNaN(dt.getTime())) {
            const dia = String(dt.getDate()).padStart(2, '0');
            const mes = String(dt.getMonth() + 1).padStart(2, '0');
            const ano = dt.getFullYear();
            const hora = dt.getHours();
            const horaFim = Math.min(hora + 3, 23);
            return `${dia}/${mes}/${ano} - ${String(hora).padStart(2, '0')}:00-${String(horaFim).padStart(2, '0')}:00`;
        }
    } catch (e) {
        // Falhou
    }
    
    return null;
}

function compararHorariosJS(horario1, horario2) {
    const norm1 = normalizarHorarioJS(horario1);
    const norm2 = normalizarHorarioJS(horario2);
    
    if (!norm1 || !norm2) return false;
    
    // Comparação exata
    if (norm1 === norm2) return true;
    
    // Normalizar espaços
    const norm1Clean = norm1.replace(/\s+/g, ' ').trim();
    const norm2Clean = norm2.replace(/\s+/g, ' ').trim();
    
    if (norm1Clean === norm2Clean) return true;
    
    // Comparação por regex
    const regex = /(\d{2}\/\d{2}\/\d{4})\s*-\s*(\d{2}:\d{2})\s*(?:às|-)\s*(\d{2}:\d{2})/;
    const match1 = norm1Clean.match(regex);
    const match2 = norm2Clean.match(regex);
    
    if (match1 && match2) {
        return (match1[1] === match2[1] && match1[2] === match2[2] && match1[3] === match2[3]);
    }
    
    return false;
}

function isHorarioConfirmadoJS(horario, solicitacao) {
    const horarioNorm = normalizarHorarioJS(horario);
    if (!horarioNorm) return false;
    
    // 1. Verificar em confirmed_schedules
    if (solicitacao.confirmed_schedules) {
        const confirmed = Array.isArray(solicitacao.confirmed_schedules) 
            ? solicitacao.confirmed_schedules 
            : (typeof solicitacao.confirmed_schedules === 'string' 
                ? JSON.parse(solicitacao.confirmed_schedules) 
                : []);
        
        if (Array.isArray(confirmed)) {
            for (const schedule of confirmed) {
                if (schedule && schedule.raw) {
                    if (compararHorariosJS(schedule.raw, horarioNorm)) {
                        return true;
                    }
                }
            }
        }
    }
    
    // 2. Verificar em horario_confirmado_raw
    if (solicitacao.horario_confirmado_raw) {
        if (compararHorariosJS(solicitacao.horario_confirmado_raw, horarioNorm)) {
            return true;
        }
    }
    
    // 3. Verificar em data_agendamento + horario_agendamento
    if (solicitacao.data_agendamento && solicitacao.horario_agendamento) {
        try {
            const dataAg = new Date(solicitacao.data_agendamento);
            const horarioDate = new Date(horarioNorm);
            if (dataAg.toDateString() === horarioDate.toDateString()) {
                const horaAg = String(solicitacao.horario_agendamento).trim();
                const horaAtual = String(horarioDate.getHours()).padStart(2, '0');
                if (horaAg.includes(horaAtual)) {
                    return true;
                }
            }
        } catch (e) {
            // Ignorar erro
        }
    }
    
    return false;
}

function formatarHorarioParaExibicaoJS(horario) {
    return String(horario).replace(/(\d{2}:\d{2})-(\d{2}:\d{2})/g, '$1 às $2');
}

function getSubcategoriasJS(solicitacao) {
    console.log('🔍 [DEBUG] getSubcategoriasJS - Iniciando busca de subcategorias');
    console.log('📋 [DEBUG] Solicitação ID:', solicitacao.id);
    console.log('📋 [DEBUG] Subcategoria principal:', solicitacao.subcategoria_nome);
    
    const subcategorias = [];
    
    // Adicionar subcategoria principal
    if (solicitacao.subcategoria_nome) {
        subcategorias.push(solicitacao.subcategoria_nome);
        console.log('✅ [DEBUG] Adicionada subcategoria principal:', solicitacao.subcategoria_nome);
    }
    
    // Verificar observações para múltiplas subcategorias
    if (solicitacao.observacoes) {
        console.log('📝 [DEBUG] Observações encontradas (primeiros 500 chars):', solicitacao.observacoes.substring(0, 500));
        
        // Tentar extrair IDs das subcategorias do formato [SUBCATEGORIAS_IDS: [...]]
        const idMatch = solicitacao.observacoes.match(/\[SUBCATEGORIAS_IDS:\s*(\[[^\]]+\])\]/);
        if (idMatch) {
            console.log('🔢 [DEBUG] Encontrado padrão SUBCATEGORIAS_IDS:', idMatch[1]);
            try {
                const subcategoriasIds = JSON.parse(idMatch[1]);
                console.log('🔢 [DEBUG] IDs parseados:', subcategoriasIds);
                if (Array.isArray(subcategoriasIds) && subcategoriasIds.length > 0) {
                    console.log('⚠️ [DEBUG] Nota: No JS não temos acesso ao modelo PHP para buscar nomes pelos IDs');
                    console.log('⚠️ [DEBUG] Os IDs encontrados são:', subcategoriasIds);
                }
            } catch (e) {
                console.error('❌ [DEBUG] Erro ao parsear SUBCATEGORIAS_IDS:', e);
            }
        } else {
            console.log('❌ [DEBUG] Padrão SUBCATEGORIAS_IDS não encontrado');
        }
        
        // Formato "Serviços solicitados (X):"
        const match2 = solicitacao.observacoes.match(/Serviços solicitados \(\d+\):\s*\n((?:\d+\.\s*[^\n]+\n?)+)/);
        if (match2) {
            console.log('✅ [DEBUG] Encontrado padrão "Serviços solicitados":', match2[1]);
            const linhas = match2[1].split('\n');
            console.log('📄 [DEBUG] Linhas encontradas:', linhas.length);
            for (const linha of linhas) {
                const linhaMatch = linha.match(/^\d+\.\s*(.+)$/);
                if (linhaMatch) {
                    const nome = linhaMatch[1].trim();
                    if (nome && !subcategorias.includes(nome)) {
                        subcategorias.push(nome);
                        console.log('✅ [DEBUG] Adicionada subcategoria da lista:', nome);
                    } else if (subcategorias.includes(nome)) {
                        console.log('⚠️ [DEBUG] Subcategoria já existe (ignorada):', nome);
                    }
                } else {
                    console.log('⚠️ [DEBUG] Linha não corresponde ao padrão:', linha);
                }
            }
        } else {
            console.log('❌ [DEBUG] Padrão "Serviços solicitados" não encontrado');
        }
    } else {
        console.log('❌ [DEBUG] Nenhuma observação encontrada');
    }
    
    console.log('📊 [DEBUG] Subcategorias finais encontradas:', subcategorias);
    console.log('📊 [DEBUG] Total de subcategorias:', subcategorias.length);
    
    return subcategorias;
}

// Funções do Offcanvas (reutilizadas do Dashboard)
function abrirDetalhes(solicitacaoId) {
    const offcanvas = document.getElementById('detalhesOffcanvas');
    const panel = document.getElementById('offcanvasPanel');
    const loadingContent = document.getElementById('loadingContent');
    const detalhesContent = document.getElementById('detalhesContent');
    
    // Resetar flag de mudanças não salvas
    hasUnsavedChanges = false;
    offcanvasSolicitacaoId = solicitacaoId;
    // Definir chatSolicitacaoId para uso no chat
    chatSolicitacaoId = solicitacaoId;
    
    offcanvas.classList.remove('hidden');
    setTimeout(() => panel.classList.remove('translate-x-full'), 10);
    
    loadingContent.classList.remove('hidden');
    detalhesContent.classList.add('hidden');
    
    console.log('🚀 [DEBUG] abrirDetalhes - Buscando detalhes da solicitação ID:', solicitacaoId);
    fetch(`<?= url('admin/solicitacoes/') ?>${solicitacaoId}/api`)
        .then(response => response.json())
        .then(data => {
            console.log('📥 [DEBUG] abrirDetalhes - Resposta da API recebida:', {
                success: data.success,
                solicitacaoId: data.solicitacao?.id,
                subcategoria_nome: data.solicitacao?.subcategoria_nome,
                observacoes_length: data.solicitacao?.observacoes?.length || 0,
                observacoes_preview: data.solicitacao?.observacoes?.substring(0, 300) || 'N/A'
            });
            
            if (data.success) {
                // Armazenar histórico de WhatsApp globalmente
                whatsappHistoricoGlobal = data.solicitacao.whatsapp_historico || [];
                // Armazenar solicitação globalmente para uso em copiarInformacoes
                window.solicitacaoAtual = data.solicitacao;
                
                console.log('📋 [DEBUG] abrirDetalhes - Dados completos da solicitação:', {
                    id: data.solicitacao.id,
                    subcategoria_nome: data.solicitacao.subcategoria_nome,
                    observacoes: data.solicitacao.observacoes ? data.solicitacao.observacoes.substring(0, 500) : 'N/A',
                    tem_observacoes: !!data.solicitacao.observacoes
                });
                
                renderizarDetalhes(data.solicitacao);
                
                // Carregar contagem de histórico se tiver contrato
                if (data.solicitacao.numero_contrato) {
                    carregarContagemHistorico(data.solicitacao.id, data.solicitacao.numero_contrato, data.solicitacao.categoria_id);
                }
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
    
    panel.classList.add('translate-x-full');
    setTimeout(() => offcanvas.classList.add('hidden'), 300);
}

// ✅ Prevenir navegação se houver mudanças não salvas
window.addEventListener('beforeunload', (e) => {
    if (hasUnsavedChanges) {
        e.preventDefault();
        e.returnValue = 'Você tem alterações não salvas. Deseja realmente sair?';
        return e.returnValue;
    }
});

// Variável global com todos os status
const todosStatus = <?= json_encode($todosStatus ?? []) ?>;
const todasCondicoes = <?= json_encode($todasCondicoes ?? []) ?>;

function renderizarDetalhes(solicitacao) {
    const content = document.getElementById('detalhesContent');
    
    // Resetar flag de mudanças ao renderizar
    hasUnsavedChanges = false;
    
    // Usar helpers para buscar horários
    let horariosLocatario = getHorariosLocatarioJS(solicitacao);
    let horariosPrestador = getHorariosPrestadorJS(solicitacao);
    let horariosOpcoes = [];
    
    // Verificar se condição é "Data Aceita pelo Prestador" ou "Data Aceita pelo Locatário"
        const condicaoAtual = todasCondicoes.find(c => c.id === solicitacao.condicao_id);
    
    // ✅ PRIORIDADE 1: Se horário foi confirmado (pelo locatário ou admin), mostrar APENAS o horário confirmado
    if (solicitacao.horario_confirmado && solicitacao.horario_confirmado_raw) {
        horariosOpcoes = [solicitacao.horario_confirmado_raw];
        console.log('🎯 [DEBUG] Disponibilidade: Usando horário confirmado:', solicitacao.horario_confirmado_raw);
    }
    // ✅ PRIORIDADE 2: Se condição é "Data Aceita pelo Prestador" ou "Data Aceita pelo Locatário"
    else if (condicaoAtual && (condicaoAtual.nome === 'Data Aceita pelo Prestador' || condicaoAtual.nome === 'Data Aceita pelo Locatário')) {
            // Quando prestador ou locatário aceitou uma data, mostrar apenas essa data de confirmed_schedules
            if (solicitacao.confirmed_schedules) {
            const confirmed = Array.isArray(solicitacao.confirmed_schedules) 
                ? solicitacao.confirmed_schedules 
                : (typeof solicitacao.confirmed_schedules === 'string' 
                    ? JSON.parse(solicitacao.confirmed_schedules) 
                    : []);
                    if (Array.isArray(confirmed) && confirmed.length > 0) {
                        const source = condicaoAtual.nome === 'Data Aceita pelo Prestador' ? 'prestador' : 'tenant';
                        const horarioAceito = confirmed.find(s => s && s.source === source && s.raw);
                        if (horarioAceito && horarioAceito.raw) {
                            horariosOpcoes = [horarioAceito.raw];
                        } else {
                            const ultimoConfirmado = confirmed[confirmed.length - 1];
                            if (ultimoConfirmado && ultimoConfirmado.raw) {
                                horariosOpcoes = [ultimoConfirmado.raw];
                            }
                    }
                }
            } else if (solicitacao.horario_confirmado_raw) {
                horariosOpcoes = [solicitacao.horario_confirmado_raw];
            }
        console.log('🎯 [DEBUG] Disponibilidade: Condição aceita, usando confirmed_schedules:', horariosOpcoes);
    }
    // ✅ PRIORIDADE 3: Se horários indisponíveis = 1, mostrar APENAS os horários da SEGURADORA
    // Normalizar para boolean (pode vir como "1", "0", 1, 0, true, false do banco)
    else if (solicitacao.horarios_indisponiveis == 1 || solicitacao.horarios_indisponiveis === true || solicitacao.horarios_indisponiveis === '1') {
        // Quando VGT marcou que horários do locatário não estão disponíveis,
        // mostrar APENAS os horários da seguradora (que estão em horarios_opcoes)
        horariosOpcoes = horariosPrestador;
        console.log('🎯 [DEBUG] Disponibilidade: horarios_indisponiveis=1, mostrando APENAS horários da seguradora:', horariosOpcoes);
        console.log('🎯 [DEBUG] Valor de horarios_indisponiveis:', solicitacao.horarios_indisponiveis, 'tipo:', typeof solicitacao.horarios_indisponiveis);
    }
    // ✅ PRIORIDADE 4: Caso padrão - mostrar horários do locatário
    else {
        horariosOpcoes = horariosLocatario;
        console.log('🎯 [DEBUG] Disponibilidade: Mostrando horários do locatário:', horariosOpcoes);
    }
    
    // Se ainda não houver horários, tentar fallbacks
    if (horariosOpcoes.length === 0) {
        if (horariosPrestador.length > 0) {
            horariosOpcoes = horariosPrestador;
        } else if (horariosLocatario.length > 0) {
                horariosOpcoes = horariosLocatario;
            }
        }
    
    // Remover duplicatas
    horariosOpcoes = [...new Set(horariosOpcoes)];
    
    // ✅ Criar variável helper para verificar horarios_indisponiveis (normalizado)
    const isHorariosIndisponiveis = solicitacao.horarios_indisponiveis == 1 || 
                                    solicitacao.horarios_indisponiveis === true || 
                                    solicitacao.horarios_indisponiveis === '1';
    
    // Buscar subcategorias usando helper
    console.log('🎯 [DEBUG] renderizarDetalhes - Buscando subcategorias para solicitação ID:', solicitacao.id);
    const subcategorias = getSubcategoriasJS(solicitacao);
    const temMultiplasSubcategorias = subcategorias.length > 1;
    console.log('🎯 [DEBUG] renderizarDetalhes - Resultado:', {
        subcategorias: subcategorias,
        total: subcategorias.length,
        temMultiplas: temMultiplasSubcategorias
    });
    
    const escapeHtml = (texto = '') => String(texto)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');

    const formatarObservacao = (texto) => escapeHtml(texto || '').replace(/\n/g, '<br>');

    // Função auxiliar para processar local_manutencao e tipo_imovel com fallback
    const processarDadosServico = (solicitacao) => {
        // PRIORIDADE 1: Buscar diretamente do campo do banco
        let localManutencao = (solicitacao.local_manutencao || '').trim();
        let finalidade = (solicitacao.finalidade_locacao || '').trim();
        let tipoImovel = (solicitacao.tipo_imovel || '').trim();
        
        // Limpar local da manutenção - remover "Validação:" se existir
        if (localManutencao) {
            localManutencao = localManutencao.replace(/^Validação:\s*/i, '');
            localManutencao = localManutencao.trim();
        }
        
        // FALLBACK: Se os campos diretos estiverem vazios (apenas para dados antigos), tentar extrair de descricao_card e observacoes
        // NOTA: Para solicitações novas, o local_manutencao deve vir direto do campo do banco
        if (!localManutencao || !tipoImovel) {
            // Tentar de descricao_card primeiro
            const descricaoCard = solicitacao.descricao_card || '';
            if (descricaoCard) {
                const linhas = descricaoCard.split('\n');
                for (const linha of linhas) {
                    const linhaTrim = linha.trim();
                    if (!linhaTrim) continue;
                    
                    // Ignorar linhas que começam com "Validação:"
                    if (/^Validação:/i.test(linhaTrim)) continue;
                    
                    // Buscar Finalidade
                    if (/Finalidade:/i.test(linhaTrim) && !finalidade) {
                        finalidade = linhaTrim.replace(/Finalidade:\s*/i, '').trim();
                        continue;
                    }
                    
                    // Buscar Tipo
                    if ((/Tipo:/i.test(linhaTrim) || /Tipo do Im[óo]vel:/i.test(linhaTrim)) && !tipoImovel) {
                        tipoImovel = linhaTrim.replace(/Tipo\s*(do\s*Im[óo]vel)?:\s*/i, '').trim();
                        // Limpar valores comuns que podem estar no início
                        tipoImovel = tipoImovel.replace(/^(RESIDENCIAL|COMERCIAL|CASA|APARTAMENTO)\s*/i, '').trim();
                        // Se ainda estiver vazio, tentar pegar o valor após "Tipo:"
                        if (!tipoImovel) {
                            const match = linhaTrim.match(/Tipo[^:]*:\s*(.+)/i);
                            if (match && match[1]) {
                                tipoImovel = match[1].trim();
                            }
                        }
                        continue;
                    }
                    
                    // Se for a primeira linha útil e não for Finalidade ou Tipo, pode ser local_manutencao
                    if (!localManutencao && !/Finalidade:/i.test(linhaTrim) && !/Tipo:/i.test(linhaTrim)) {
                        // Não deve ser muito longa (provavelmente não é local)
                        if (linhaTrim.length < 100) {
                            localManutencao = linhaTrim;
                        }
                    }
                }
            }
            
            // Se ainda estiver vazio, tentar de observacoes
            if (!localManutencao || !tipoImovel) {
                const observacoes = solicitacao.observacoes || '';
                if (observacoes) {
                    const linhas = observacoes.split('\n');
                    for (const linha of linhas) {
                        const linhaTrim = linha.trim();
                        if (!linhaTrim) continue;
                        
                        // Ignorar linhas que começam com "Validação:"
                        if (/^Validação:/i.test(linhaTrim)) continue;
                        
                        // Buscar Tipo
                        if ((/Tipo:/i.test(linhaTrim) || /Tipo do Im[óo]vel:/i.test(linhaTrim)) && !tipoImovel) {
                            tipoImovel = linhaTrim.replace(/Tipo\s*(do\s*Im[óo]vel)?:\s*/i, '').trim();
                            tipoImovel = tipoImovel.replace(/^(RESIDENCIAL|COMERCIAL|CASA|APARTAMENTO)\s*/i, '').trim();
                            if (!tipoImovel) {
                                const match = linhaTrim.match(/Tipo[^:]*:\s*(.+)/i);
                                if (match && match[1]) {
                                    tipoImovel = match[1].trim();
                                }
                            }
                            continue;
                        }
                        
                        // Buscar local (primeira linha útil que não seja Tipo ou Finalidade)
                        if (!localManutencao && !/Finalidade:/i.test(linhaTrim) && !/Tipo:/i.test(linhaTrim)) {
                            // Não deve ser muito longa ou parecer uma observação real
                            if (linhaTrim.length < 100 && !/[.!?]{2,}/.test(linhaTrim)) {
                                localManutencao = linhaTrim;
                            }
                        }
                    }
                }
            }
        }
        
        // Limpar valores finais
        localManutencao = localManutencao.trim();
        tipoImovel = tipoImovel.trim();
        finalidade = finalidade.trim();
        
        return {
            localManutencao,
            tipoImovel,
            finalidade
        };
    };
    
    // Processar dados do serviço
    const dadosServico = processarDadosServico(solicitacao);

    const historicoOrdenado = Array.isArray(solicitacao.historico_status)
        ? [...solicitacao.historico_status].sort((a, b) => {
            const dataA = new Date(a.created_at);
            const dataB = new Date(b.created_at);
            return dataA - dataB;
        })
        : [];

    const timelineEventos = [];
    if (solicitacao.created_at) {
        timelineEventos.push({
            titulo: 'Solicitação criada',
            usuario: null,
            observacoes: 'Registro inicial da solicitação.',
            data: solicitacao.created_at
        });
    }

    historicoOrdenado.forEach(evento => {
        // Determinar quem executou a ação
        let executadoPor = evento.executado_por || null;
        if (!executadoPor) {
            if (!evento.usuario_id && evento.observacoes && (evento.observacoes.toLowerCase().includes('locatário') || evento.observacoes.toLowerCase().includes('locatario'))) {
                executadoPor = 'Por Locatário';
            } else if (evento.usuario_nome) {
                executadoPor = 'Por ' + evento.usuario_nome;
            } else {
                executadoPor = 'Por Sistema';
            }
        }
        
        // Filtrar observações que já indicam quem fez a ação
        let observacoesLimpas = evento.observacoes;
        if (observacoesLimpas && (observacoesLimpas.includes('pelo Locatário') || observacoesLimpas.includes('- Por Locatário') || observacoesLimpas.includes('- Confirmado pelo Locatário'))) {
            observacoesLimpas = observacoesLimpas.replace(/\s*-\s*(Por|Confirmado pelo)\s+Locatário/gi, '').trim();
        }
        
        timelineEventos.push({
            titulo: evento.status_nome ? `Status: ${evento.status_nome}` : 'Atualização de status',
            usuario: executadoPor,
            observacoes: observacoesLimpas || null,
            data: evento.created_at
        });
    });

    const timelineHtml = timelineEventos.length
        ? timelineEventos.map((evento, index) => {
            const corPonto = index === timelineEventos.length - 1 ? 'bg-blue-500' : 'bg-gray-300';
            const linhaHtml = index !== timelineEventos.length - 1
                ? '<div class="w-0.5 flex-1 bg-gray-200 mt-1"></div>'
                : '';
            const usuarioLabel = evento.usuario || 'Por Sistema';
            const observacoesHtml = evento.observacoes
                ? `<p class="text-xs text-gray-500 mt-1">${formatarObservacao(evento.observacoes)}</p>`
                : '';

            return `
                <div class="flex gap-3 ${index !== timelineEventos.length - 1 ? 'pb-4' : ''}">
                    <div class="flex flex-col items-center">
                        <div class="w-3 h-3 rounded-full ${corPonto}"></div>
                        ${linhaHtml}
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-sm text-gray-900">${escapeHtml(evento.titulo)}</p>
                        <p class="text-xs text-gray-500 mt-1">${usuarioLabel}</p>
                        ${observacoesHtml}
                        <p class="text-xs text-gray-400 mt-2">${formatarDataHora(evento.data)}</p>
                    </div>
                </div>
            `;
        }).join('')
        : '<p class="text-sm text-gray-500">Nenhum evento de histórico registrado ainda.</p>';

    content.innerHTML = `
        <!-- Cabeçalho com ID e Data -->
        <div class="bg-white rounded-lg p-5 mb-4">
            <div class="flex items-start justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <div class="text-3xl font-bold text-gray-900">${solicitacao.numero_solicitacao || 'KSS' + solicitacao.id}</div>
                        ${solicitacao.numero_contrato ? `
                            <span class="text-sm text-gray-500 mt-2">
                                Contrato: ${solicitacao.numero_contrato}
                            </span>
                        ` : ''}
                    </div>
                    <div class="text-lg font-semibold text-gray-800">${solicitacao.categoria_nome}</div>
                    ${solicitacao.subcategoria_nome ? `<div class="text-sm text-gray-600 mt-1">${solicitacao.subcategoria_nome}</div>` : ''}
                </div>
                <div class="text-right text-sm text-gray-500">
                    <div>${formatarData(solicitacao.created_at)}</div>
                    ${solicitacao.numero_contrato ? `
                        <button onclick="abrirHistoricoUtilizacao(${solicitacao.id}, '${solicitacao.numero_contrato}', ${solicitacao.categoria_id || 'null'})" 
                                class="mt-2 px-3 py-1.5 bg-gray-600 text-white text-xs font-medium rounded-lg hover:bg-gray-700 transition-colors flex items-center gap-2"
                                id="btnHistorico-${solicitacao.id}">
                            <i class="fas fa-history"></i>
                            Histórico de Utilização
                            <span id="badgeHistorico-${solicitacao.id}" class="bg-white text-gray-600 px-1.5 py-0.5 rounded-full text-xs font-bold" title="Carregando...">
                                ...
                            </span>
                        </button>
                    ` : ''}
                </div>
            </div>
        </div>
        
        <!-- Histórico de Validação -->
        <div class="bg-white rounded-lg p-5 mb-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-check-circle mr-2 text-green-600"></i>
                Histórico de Validação
            </h3>
            <div class="space-y-2 text-sm">
                ${(() => {
                    // Lógica baseada no tipo_qualificacao para determinar aprovações
                    const tipoQualif = solicitacao.tipo_qualificacao;
                    const bolsaoAprovado = tipoQualif === 'BOLSAO' || solicitacao.validacao_bolsao == 1 || solicitacao.validacao_bolsao === true;
                    const dtAssinaturaAprovado = tipoQualif === 'REGRA_2';
                    // Limite Utilização: Verificar tanto validacao_utilizacao quanto limite de categoria por contrato
                    // 1. Verificar se validacao_utilizacao está marcado como 0 (limite de CPF excedido)
                    const limiteCpfRecusado = solicitacao.validacao_utilizacao === 0 || solicitacao.validacao_utilizacao === '0' || solicitacao.validacao_utilizacao === false;
                    
                    // 2. Verificar se há mensagem de limite de categoria excedido nas observações
                    const observacoes = solicitacao.observacoes || '';
                    const limiteCategoriaExcedido = observacoes.includes('EXCEDEU_LIMITE_QUANTIDADE') || observacoes.includes('Limite de solicitações da categoria excedido');
                    
                    // Limite está recusado se qualquer uma das condições for verdadeira
                    const limiteRecusado = limiteCpfRecusado || limiteCategoriaExcedido;
                    const limiteAprovado = !limiteRecusado;
                    const temApi = solicitacao.imobiliaria_api_id || solicitacao.imobiliaria_integracao_ativa == 1;
                    
                    let html = '';
                    
                    // Bolsão
                    html += `<div class="flex items-center justify-between py-2 border-b border-gray-200">
                        <span class="font-medium text-gray-700">BOLSÃO:</span>
                        <span class="px-3 py-1 rounded-full text-xs font-medium ${bolsaoAprovado ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                            ${bolsaoAprovado ? 'APROVADO' : 'RECUSADO'}
                        </span>
                    </div>`;
                    
                    // Data Assinatura - Só mostrar se bolsão NÃO estiver aprovado
                    if (!bolsaoAprovado) {
                        html += `<div class="flex items-center justify-between py-2 border-b border-gray-200">
                            <span class="font-medium text-gray-700">DT ASSINATURA:</span>
                            <span class="px-3 py-1 rounded-full text-xs font-medium ${dtAssinaturaAprovado ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                ${dtAssinaturaAprovado ? 'APROVADO' : 'RECUSADO ' + (temApi ? 'COM API' : 'SEM API')}
                            </span>
                        </div>`;
                    }
                    
                    // Limite Utilização
                    html += `<div class="flex items-center justify-between py-2">
                        <span class="font-medium text-gray-700">LIMITE UTILIZAÇÃO:</span>
                        <span class="px-3 py-1 rounded-full text-xs font-medium ${limiteAprovado ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                            ${limiteAprovado ? 'APROVADO' : 'RECUSADO'}
                        </span>
                    </div>`;
                    
                    // Observação da Qualificação (se não qualificada)
                    if (tipoQualif === 'NAO_QUALIFICADA' && solicitacao.observacao_qualificacao) {
                        html += `<div class="mt-3 pt-3 border-t border-gray-200">
                            <div class="flex items-start gap-2 text-sm text-red-800 bg-red-50 border-l-4 border-red-400 p-3 rounded-r">
                                <i class="fas fa-exclamation-triangle text-red-600 mt-0.5"></i>
                                <div>
                                    <strong class="text-red-900">Observação da Validação:</strong>
                                    <p class="mt-1 text-red-700">${solicitacao.observacao_qualificacao}</p>
                                </div>
                            </div>
                        </div>`;
                    }
                    
                    return html;
                })()}
            </div>
        </div>
        
        <!-- Bloco 1: Informações do Cliente e Endereço -->
        <div class="bg-white rounded-lg p-5 mb-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-user mr-2 text-blue-600"></i>
                Informações do Cliente e Endereço
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Informações do Cliente -->
                <div>
                    <h4 class="text-sm font-medium text-gray-700 mb-3">Informações do Cliente</h4>
                    <div class="space-y-3 text-sm">
                        <div>
                            <p class="text-gray-500 mb-1">Nome:</p>
                            <p class="font-medium text-gray-900">${solicitacao.locatario_nome}</p>
                        </div>
                        ${solicitacao.locatario_cpf ? `
                        <div>
                            <p class="text-gray-500 mb-1">CPF/CNPJ:</p>
                            <p class="font-medium text-gray-900">${formatarCpfCnpj(solicitacao.locatario_cpf)}</p>
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
                <!-- Endereço -->
                ${solicitacao.imovel_endereco ? `
                <div>
                    <h4 class="text-sm font-medium text-gray-700 mb-3">
                        <i class="fas fa-map-marker-alt mr-2 text-gray-400"></i>
                        Endereço
                    </h4>
                    <div class="text-sm text-gray-900">
                        <p class="font-medium">${solicitacao.imovel_endereco}${solicitacao.imovel_numero ? ', ' + solicitacao.imovel_numero : ''}${solicitacao.imovel_complemento ? ' - ' + solicitacao.imovel_complemento : ''}</p>
                        ${solicitacao.imovel_bairro || solicitacao.imovel_cidade ? `
                            <p class="text-gray-600 mt-1">${solicitacao.imovel_bairro || ''}${solicitacao.imovel_bairro && solicitacao.imovel_cidade ? ' - ' : ''}${solicitacao.imovel_cidade || ''}${solicitacao.imovel_estado ? '/' + solicitacao.imovel_estado : ''}</p>
                        ` : ''}
                        ${solicitacao.imovel_cep ? `
                            <p class="text-gray-600">CEP: ${solicitacao.imovel_cep}</p>
                        ` : ''}
                    </div>
                </div>
                ` : ''}
            </div>
        </div>
        
        <!-- Bloco 2: Descrição do Problema, Informação do Serviço, Obs do Segurado e Fotos -->
        <div class="bg-white rounded-lg p-5 mb-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-info-circle mr-2 text-blue-600"></i>
                Informações do Serviço
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Lado Esquerdo: Local, Tipo Imóvel, Descrição -->
                <div>
                    <div class="space-y-3">
                        <!-- 1. Local da Manutenção (PRIMEIRO) -->
                        <div>
                            <span class="text-sm font-semibold text-gray-900">Local da Manutenção:</span>
                            <p class="text-sm text-gray-700 mt-1">${dadosServico.localManutencao ? escapeHtml(dadosServico.localManutencao) : '-'}</p>
                        </div>
                        
                        <!-- 2. Tipo do Imóvel (SEGUNDO) -->
                        <div>
                            <span class="text-sm font-semibold text-gray-900">Tipo do Imóvel:</span>
                            <p class="text-sm text-gray-700 mt-1">${dadosServico.tipoImovel ? escapeHtml(dadosServico.tipoImovel) : '-'}</p>
                        </div>
                        
                        <!-- 3. Descrição do Problema -->
                        <div>
                            <span class="text-sm font-semibold text-gray-900">Descrição do Problema:</span>
                            <div class="bg-gray-50 rounded-lg p-3 mt-1">
                                <p class="text-sm text-gray-700 text-left whitespace-pre-wrap">${solicitacao.descricao_problema ? escapeHtml(solicitacao.descricao_problema).replace(/\n/g, '<br>') : 'Nenhuma descrição fornecida.'}</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Lado Direito: Categoria, Serviços, Finalidade, Prioridade, Data, Validação -->
                <div>
                    <div class="space-y-3 text-sm">
                        <div>
                            <span class="text-sm font-semibold text-gray-900">Categoria:</span>
                            <p class="text-sm text-gray-700">${escapeHtml(solicitacao.categoria_nome)}</p>
                        </div>
                        ${temMultiplasSubcategorias ? `
                        <div class="mt-2">
                            <span class="text-sm font-semibold text-gray-900">Serviços:</span>
                            <ul class="list-disc list-inside space-y-0.5 text-sm text-gray-700 mt-1">
                                ${subcategorias.map(sub => `<li>${escapeHtml(sub)}</li>`).join('')}
                            </ul>
                        </div>
                        ` : (subcategorias.length > 0 ? `
                        <div class="mt-2">
                            <span class="text-sm font-semibold text-gray-900">Tipo de Serviço:</span>
                            <p class="text-sm text-gray-700">${escapeHtml(subcategorias[0])}</p>
                        </div>
                        ` : (solicitacao.subcategoria_nome ? `
                        <div class="mt-2">
                            <span class="text-sm font-semibold text-gray-900">Tipo de Serviço:</span>
                            <p class="text-sm text-gray-700">${escapeHtml(solicitacao.subcategoria_nome)}</p>
                        </div>
                        ` : ''))}
                        ${dadosServico.finalidade ? `
                        <div class="mt-2">
                            <span class="text-sm font-semibold text-gray-900">Finalidade:</span>
                            <p class="text-sm text-gray-700">${escapeHtml(dadosServico.finalidade)}</p>
                        </div>
                        ` : ''}
                        <div class="mt-2">
                            <span class="text-sm font-semibold text-gray-900">Prioridade:</span>
                            ${(() => {
                                const isEmergencial = solicitacao.subcategoria_is_emergencial || solicitacao.is_emergencial || false;
                                const prioridade = solicitacao.prioridade || 'NORMAL';
                                let texto = '';
                                let cor = '';
                                
                                if (isEmergencial) {
                                    texto = 'Emergencial';
                                    cor = 'bg-red-100 text-red-800';
                                } else if (prioridade === 'ALTA') {
                                    texto = 'ALTA';
                                    cor = 'bg-red-100 text-red-800';
                                } else if (prioridade === 'MEDIA') {
                                    texto = 'MEDIA';
                                    cor = 'bg-yellow-100 text-yellow-800';
                                } else {
                                    texto = 'NORMAL';
                                    cor = 'bg-green-100 text-green-800';
                                }
                                
                                return `<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${cor} mt-1">
                                    ${texto}
                                </span>`;
                            })()}
                        </div>
                        <div class="mt-2">
                            <span class="text-sm font-semibold text-gray-900">Data de Criação:</span>
                            <p class="text-sm text-gray-700">
                                ${solicitacao.created_at ? formatarDataHora(solicitacao.created_at) : '-'}
                            </p>
                        </div>
                        ${(() => {
                            // Converter tipo_qualificacao para texto legível
                            const tipoQualificacao = solicitacao.tipo_qualificacao;
                            if (!tipoQualificacao) return '';
                            
                            let texto = '';
                            let cor = '';
                            let icone = '';
                            let titulo = '';
                            
                            switch(tipoQualificacao) {
                                case 'BOLSAO':
                                    texto = 'Bolsão';
                                    cor = 'bg-blue-100 text-blue-800 border-blue-200';
                                    icone = 'fa-check-circle';
                                    titulo = 'CPF validado na listagem da imobiliária';
                                    break;
                                case 'CORTESIA':
                                    texto = 'Cortesia';
                                    cor = 'bg-green-100 text-green-800 border-green-200';
                                    icone = 'fa-gift';
                                    titulo = 'Solicitação cortesia';
                                    break;
                                case 'NAO_QUALIFICADA':
                                    texto = 'Não Qualificada';
                                    cor = 'bg-red-100 text-red-800 border-red-200';
                                    icone = 'fa-times-circle';
                                    titulo = 'Solicitação não qualificada';
                                    break;
                                case 'REGRA_2':
                                    texto = 'Dt assinatura';
                                    cor = 'bg-purple-100 text-purple-800 border-purple-200';
                                    icone = 'fa-calendar-check';
                                    titulo = 'Validação por data de assinatura do contrato';
                                    break;
                                default:
                                    return '';
                            }
                            
                            // Se for CORTESIA e tiver observação, mostrar observação ao lado
                            if (tipoQualificacao === 'CORTESIA' && solicitacao.observacao_qualificacao) {
                                return `<div class="mt-2">
                            <p class="text-gray-500 mb-1">Tipo validação:</p>
                                <div class="flex items-start gap-2 flex-wrap">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium ${cor} border" title="${titulo}">
                                        <i class="fas ${icone} mr-1"></i>
                                        ${texto}
                            </span>
                                    <div class="flex-1 min-w-[200px] text-xs text-gray-700 bg-green-50 border-l-3 border-green-400 p-2 rounded-r">
                                        <strong class="text-green-900">OBS:</strong> ${escapeHtml(solicitacao.observacao_qualificacao)}
                        </div>
                                </div>
                                </div>`;
                            }
                            
                            return `<div class="mt-2">
                            <p class="text-gray-500 mb-1">Tipo validação:</p>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium ${cor} border" title="${titulo}">
                                    <i class="fas ${icone} mr-1"></i>
                                    ${texto}
                            </span>
                            </div>`;
                        })()}
                    </div>
                </div>
            </div>
            
            <!-- Motivo do Cancelamento (se cancelado) -->
            ${solicitacao.status_nome && solicitacao.status_nome.includes('Cancelado') && solicitacao.motivo_cancelamento ? `
            <div class="mt-6 bg-red-50 border border-red-200 rounded-lg p-4">
                <h4 class="text-sm font-medium text-red-700 mb-2">
                    <i class="fas fa-times-circle mr-2"></i>
                    Motivo do Cancelamento
                </h4>
                <p class="text-sm text-red-900">${solicitacao.motivo_cancelamento}</p>
            </div>
            ` : ''}
            
            <!-- Observações do Segurado -->
            <div class="mt-6">
                <h4 class="text-sm font-medium text-gray-700 mb-3">
                    <i class="fas fa-comment-dots mr-2 text-gray-400"></i>
                    Observações do Segurado
                </h4>
                <textarea class="w-full bg-gray-50 border border-gray-200 rounded p-3 text-sm text-gray-700 min-h-[120px] resize-none" 
                          placeholder="Descreva qualquer situação adicional (ex: prestador não compareceu, precisa comprar peças, etc.)">${(() => {
                    // Limpar observações - remover informações de tipo, finalidade, validação e local da manutenção
                    let obs = solicitacao.observacoes || '';
                    if (obs) {
                        // Remover linhas que contêm "Tipo do Imóvel:" ou "Tipo:" seguido de valor
                        obs = obs.replace(/Tipo\s+do\s+Im[óo]vel\s*:\s*[^\n]*\n?/gi, '');
                        obs = obs.replace(/^Tipo\s*:\s*[^\n]*\n?/gim, '');
                        // Remover linhas que contêm "Finalidade:"
                        obs = obs.replace(/Finalidade\s*:\s*[^\n]*\n?/gi, '');
                        // Remover linhas que começam com "Validação:"
                        obs = obs.replace(/^Validação\s*:\s*[^\n]*\n?/gim, '');
                        
                        // Remover local_manutencao caso esteja no início (para dados antigos)
                        // Se a primeira linha for curta e não parecer uma observação real, remover
                        const linhas = obs.split('\n');
                        if (linhas.length > 0) {
                            const primeiraLinha = linhas[0].trim();
                            // Verificar se parece ser um local da manutenção (curto, sem pontuação, sem palavras de observação)
                            if (primeiraLinha.length < 100 && !/[.!?]{2,}/.test(primeiraLinha) && !/Local/i.test(primeiraLinha)) {
                                const palavrasObservacao = ['prestador', 'compareceu', 'peças', 'material', 'situação', 'adicional'];
                                const ehObservacao = palavrasObservacao.some(palavra => primeiraLinha.toLowerCase().includes(palavra));
                                if (!ehObservacao) {
                                    linhas.shift(); // Remover primeira linha
                                }
                            }
                            obs = linhas.join('\n');
                        }
                        
                        obs = obs.trim();
                    }
                    return escapeHtml(obs);
                })()}</textarea>
            </div>
            <!-- Fotos Enviadas -->
            <div class="mt-6">
                <h4 class="text-sm font-medium text-gray-700 mb-3">
                    <i class="fas fa-camera mr-2 text-gray-400"></i>
                    Fotos Enviadas
                    <span class="text-xs text-gray-500" id="fotos-count">(${solicitacao.fotos && Array.isArray(solicitacao.fotos) ? solicitacao.fotos.length : 0})</span>
                </h4>
                ${solicitacao.fotos && Array.isArray(solicitacao.fotos) && solicitacao.fotos.length > 0 ? `
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    ${solicitacao.fotos.map((foto, index) => {
                        let urlFoto = '';
                        const nomeArquivo = foto.nome_arquivo || (foto.url_arquivo ? foto.url_arquivo.split('/').pop() : '');
                        if (nomeArquivo) {
                            urlFoto = '<?= url("Public/uploads/solicitacoes/") ?>' + nomeArquivo;
                        } else {
                            return '';
                        }
                        // Usar data attributes e event delegation ao invés de onclick inline
                        const fotoId = 'foto-' + solicitacao.id + '-' + index;
                        const urlFotoEscapada = urlFoto.replace(/'/g, "\\'").replace(/"/g, '&quot;');
                        const nomeArquivoEscapado = nomeArquivo.replace(/'/g, "\\'").replace(/"/g, '&quot;');
                        return `
                            <div class="relative group foto-container" 
                                 data-foto-url="${urlFotoEscapada}"
                                 data-foto-nome="${nomeArquivoEscapado}">
                                <img src="${urlFotoEscapada}" 
                                     alt="Foto ${index + 1}" 
                                     id="${fotoId}"
                                     class="w-full h-32 object-cover rounded-lg border border-gray-200 cursor-pointer hover:opacity-80 transition-opacity shadow-sm"
                                     onerror="this.parentElement.innerHTML='<div class=\\'w-full h-32 bg-gray-100 rounded-lg flex items-center justify-center text-gray-400 text-xs\\'><i class=\\'fas fa-image mr-2\\'></i>Erro</div>';">
                                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 rounded-lg transition-all flex items-center justify-center pointer-events-none">
                                    <i class="fas fa-search-plus text-white opacity-0 group-hover:opacity-100 transition-opacity text-2xl"></i>
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
                ` : `
                <div class="text-center py-8 text-gray-400">
                    <i class="fas fa-camera text-3xl mb-2 block"></i>
                    <p class="text-sm">Nenhuma foto enviada</p>
                </div>
                `}
            </div>
        </div>
        
        <!-- Bloco 3: Disponibilidade de Data, Status da Solicitação, Condições, Protocolo da Seguradora -->
        <div class="bg-white rounded-lg p-5 mb-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-calendar-alt mr-2 text-blue-600"></i>
                Status e Agendamento
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Status da Solicitação -->
                <div>
                    <h4 class="text-sm font-medium text-gray-700 mb-3">Status da Solicitação</h4>
                    <select id="statusSelectKanban" 
                            onchange="marcarMudancaStatus()"
                            class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        ${todosStatus.map(status => `
                            <option value="${status.id}" ${status.id == solicitacao.status_id ? 'selected' : ''}>
                                ${status.nome}
                            </option>
                        `).join('')}
                    </select>
                </div>
                <!-- Condições/Pendências -->
                <div>
                    <h4 class="text-sm font-medium text-gray-700 mb-3">Condições/Pendências</h4>
                    <select id="condicaoSelectKanban" 
                            onchange="marcarMudancaCondicao()"
                            class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <option value="">Selecione uma condição</option>
                        ${todasCondicoes.map(condicao => `
                            <option value="${condicao.id}" ${condicao.id == solicitacao.condicao_id ? 'selected' : ''}>
                                ${condicao.nome}
                            </option>
                        `).join('')}
                    </select>
                </div>
            </div>
            <!-- Protocolo da Seguradora -->
            <div class="mt-6">
                <h4 class="text-sm font-medium text-gray-700 mb-3">Protocolo da Seguradora</h4>
                <input type="text" 
                       id="protocoloSeguradora"
                       placeholder="Ex.: 123456/2025" 
                       value="${solicitacao.protocolo_seguradora || ''}"
                       class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-sm text-gray-900">
            </div>
            <!-- Disponibilidade de Data -->
            <div class="mt-6">
                <h4 class="text-sm font-medium text-gray-700 mb-3">
                    <i class="fas fa-clock mr-2 ${solicitacao.horario_confirmado ? 'text-green-600' : (isHorariosIndisponiveis ? 'text-blue-600' : 'text-gray-400')}"></i>
                    ${solicitacao.horario_confirmado ? 
                        '<span class="text-green-700">Horário Confirmado</span>' : 
                        (isHorariosIndisponiveis ? 
                            '<span class="text-blue-700">Disponibilidade Informada pela Seguradora</span>' : 
                            'Disponibilidade Informada pelo Locatário')}
                </h4>
                ${horariosOpcoes.length > 0 ? `
                <div class="space-y-2">
                    ${horariosOpcoes.filter(h => h && String(h).trim()).map((horario, index) => {
                        try {
                            // Normalizar horário usando helper
                            const horarioNorm = normalizarHorarioJS(horario);
                            if (!horarioNorm) return '';
                            
                            // Formatar para exibição
                            const textoHorario = formatarHorarioParaExibicaoJS(horarioNorm);
                            
                            // Verificar se está confirmado usando helper
                            const isConfirmed = isHorarioConfirmadoJS(horarioNorm, solicitacao);
                            
                            return `
                            <div class="flex items-center gap-3 py-2 ${isConfirmed ? 'bg-green-50 rounded px-2' : ''}">
                                <input type="checkbox" 
                                       class="w-4 h-4 text-blue-600 rounded horario-offcanvas" 
                                       data-raw="${escapeHtml(horarioNorm)}" 
                                       id="horario-${index}"
                                       ${isConfirmed ? 'checked' : ''}>
                                <label for="horario-${index}" class="text-sm text-gray-700 flex items-center gap-2">
                                    ${isConfirmed ? '<i class="fas fa-check-circle text-green-600"></i>' : ''}
                                    <span>${escapeHtml(textoHorario)}</span>
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
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" 
                               id="horarios-indisponiveis-kanban" 
                               class="w-4 h-4 text-blue-600 rounded"
                               ${isHorariosIndisponiveis ? 'checked' : ''}
                               onchange="toggleAdicionarHorariosSeguradoraKanban(${solicitacao.id}, this.checked)">
                        <span class="text-sm text-gray-700">Nenhum horário está disponível</span>
                    </label>
                </div>
                <div id="secao-adicionar-horarios-seguradora-kanban" 
                     class="mt-4 ${isHorariosIndisponiveis ? '' : 'hidden'}"
                     style="${isHorariosIndisponiveis ? 'display: block;' : 'display: none;'}">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-blue-900 mb-2">
                            <i class="fas fa-clock mr-2"></i>Adicionar Horário da Seguradora
                        </h3>
                        <p class="text-xs text-blue-700 mb-4">
                            Adicione horários alternativos que a seguradora pode oferecer
                        </p>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Data</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-calendar-alt text-gray-400"></i>
                                    </div>
                                    <input type="date" 
                                           id="data-seguradora-kanban" 
                                           class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
                                           min="${new Date(Date.now() + 86400000).toISOString().split('T')[0]}"
                                           max="${new Date(Date.now() + 30 * 86400000).toISOString().split('T')[0]}">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-2">Horário</label>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                    <label class="relative cursor-pointer">
                                        <input type="radio" name="horario-seguradora-kanban" value="08:00-11:00" class="sr-only">
                                        <div class="border-2 border-gray-200 rounded-lg p-2 text-center hover:border-blue-300 transition-colors horario-seguradora-card-kanban">
                                            <div class="text-xs font-medium text-gray-900">08h00 às 11h00</div>
                                        </div>
                                    </label>
                                    <label class="relative cursor-pointer">
                                        <input type="radio" name="horario-seguradora-kanban" value="11:00-14:00" class="sr-only">
                                        <div class="border-2 border-gray-200 rounded-lg p-2 text-center hover:border-blue-300 transition-colors horario-seguradora-card-kanban">
                                            <div class="text-xs font-medium text-gray-900">11h00 às 14h00</div>
                                        </div>
                                    </label>
                                    <label class="relative cursor-pointer">
                                        <input type="radio" name="horario-seguradora-kanban" value="14:00-17:00" class="sr-only">
                                        <div class="border-2 border-gray-200 rounded-lg p-2 text-center hover:border-blue-300 transition-colors horario-seguradora-card-kanban">
                                            <div class="text-xs font-medium text-gray-900">14h00 às 17h00</div>
                                        </div>
                                    </label>
                                    <label class="relative cursor-pointer">
                                        <input type="radio" name="horario-seguradora-kanban" value="17:00-20:00" class="sr-only">
                                        <div class="border-2 border-gray-200 rounded-lg p-2 text-center hover:border-blue-300 transition-colors horario-seguradora-card-kanban">
                                            <div class="text-xs font-medium text-gray-900">17h00 às 20h00</div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            <button type="button" 
                                    onclick="adicionarHorarioSeguradoraKanban(${solicitacao.id})" 
                                    class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium">
                                <i class="fas fa-plus mr-2"></i>Salvar Horário
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            ` : ''}
        </div>
        
        <!-- Bloco 4: Anexar Documentos com Campo de Obs -->
        <div class="bg-white rounded-lg p-5 mb-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-paperclip mr-2 text-blue-600"></i>
                Anexar Documentos
            </h3>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Observação</label>
                <textarea id="obsAnexoDocumento" rows="3" class="w-full bg-gray-50 border border-gray-200 rounded p-3 text-sm text-gray-700 resize-none" 
                          placeholder="Adicione uma observação sobre os documentos..."></textarea>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Anexos</label>
                <input type="file" id="anexoDocumento" multiple accept="image/*,.pdf,.doc,.docx" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <p class="text-xs text-gray-500 mt-1">Você pode selecionar múltiplos arquivos (imagens, PDF, Word) - máx 5MB cada</p>
            </div>
            <button onclick="anexarDocumento(${solicitacao.id})" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition-colors">
                <i class="fas fa-upload mr-2"></i>
                Enviar Documentos
            </button>
        </div>
        
        <!-- Bloco 5: Reembolso -->
        <div class="bg-white rounded-lg p-5 mb-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-money-bill-wave mr-2 text-blue-600"></i>
                Reembolso
            </h3>
            ${solicitacao.precisa_reembolso || solicitacao.valor_reembolso ? `
            <div class="bg-gray-50 rounded-lg p-4 mb-4">
                <div class="space-y-2">
                    ${solicitacao.valor_reembolso ? `
                    <div>
                        <span class="text-sm text-gray-500">Valor do Reembolso:</span>
                        <p class="text-sm font-medium text-gray-900">R$ ${formatarValorMoeda(solicitacao.valor_reembolso)}</p>
                    </div>
                    ` : ''}
                    ${solicitacao.observacoes ? `
                    <div>
                        <span class="text-sm text-gray-500">Observação:</span>
                        <p class="text-sm text-gray-900">${solicitacao.observacoes}</p>
                    </div>
                    ` : ''}
                </div>
            </div>
            ` : ''}
            <div class="mb-4">
                <label class="flex items-center gap-2 mb-3">
                    <input type="checkbox" 
                           id="checkboxReembolso" 
                           class="w-4 h-4 text-blue-600 rounded" 
                           onchange="toggleCampoReembolso()"
                           ${solicitacao.precisa_reembolso ? 'checked' : ''}>
                    <span class="text-sm font-medium text-gray-900">Precisa de Reembolso?</span>
                </label>
                <div id="campoValorReembolso" class="${solicitacao.precisa_reembolso ? '' : 'hidden'} mt-3">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Observação</label>
                    <textarea id="obsReembolso" rows="3" class="w-full bg-gray-50 border border-gray-200 rounded p-3 text-sm text-gray-700 resize-none" 
                              placeholder="Justifique o motivo do reembolso..."></textarea>
                    <label class="block text-sm font-medium text-gray-700 mb-2 mt-3">Valor do Reembolso (R$)</label>
                    <input type="text" 
                           id="valorReembolso"
                           placeholder="R$ 0,00" 
                           value="${solicitacao.valor_reembolso ? formatarValorMoeda(solicitacao.valor_reembolso) : ''}"
                           class="w-full bg-gray-50 border border-gray-200 rounded px-3 py-2 text-sm text-gray-900"
                           onkeyup="formatarMoeda(this)">
                    <label class="block text-sm font-medium text-gray-700 mb-2 mt-3">Anexos</label>
                    <input type="file" id="anexoReembolso" multiple accept="image/*,.pdf,.doc,.docx" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <p class="text-xs text-gray-500 mt-1">Você pode selecionar múltiplos arquivos (imagens, PDF, Word)</p>
                </div>
            </div>
        </div>
        
        <!-- Bloco 6: Linha do Tempo -->
        <div class="bg-white rounded-lg p-5 mb-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-history mr-2 text-blue-600"></i>
                Linha do Tempo
            </h3>
            <div class="space-y-4">
                ${timelineHtml}
            </div>
        </div>
        
        <!-- Bloco 7: Histórico do WhatsApp -->
        <div class="bg-white rounded-lg p-5 mb-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fab fa-whatsapp mr-2 text-green-600"></i>
                Histórico do WhatsApp
            </h3>
            <div class="space-y-2 max-h-64 overflow-y-auto">
                ${solicitacao.whatsapp_historico && solicitacao.whatsapp_historico.length > 0 ? 
                    solicitacao.whatsapp_historico.map((envio, index) => {
                        const statusIcon = envio.status === 'sucesso' ? 'fa-check-circle text-green-600' : 
                                          envio.status === 'erro' ? 'fa-times-circle text-red-600' : 
                                          'fa-clock text-yellow-600';
                        const statusText = envio.status === 'sucesso' ? 'Enviado' : 
                                          envio.status === 'erro' ? 'Erro' : 
                                          'Pendente';
                        return `
                            <div class="border border-gray-200 rounded-lg p-3 hover:bg-gray-50 transition-colors">
                                <div class="flex items-start justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <i class="fas ${statusIcon} text-sm"></i>
                                        <span class="text-xs font-medium text-gray-700">${envio.tipo}</span>
                                        <span class="text-xs px-2 py-0.5 rounded ${envio.status === 'sucesso' ? 'bg-green-100 text-green-700' : envio.status === 'erro' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700'}">${statusText}</span>
                                    </div>
                                    <span class="text-xs text-gray-500">${formatarDataHora(envio.timestamp)}</span>
                                </div>
                                ${envio.telefone ? `
                                    <div class="text-xs text-gray-600 mb-1">
                                        <i class="fas fa-phone mr-1"></i>
                                        ${envio.telefone}
                                    </div>
                                ` : ''}
                                ${envio.erro ? `
                                    <div class="text-xs text-red-600 mt-1">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>
                                        ${envio.erro}
                                    </div>
                                ` : ''}
                                <div class="flex items-center gap-3 mt-2">
                                    <button onclick="verMensagemWhatsApp(${index})" class="text-xs text-blue-600 hover:text-blue-800 flex items-center">
                                        <i class="fas fa-eye mr-1"></i>
                                        Ver mensagem
                                    </button>
                                    <button onclick="reenviarMensagemWhatsApp(${solicitacao.id}, ${index}, event)" class="text-xs text-green-600 hover:text-green-800 flex items-center">
                                        <i class="fas fa-redo mr-1"></i>
                                        Reenviar
                                    </button>
                                </div>
                            </div>
                        `;
                    }).join('') : 
                    `
                    <div class="text-center py-6 text-gray-400">
                        <i class="fab fa-whatsapp text-2xl mb-2 block"></i>
                        <p class="text-sm">Nenhum envio de WhatsApp registrado</p>
                    </div>
                    `
                }
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
    
    // ✅ Monitorar mudanças em todos os campos após renderizar
    setTimeout(() => {
        monitorarMudancas();
        
        // Verificar se a seção foi criada
        const secao = document.getElementById('secao-adicionar-horarios-seguradora-kanban');
        const checkbox = document.getElementById('horarios-indisponiveis-kanban');
        console.log('🔍 Após renderizar - Seção encontrada:', secao);
        console.log('🔍 Após renderizar - Checkbox encontrado:', checkbox);
        console.log('🔍 Após renderizar - Checkbox checked:', checkbox?.checked);
        console.log('🔍 Após renderizar - Seção display:', secao?.style.display);
        
        // Ajustar visibilidade inicial baseado no estado do checkbox
        if (checkbox && secao) {
            if (checkbox.checked) {
                secao.style.display = 'block';
                secao.classList.remove('hidden');
            } else {
                secao.style.display = 'none';
                secao.classList.add('hidden');
            }
        }
    }, 100);
}

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
    
    // Monitorar select de status
    const statusSelect = document.getElementById('statusSelectKanban');
    if (statusSelect) {
        statusSelect.addEventListener('change', () => {
            hasUnsavedChanges = true;
        });
    }
    
    // Monitorar select de condição
    const condicaoSelect = document.getElementById('condicaoSelectKanban');
    if (condicaoSelect) {
        condicaoSelect.addEventListener('change', () => {
            hasUnsavedChanges = true;
        });
    }
}

// Funções para marcar mudanças (não salvar automaticamente)
function marcarMudancaStatus() {
    hasUnsavedChanges = true;
}

function marcarMudancaCondicao() {
    hasUnsavedChanges = true;
}

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
    
    // Buscar horários informados pelo locatário (mesma lógica de renderizarDetalhes)
    let horariosLocatario = [];
    try {
        if (solicitacao.horarios_indisponiveis) {
            // Quando horarios_indisponiveis = 1, horários originais do locatário estão em datas_opcoes
            if (solicitacao.datas_opcoes) {
                horariosLocatario = typeof solicitacao.datas_opcoes === 'string' 
                    ? JSON.parse(solicitacao.datas_opcoes) 
                    : solicitacao.datas_opcoes;
                if (!Array.isArray(horariosLocatario)) {
                    horariosLocatario = [];
                }
            } else if (solicitacao.horarios_opcoes) {
                // Fallback: se datas_opcoes não existir, tentar buscar de horarios_opcoes
                horariosLocatario = typeof solicitacao.horarios_opcoes === 'string' 
                    ? JSON.parse(solicitacao.horarios_opcoes) 
                    : solicitacao.horarios_opcoes;
                if (!Array.isArray(horariosLocatario)) {
                    horariosLocatario = [];
                }
            }
        } else {
            // Quando horarios_indisponiveis = 0, horários do locatário estão em horarios_opcoes
            if (solicitacao.horarios_opcoes) {
                horariosLocatario = typeof solicitacao.horarios_opcoes === 'string' 
                    ? JSON.parse(solicitacao.horarios_opcoes) 
                    : solicitacao.horarios_opcoes;
                if (!Array.isArray(horariosLocatario)) {
                    horariosLocatario = [];
                }
            }
        }
    } catch (e) {
        console.error('Erro ao parsear horários do locatário:', e);
        horariosLocatario = [];
    }
    
    // Formatar data da visita do prestador (horários informados pelo locatário)
    let dataVisitaPrestador = '';
    if (horariosLocatario && horariosLocatario.length > 0) {
        // Normalizar cada horário usando a função corrigida
        dataVisitaPrestador = horariosLocatario.map(horario => {
            return normalizarHorarioCompleto(horario);
        }).filter(h => h).join('\n');
    } else if (solicitacao.data_agendamento && solicitacao.horario_agendamento) {
        // Fallback: usar data_agendamento se não houver horários do locatário
        const dataFormatada = formatarDataISO(solicitacao.data_agendamento);
        let horaFormatada = String(solicitacao.horario_agendamento).trim();
        
        // Se horario_agendamento for um horário simples "08:00", converter para faixa
        if (horaFormatada.match(/^\d{2}:\d{2}$/)) {
            const hora = parseInt(horaFormatada.substring(0, 2));
            if (hora >= 8 && hora < 11) {
                horaFormatada = '08:00 às 11:00';
            } else if (hora >= 11 && hora < 14) {
                horaFormatada = '11:00 às 14:00';
            } else if (hora >= 14 && hora < 17) {
                horaFormatada = '14:00 às 17:00';
            } else if (hora >= 17 && hora < 20) {
                horaFormatada = '17:00 às 20:00';
            } else {
                horaFormatada = horaFormatada + ' às 20:00';
            }
        }
        
        dataVisitaPrestador = `${dataFormatada} - ${horaFormatada}`;
    } else if (solicitacao.horario_confirmado_raw) {
        // Tentar extrair data e hora do horario_confirmado_raw
        dataVisitaPrestador = normalizarHorarioCompleto(solicitacao.horario_confirmado_raw);
    }
    
    // Montar informações no formato solicitado
    let info = `📋 INFORMAÇÕES DA SOLICITAÇÃO

🔢 Número da Solicitação VGT/KSS: ${solicitacao.numero_solicitacao || 'KSS' + solicitacao.id}

📊 Status: ${solicitacao.status_nome || 'Não informado'}

📅 Data da solicitação: ${dataCriacaoFormatada}

👤 DADOS DO SEGURADO

Nome: ${solicitacao.locatario_nome || 'Não informado'}

CPF: ${solicitacao.locatario_cpf || 'Não informado'}
Telefone: ${solicitacao.locatario_telefone || ''}
Nº do Contrato KSI: ${solicitacao.numero_contrato || ''}

Imobiliária: ${solicitacao.imobiliaria_nome || ''}

📅 DATA DA VISITA DO PRESTADOR SOLICITADO PELO SEGURADO

${dataVisitaPrestador || ''}

📍 ENDEREÇO DO IMÓVEL SEGURADO

Endereço: ${enderecoCompleto || ''}
Bairro/Cidade/Estado: ${localizacao || ''}
CEP: ${solicitacao.imovel_cep || ''}

📝 SERVIÇO SOLICITADO

Categoria: ${solicitacao.categoria_nome || ''}
Subcategoria: ${solicitacao.subcategoria_nome || ''}

📝 DESCRIÇÃO DO PROBLEMA

${solicitacao.descricao_problema || ''}

📝 OBSERVAÇÃO / ANEXO

${solicitacao.observacoes || ''}`.trim();
    
    navigator.clipboard.writeText(info).then(() => {
        alert('✅ Informações copiadas para a área de transferência!');
        fecharMenuCopiar();
    }).catch(err => {
        console.error('Erro ao copiar:', err);
        alert('Erro ao copiar informações. Por favor, tente novamente.');
    });
}

function toggleMenuCopiar() {
    const menu = document.getElementById('menuCopiar');
    menu.classList.toggle('hidden');
}

function fecharMenuCopiar() {
    const menu = document.getElementById('menuCopiar');
    menu.classList.add('hidden');
}

// Fechar menu ao clicar fora
document.addEventListener('click', function(event) {
    const menu = document.getElementById('menuCopiar');
    const button = event.target.closest('button[onclick="toggleMenuCopiar()"]');
    if (menu && !menu.contains(event.target) && !button) {
        menu.classList.add('hidden');
    }
});

function enviarInformacoesNoChat() {
    fecharMenuCopiar();
    
    if (!offcanvasSolicitacaoId) {
        alert('Nenhuma solicitação selecionada');
        return;
    }
    
    // Verificar se há conversa ativa (instância selecionada e atendimento ativo)
    const select = document.getElementById('chatWhatsappInstance');
    if (!select || !select.value) {
        alert('⚠️ Selecione uma instância WhatsApp e inicie o atendimento antes de enviar informações no chat.');
        // Abrir aba de chat se não estiver aberta
        mostrarAba('chat');
        return;
    }
    
    // Verificar se o atendimento está ativo (select desabilitado significa que está bloqueado/ativo)
    if (select.disabled === false) {
        alert('⚠️ Inicie o atendimento selecionando uma instância WhatsApp antes de enviar informações no chat.');
        mostrarAba('chat');
        return;
    }
    
    // Buscar dados da solicitação atual
    const solicitacao = window.solicitacaoAtual || null;
    
    if (!solicitacao) {
        alert('Erro: Dados da solicitação não encontrados. Por favor, recarregue a página.');
        return;
    }
    
    // Formatar informações (usar a mesma lógica de copiarInformacoes)
    const dataCriacao = formatarDataHora(solicitacao.created_at);
    const dataCriacaoFormatada = dataCriacao.replace(' às ', ' às ');
    
    let enderecoCompleto = '';
    if (solicitacao.imovel_endereco) {
        enderecoCompleto = solicitacao.imovel_endereco;
        if (solicitacao.imovel_numero) {
            enderecoCompleto += ', ' + solicitacao.imovel_numero;
        }
    }
    
    const localizacao = [solicitacao.imovel_bairro, solicitacao.imovel_cidade, solicitacao.imovel_estado].filter(Boolean).join(' - ');
    
    // Buscar horários informados pelo locatário (mesma lógica de renderizarDetalhes)
    let horariosLocatario = [];
    try {
        if (solicitacao.horarios_indisponiveis) {
            // Quando horarios_indisponiveis = 1, horários originais do locatário estão em datas_opcoes
            if (solicitacao.datas_opcoes) {
                horariosLocatario = typeof solicitacao.datas_opcoes === 'string' 
                    ? JSON.parse(solicitacao.datas_opcoes) 
                    : solicitacao.datas_opcoes;
                if (!Array.isArray(horariosLocatario)) {
                    horariosLocatario = [];
                }
            } else if (solicitacao.horarios_opcoes) {
                // Fallback: se datas_opcoes não existir, tentar buscar de horarios_opcoes
                horariosLocatario = typeof solicitacao.horarios_opcoes === 'string' 
                    ? JSON.parse(solicitacao.horarios_opcoes) 
                    : solicitacao.horarios_opcoes;
                if (!Array.isArray(horariosLocatario)) {
                    horariosLocatario = [];
                }
            }
        } else {
            // Quando horarios_indisponiveis = 0, horários do locatário estão em horarios_opcoes
            if (solicitacao.horarios_opcoes) {
                horariosLocatario = typeof solicitacao.horarios_opcoes === 'string' 
                    ? JSON.parse(solicitacao.horarios_opcoes) 
                    : solicitacao.horarios_opcoes;
                if (!Array.isArray(horariosLocatario)) {
                    horariosLocatario = [];
                }
            }
        }
    } catch (e) {
        console.error('Erro ao parsear horários do locatário:', e);
        horariosLocatario = [];
    }
    
    // Formatar data da visita do prestador (horários informados pelo locatário)
    let dataVisitaPrestador = '';
    if (horariosLocatario && horariosLocatario.length > 0) {
        // Normalizar cada horário usando a função corrigida
        dataVisitaPrestador = horariosLocatario.map(horario => {
            return normalizarHorarioCompleto(horario);
        }).filter(h => h).join('\n');
    } else if (solicitacao.data_agendamento && solicitacao.horario_agendamento) {
        // Fallback: usar data_agendamento se não houver horários do locatário
        const dataFormatada = formatarDataISO(solicitacao.data_agendamento);
        let horaFormatada = String(solicitacao.horario_agendamento).trim();
        
        // Se horario_agendamento for um horário simples "08:00", converter para faixa
        if (horaFormatada.match(/^\d{2}:\d{2}$/)) {
            const hora = parseInt(horaFormatada.substring(0, 2));
            if (hora >= 8 && hora < 11) {
                horaFormatada = '08:00 às 11:00';
            } else if (hora >= 11 && hora < 14) {
                horaFormatada = '11:00 às 14:00';
            } else if (hora >= 14 && hora < 17) {
                horaFormatada = '14:00 às 17:00';
            } else if (hora >= 17 && hora < 20) {
                horaFormatada = '17:00 às 20:00';
            } else {
                horaFormatada = horaFormatada + ' às 20:00';
            }
        }
        
        dataVisitaPrestador = `${dataFormatada} - ${horaFormatada}`;
    } else if (solicitacao.horario_confirmado_raw) {
        // Tentar extrair data e hora do horario_confirmado_raw
        dataVisitaPrestador = normalizarHorarioCompleto(solicitacao.horario_confirmado_raw);
    }
    
    // Montar informações formatadas no formato solicitado
    let mensagem = `📋 INFORMAÇÕES DA SOLICITAÇÃO

🔢 Número da Solicitação VGT/KSS: ${solicitacao.numero_solicitacao || 'KSS' + solicitacao.id}

📊 Status: ${solicitacao.status_nome || 'Não informado'}

📅 Data da solicitação: ${dataCriacaoFormatada}

👤 DADOS DO SEGURADO

Nome: ${solicitacao.locatario_nome || 'Não informado'}

CPF: ${solicitacao.locatario_cpf || 'Não informado'}
Telefone: ${solicitacao.locatario_telefone || ''}
Nº do Contrato KSI: ${solicitacao.numero_contrato || ''}

Imobiliária: ${solicitacao.imobiliaria_nome || ''}

📅 DATA DA VISITA DO PRESTADOR SOLICITADO PELO SEGURADO

${dataVisitaPrestador || ''}

📍 ENDEREÇO DO IMÓVEL SEGURADO

Endereço: ${enderecoCompleto || ''}
Bairro/Cidade/Estado: ${localizacao || ''}
CEP: ${solicitacao.imovel_cep || ''}

📝 SERVIÇO SOLICITADO

Categoria: ${solicitacao.categoria_nome || ''}
Subcategoria: ${solicitacao.subcategoria_nome || ''}

📝 DESCRIÇÃO DO PROBLEMA

${solicitacao.descricao_problema || ''}

📝 OBSERVAÇÃO / ANEXO

${solicitacao.observacoes || ''}`.trim();
    
    // Preencher o campo de mensagem e enviar
    // Abrir aba de chat se não estiver aberta
    mostrarAba('chat');
    
    // Aguardar um pouco para garantir que o chat está carregado
    setTimeout(() => {
        const inputMensagem = document.getElementById('chatMessageInput');
        if (inputMensagem) {
            inputMensagem.value = mensagem;
            // Enviar mensagem
            enviarMensagemChat();
        } else {
            alert('Erro: Campo de mensagem não encontrado. Por favor, aguarde o chat carregar.');
        }
    }, 500);
}

// Funções do Modal de Links de Ações
function abrirLinksAcoes() {
    if (!offcanvasSolicitacaoId) {
        alert('Nenhuma solicitação selecionada');
        return;
    }
    
    const modal = document.getElementById('modalLinksAcoes');
    const content = document.getElementById('linksAcoesContent');
    
    modal.classList.remove('hidden');
    
    // Mostrar loading
    content.innerHTML = `
        <div class="text-center py-8">
            <i class="fas fa-spinner fa-spin text-3xl text-gray-400 mb-3"></i>
            <p class="text-gray-600">Carregando links...</p>
        </div>
    `;
    
    // Buscar links da API
    fetch(`<?= url('admin/solicitacoes/') ?>${offcanvasSolicitacaoId}/api`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.solicitacao.links_acoes) {
                renderizarLinksAcoes(data.solicitacao.links_acoes);
            } else {
                content.innerHTML = `
                    <div class="text-center py-8">
                        <i class="fas fa-exclamation-triangle text-3xl text-yellow-400 mb-3"></i>
                        <p class="text-gray-600">Nenhum link encontrado para esta solicitação.</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Erro ao carregar links:', error);
            content.innerHTML = `
                <div class="text-center py-8">
                    <i class="fas fa-exclamation-triangle text-3xl text-red-400 mb-3"></i>
                    <p class="text-gray-600">Erro ao carregar links. Por favor, tente novamente.</p>
                </div>
            `;
        });
}

function fecharModalLinksAcoes() {
    const modal = document.getElementById('modalLinksAcoes');
    modal.classList.add('hidden');
}

function renderizarLinksAcoes(links) {
    const content = document.getElementById('linksAcoesContent');
    
    if (!links || links.length === 0) {
        content.innerHTML = `
            <div class="text-center py-8">
                <i class="fas fa-link text-3xl text-gray-300 mb-3"></i>
                <p class="text-gray-600">Nenhum link de ação foi gerado para esta solicitação.</p>
            </div>
        `;
        return;
    }
    
    // Agrupar links por status
    const linksAtivos = links.filter(l => l.status === 'ativo');
    const linksUsados = links.filter(l => l.status === 'usado');
    const linksExpirados = links.filter(l => l.status === 'expirado');
    const linksPermanentes = links.filter(l => l.status === 'permanente');
    
    let html = '';
    
    // Função para renderizar um link
    const renderizarLink = (link) => {
        const statusBadge = {
            'ativo': '<span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-medium rounded-full">Ativo</span>',
            'usado': '<span class="px-2 py-1 bg-gray-100 text-gray-800 text-xs font-medium rounded-full">Usado</span>',
            'expirado': '<span class="px-2 py-1 bg-red-100 text-red-800 text-xs font-medium rounded-full">Expirado</span>',
            'permanente': '<span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded-full">Permanente</span>'
        };
        
        const iconMap = {
            'Confirmação de Horário': 'fa-check-circle',
            'Cancelamento de Horário': 'fa-times-circle',
            'Reagendamento': 'fa-calendar-alt',
            'Compra de Peça': 'fa-shopping-cart',
            'Ações Pré-Serviço': 'fa-tools',
            'Ações Pós-Serviço': 'fa-clipboard-check',
            'Status da Solicitação': 'fa-info-circle',
            'Cancelar Solicitação': 'fa-ban',
            'Ação Genérica': 'fa-link'
        };
        
        const icon = iconMap[link.tipo] || 'fa-link';
        const statusHtml = statusBadge[link.status] || '';
        
        const dataInfo = link.criado_em 
            ? `<div class="text-xs text-gray-500 mt-1">
                Criado em: ${formatarDataHora(link.criado_em)}
                ${link.expira_em ? ` | Expira em: ${formatarDataHora(link.expira_em)}` : ''}
                ${link.usado_em ? ` | Usado em: ${formatarDataHora(link.usado_em)}` : ''}
               </div>`
            : '';
        
        return `
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200 hover:border-blue-300 transition-colors">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="fas ${icon} text-blue-600"></i>
                            <h4 class="font-medium text-gray-900">${link.tipo}</h4>
                            ${statusHtml}
                        </div>
                        <div class="flex items-center gap-2 mb-2">
                            <input type="text" 
                                   value="${link.url.replace(/"/g, '&quot;')}" 
                                   readonly 
                                   class="flex-1 px-3 py-2 bg-white border border-gray-300 rounded-md text-sm font-mono text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   id="link-${(link.token || link.action_type || 'link').replace(/[^a-zA-Z0-9]/g, '_')}">
                            <button onclick="copiarLink(this)" 
                                    data-link-id="${(link.token || link.action_type || 'link').replace(/[^a-zA-Z0-9]/g, '_')}"
                                    class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        ${dataInfo}
                    </div>
                </div>
            </div>
        `;
    };
    
    // Renderizar seções
    if (linksPermanentes.length > 0) {
        html += '<div class="mb-4"><h4 class="text-sm font-semibold text-gray-700 mb-2">Links Permanentes</h4>';
        linksPermanentes.forEach(link => {
            html += renderizarLink(link);
        });
        html += '</div>';
    }
    
    if (linksAtivos.length > 0) {
        html += '<div class="mb-4"><h4 class="text-sm font-semibold text-gray-700 mb-2">Links Ativos</h4>';
        linksAtivos.forEach(link => {
            html += renderizarLink(link);
        });
        html += '</div>';
    }
    
    if (linksUsados.length > 0) {
        html += '<div class="mb-4"><h4 class="text-sm font-semibold text-gray-700 mb-2">Links Usados</h4>';
        linksUsados.forEach(link => {
            html += renderizarLink(link);
        });
        html += '</div>';
    }
    
    if (linksExpirados.length > 0) {
        html += '<div class="mb-4"><h4 class="text-sm font-semibold text-gray-700 mb-2">Links Expirados</h4>';
        linksExpirados.forEach(link => {
            html += renderizarLink(link);
        });
        html += '</div>';
    }
    
    content.innerHTML = html;
}

function copiarLink(button) {
    const linkId = button.getAttribute('data-link-id');
    const input = document.getElementById(`link-${linkId}`);
    if (input && input.value) {
        navigator.clipboard.writeText(input.value).then(() => {
            // Feedback visual
            const originalHtml = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check"></i>';
            button.classList.remove('bg-blue-600', 'hover:bg-blue-700');
            button.classList.add('bg-green-600');
            
            setTimeout(() => {
                button.innerHTML = originalHtml;
                button.classList.remove('bg-green-600');
                button.classList.add('bg-blue-600', 'hover:bg-blue-700');
            }, 2000);
        }).catch(err => {
            console.error('Erro ao copiar link:', err);
            // Fallback para método antigo
            input.select();
            input.setSelectionRange(0, 99999);
            document.execCommand('copy');
            
            // Feedback visual mesmo com fallback
            const originalHtml = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check"></i>';
            button.classList.remove('bg-blue-600', 'hover:bg-blue-700');
            button.classList.add('bg-green-600');
            
            setTimeout(() => {
                button.innerHTML = originalHtml;
                button.classList.remove('bg-green-600');
                button.classList.add('bg-blue-600', 'hover:bg-blue-700');
            }, 2000);
        });
    }
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

function anexarDocumento(solicitacaoId) {
    const obsAnexo = document.getElementById('obsAnexoDocumento')?.value || '';
    const anexoInput = document.getElementById('anexoDocumento');
    
    if (!anexoInput || !anexoInput.files || anexoInput.files.length === 0) {
        alert('Por favor, selecione pelo menos um arquivo para anexar.');
        return;
    }
    
    const formData = new FormData();
    formData.append('solicitacao_id', solicitacaoId);
    formData.append('observacao', obsAnexo);
    
    for (let i = 0; i < anexoInput.files.length; i++) {
        formData.append('anexos[]', anexoInput.files[i]);
    }
    
    // Mostrar loading
    const btn = document.querySelector('button[onclick*="anexarDocumento"]');
    const originalText = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Enviando...';
    }
    
    fetch('<?= url('admin/solicitacoes/') ?>' + solicitacaoId + '/anexar-documento', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Documentos anexados com sucesso!');
            // Limpar campos
            const obsField = document.getElementById('obsAnexoDocumento');
            const anexoField = document.getElementById('anexoDocumento');
            if (obsField) obsField.value = '';
            if (anexoField) anexoField.value = '';
            // Recarregar detalhes
            abrirDetalhes(solicitacaoId);
        } else {
            alert('Erro: ' + (data.message || 'Erro ao anexar documentos'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao anexar documentos. Tente novamente.');
    })
    .finally(() => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    });
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
    
    // ✅ Coletar status e condição
    const statusId = document.getElementById('statusSelectKanban')?.value || '';
    const condicaoId = document.getElementById('condicaoSelectKanban')?.value || '';
    
    // ✅ Validação: Verificar se está tentando mudar para "Serviço Agendado"
    if (statusId) {
        const statusSelect = document.getElementById('statusSelectKanban');
        const statusNome = statusSelect.options[statusSelect.selectedIndex]?.text || '';
        const solicitacao = window.solicitacaoAtual || null;
        const statusAtual = solicitacao?.status_nome || '';
        
        if (statusNome === 'Serviço Agendado') {
            // Verificar se está vindo de "Buscando Prestador"
            if (statusAtual === 'Buscando Prestador') {
                // Verificar se há horário confirmado (salvo no banco ou marcado na interface)
                let temHorarioConfirmado = false;
                
                // Verificar horario_confirmado_raw
                if (solicitacao?.horario_confirmado_raw && solicitacao.horario_confirmado_raw.trim()) {
                    temHorarioConfirmado = true;
                }
                
                // Verificar confirmed_schedules
                if (!temHorarioConfirmado && solicitacao?.confirmed_schedules) {
                    try {
                        const confirmed = typeof solicitacao.confirmed_schedules === 'string' 
                            ? JSON.parse(solicitacao.confirmed_schedules) 
                            : solicitacao.confirmed_schedules;
                        if (Array.isArray(confirmed) && confirmed.length > 0) {
                            temHorarioConfirmado = true;
                        }
                    } catch (e) {
                        console.error('Erro ao parsear confirmed_schedules:', e);
                    }
                }
                
                // Verificar data_agendamento e horario_agendamento
                if (!temHorarioConfirmado && solicitacao?.data_agendamento && solicitacao?.horario_agendamento) {
                    temHorarioConfirmado = true;
                }
                
                // Verificar se há checkboxes marcados na interface (horários que serão salvos)
                if (!temHorarioConfirmado) {
                    const checkboxesMarcados = document.querySelectorAll('.horario-offcanvas:checked');
                    if (checkboxesMarcados.length > 0) {
                        temHorarioConfirmado = true;
                    }
                }
                
                if (!temHorarioConfirmado) {
                    // Restaurar botão
                    btnSalvar.innerHTML = originalText;
                    btnSalvar.disabled = originalDisabled;
                    btnSalvar.classList.add('hover:bg-blue-700');
                    
                    mostrarNotificacao('É necessário ter um horário confirmado para mudar de "Buscando Prestador" para "Serviço Agendado". Marque pelo menos um horário na seção "Disponibilidade Informada".', 'error');
                    
                    return;
                }
            }
            
            // Verificar protocolo
            if (!protocoloSeguradora.trim()) {
                // Restaurar botão
                btnSalvar.innerHTML = originalText;
                btnSalvar.disabled = originalDisabled;
                btnSalvar.classList.add('hover:bg-blue-700');
                
                mostrarNotificacao('É obrigatório preencher o protocolo da seguradora para mudar para "Serviço Agendado"', 'error');
                
                // Destacar o campo de protocolo
                const protocoloInput = document.getElementById('protocoloSeguradora');
                if (protocoloInput) {
                    protocoloInput.focus();
                    protocoloInput.classList.add('border-red-500', 'ring-2', 'ring-red-500');
                    setTimeout(() => {
                        protocoloInput.classList.remove('border-red-500', 'ring-2', 'ring-red-500');
                    }, 3000);
                }
                
                return;
            }
        }
    }
    
    // ✅ Coletar horários da seguradora da lista visual
    const horariosSeguradora = coletarHorariosSeguradoraVisual();
    
    // Criar objeto com os dados
    // ✅ Coletar horários selecionados
    // IMPORTANTE: Só enviar schedules se houver horários selecionados
    // Se não houver horários selecionados, não enviar schedules para não limpar os existentes
    const schedules = coletarSchedulesOffcanvas();
    const dados = {
        observacoes: observacoes,
        precisa_reembolso: precisaReembolso,
        valor_reembolso: valorReembolso,
        protocolo_seguradora: protocoloSeguradora
    };
    
    // ✅ Adicionar status_id e condicao_id se foram alterados
    if (statusId) {
        dados.status_id = statusId;
    }
    if (condicaoId) {
        dados.condicao_id = condicaoId;
    }
    
    // ✅ Só adicionar schedules se houver horários selecionados
    // Se o array estiver vazio, não enviar schedules para preservar os horários existentes
    if (schedules.length > 0) {
        dados.schedules = schedules;
    }
    
    // ✅ Adicionar horários da seguradora se houver
    if (horariosSeguradora.length > 0) {
        dados.horarios_seguradora = horariosSeguradora;
    }
    
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
            mostrarNotificacao('Erro ao salvar: ' + (data.error || 'Erro desconhecido'), 'error');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        // ✅ Restaurar botão em caso de erro
        btnSalvar.innerHTML = originalText;
        btnSalvar.disabled = originalDisabled;
        btnSalvar.classList.add('hover:bg-blue-700');
        mostrarNotificacao('Erro ao salvar alterações. Tente novamente.', 'error');
    });
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
    const checkboxes = Array.from(document.querySelectorAll('.horario-offcanvas:checked'));
    
    // ✅ DEBUG: Log dos checkboxes encontrados
    console.log('🔍 coletarSchedulesOffcanvas - Total de checkboxes marcados:', checkboxes.length);
    checkboxes.forEach((chk, idx) => {
        console.log(`  [${idx}] data-raw:`, chk.getAttribute('data-raw'));
    });
    
    // ✅ Mapear e processar
    const schedules = checkboxes
        .map(chk => {
            const raw = chk.getAttribute('data-raw');
            // ✅ Validar se raw é válido (não contém NaN ou formato inválido)
            if (!raw || raw.includes('NaN') || raw.trim() === '') {
                console.warn('⚠️ Horário inválido ignorado:', raw);
                return null;
            }
            const parsed = parseScheduleRawOffcanvas(raw);
            // ✅ Validar se o parse foi bem-sucedido
            if (!parsed.date && !parsed.time) {
                console.warn('⚠️ Horário não parseado corretamente:', raw);
                return null;
            }
            return parsed;
        })
        .filter(s => s !== null && (s.date || s.time));
    
    // ✅ Remover duplicatas baseado no raw (comparação precisa)
    const schedulesUnicos = [];
    const rawsProcessados = [];
    
    schedules.forEach(s => {
        const rawNorm = String(s.raw || '').trim().replace(/\s+/g, ' ');
        
        // ✅ Verificar se já processamos este raw
        const jaExiste = rawsProcessados.some(rp => {
            // Comparação exata primeiro
            if (rp === rawNorm) return true;
            
            // Comparação por regex (data e hora inicial)
            const regex = /(\d{2}\/\d{2}\/\d{4})\s*-\s*(\d{2}:\d{2})-(\d{2}:\d{2})/;
            const match1 = rp.match(regex);
            const match2 = rawNorm.match(regex);
            
            if (match1 && match2) {
                return (match1[1] === match2[1] && match1[2] === match2[2]);
            }
            
            return false;
        });
        
        if (!jaExiste) {
            rawsProcessados.push(rawNorm);
            schedulesUnicos.push(s);
        } else {
            console.log('⚠️ coletarSchedulesOffcanvas - Duplicata removida:', rawNorm);
        }
    });
    
    console.log('🔍 coletarSchedulesOffcanvas - Schedules únicos finais:', schedulesUnicos.length);
    console.log('🔍 coletarSchedulesOffcanvas - Schedules:', schedulesUnicos);
    
    return schedulesUnicos;
}

function formatarData(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR');
}

function formatarDataHora(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR') + ' às ' + date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
}

// Função auxiliar para formatar data de "2025-12-30 08:00:00" para "30/12/2025"
function formatarCpfCnpj(cpfCnpj) {
    if (!cpfCnpj) return '';
    
    // Remover caracteres não numéricos
    const numeros = cpfCnpj.replace(/\D/g, '');
    
    // CPF (11 dígitos)
    if (numeros.length === 11) {
        return numeros.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
    }
    
    // CNPJ (14 dígitos)
    if (numeros.length === 14) {
        return numeros.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
    }
    
    // Se não for CPF nem CNPJ, retornar original
    return cpfCnpj;
}

function formatarDataISO(dataString) {
    if (!dataString) return '';
    
    // Se for formato ISO com horário: "2025-12-30 08:00:00"
    if (dataString.includes(' ')) {
        const [dataParte] = dataString.split(' ');
        const [ano, mes, dia] = dataParte.split('-');
        return `${dia}/${mes}/${ano}`;
    }
    
    // Se for apenas data: "2025-12-30"
    if (dataString.includes('-')) {
        const [ano, mes, dia] = dataString.split('-');
        return `${dia}/${mes}/${ano}`;
    }
    
    return dataString;
}

// Função para extrair faixa de horário de um timestamp
function extrairFaixaHorario(timestamp) {
    if (!timestamp) return '';
    
    // Extrair hora do timestamp "2025-12-30 08:00:00"
    const match = timestamp.match(/(\d{2}):(\d{2}):\d{2}$/);
    if (!match) return '';
    
    const hora = parseInt(match[1]);
    const minuto = match[2];
    
    let faixa = '';
    if (hora >= 8 && hora < 11) {
        faixa = '08:00-11:00';
    } else if (hora >= 11 && hora < 14) {
        faixa = '11:00-14:00';
    } else if (hora >= 14 && hora < 17) {
        faixa = '14:00-17:00';
    } else if (hora >= 17 && hora < 20) {
        faixa = '17:00-20:00';
    } else {
        faixa = `${match[1]}:${minuto}-20:00`;
    }
    
    return faixa;
}

// Função para normalizar horário completo (aceita vários formatos e retorna formato padronizado)
function normalizarHorarioCompleto(horario) {
    if (!horario) return '';
    
    const horarioStr = String(horario).trim();
    
    // Formato 1: "dd/mm/yyyy - HH:MM-HH:MM" ou "dd/mm/yyyy - HH:MM às HH:MM" (já formatado)
    if (horarioStr.includes(' - ') || horarioStr.includes(' às ')) {
        // Converter hífen para "às" no formato de horário
        return horarioStr.replace(/(\d{2}:\d{2})-(\d{2}:\d{2})/g, '$1 às $2');
    }
    
    // Formato 2: "2025-12-30 08:00:00-11:00:00" (formato ISO do banco com faixa)
    if (horarioStr.match(/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}-\d{2}:\d{2}:\d{2}$/)) {
        const partes = horarioStr.split(' ');
        const dataParte = partes[0]; // "2025-12-30"
        const horaParte = partes[1]; // "08:00:00-11:00:00"
        
        const [ano, mes, dia] = dataParte.split('-');
        const [horaInicio, horaFim] = horaParte.split('-');
        const horaInicioFormatada = horaInicio.substring(0, 5); // "08:00"
        const horaFimFormatada = horaFim.substring(0, 5); // "11:00"
        
        return `${dia}/${mes}/${ano} - ${horaInicioFormatada} às ${horaFimFormatada}`;
    }
    
    // Formato 3: "2025-12-30 08:00:00" (formato ISO com horário simples)
    if (horarioStr.match(/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}$/)) {
        const partes = horarioStr.split(' ');
        const dataParte = partes[0]; // "2025-12-30"
        const horaParte = partes[1]; // "08:00:00"
        
        const [ano, mes, dia] = dataParte.split('-');
        const horaFormatada = horaParte.substring(0, 5); // "08:00"
        
        // Converter horário simples para faixa
        const hora = parseInt(horaFormatada.substring(0, 2));
        let faixaHorario = '';
        if (hora >= 8 && hora < 11) {
            faixaHorario = '08:00 às 11:00';
        } else if (hora >= 11 && hora < 14) {
            faixaHorario = '11:00 às 14:00';
        } else if (hora >= 14 && hora < 17) {
            faixaHorario = '14:00 às 17:00';
        } else if (hora >= 17 && hora < 20) {
            faixaHorario = '17:00 às 20:00';
        } else {
            faixaHorario = horaFormatada + ' às 20:00';
        }
        
        return `${dia}/${mes}/${ano} - ${faixaHorario}`;
    }
    
    // Formato 4: Apenas data "2025-12-30"
    if (horarioStr.match(/^\d{4}-\d{2}-\d{2}$/)) {
        const [ano, mes, dia] = horarioStr.split('-');
        return `${dia}/${mes}/${ano}`;
    }
    
    // Formato 5: Tentar como Date object
    const dateObj = new Date(horarioStr);
    if (!isNaN(dateObj.getTime())) {
        const dia = String(dateObj.getDate()).padStart(2, '0');
        const mes = String(dateObj.getMonth() + 1).padStart(2, '0');
        const ano = dateObj.getFullYear();
        const hora = String(dateObj.getHours()).padStart(2, '0');
        
        // Converter horário simples para faixa
        const horaInt = parseInt(hora);
        let faixaHorario = '';
        if (horaInt >= 8 && horaInt < 11) {
            faixaHorario = '08:00 às 11:00';
        } else if (horaInt >= 11 && horaInt < 14) {
            faixaHorario = '11:00 às 14:00';
        } else if (horaInt >= 14 && horaInt < 17) {
            faixaHorario = '14:00 às 17:00';
        } else if (horaInt >= 17 && horaInt < 20) {
            faixaHorario = '17:00 às 20:00';
        } else {
            faixaHorario = hora + ':00 às 20:00';
        }
        
        return `${dia}/${mes}/${ano} - ${faixaHorario}`;
    }
    
    // Se não conseguir normalizar, retornar string original
    return horarioStr;
}

// Função para reenviar mensagem WhatsApp
function reenviarMensagemWhatsApp(solicitacaoId, index, event) {
    event.stopPropagation(); // Evitar que o clique no card abra a visualização
    
    if (!whatsappHistoricoGlobal || !whatsappHistoricoGlobal[index]) {
        mostrarNotificacao('Mensagem não encontrada', 'error');
        return;
    }
    
    const envio = whatsappHistoricoGlobal[index];
    const tipo = envio.tipo;
    
    if (!tipo || tipo === 'N/A') {
        mostrarNotificacao('Tipo de mensagem não encontrado', 'error');
        return;
    }
    
    // Confirmar reenvio
    if (!confirm(`Deseja reenviar a mensagem "${tipo}"?`)) {
        return;
    }
    
    // Extrair extraData dos detalhes se disponível
    let extraData = {};
    if (envio.detalhes) {
        // Tentar extrair dados extras do histórico
        const detalhes = envio.detalhes;
        
        // Extrair dados de agendamento se disponíveis
        if (detalhes.data_agendamento) {
            extraData.data_agendamento = detalhes.data_agendamento;
        }
        if (detalhes.horario_agendamento) {
            extraData.horario_agendamento = detalhes.horario_agendamento;
        }
        if (detalhes.horarios_sugeridos) {
            extraData.horarios_sugeridos = detalhes.horarios_sugeridos;
        }
        if (detalhes.horario_servico) {
            extraData.horario_servico = detalhes.horario_servico;
        }
        if (detalhes.periodo_chegada) {
            extraData.periodo_chegada = detalhes.periodo_chegada;
        }
        // Adicionar outros campos que possam estar nos detalhes
        if (detalhes.status_atual) {
            extraData.status_atual = detalhes.status_atual;
        }
        if (detalhes.prestador_nome) {
            extraData.prestador_nome = detalhes.prestador_nome;
        }
        if (detalhes.prestador_telefone) {
            extraData.prestador_telefone = detalhes.prestador_telefone;
        }
    }
    
    // Mostrar loading
    const btn = event.target.closest('button');
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Enviando...';
    
    // Fazer requisição para reenviar
    fetch(`<?= url('admin/solicitacoes/') ?>${solicitacaoId}/reenviar-whatsapp`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            tipo: tipo,
            extra_data: extraData
        })
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        
        if (data.success) {
            mostrarNotificacao('Mensagem reenviada com sucesso!', 'success');
            // Recarregar detalhes para atualizar o histórico
            setTimeout(() => {
                carregarDetalhes(solicitacaoId);
            }, 1000);
        } else {
            mostrarNotificacao(data.message || 'Erro ao reenviar mensagem', 'error');
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        console.error('Erro:', error);
        mostrarNotificacao('Erro ao reenviar mensagem', 'error');
    });
}

// Função para exibir mensagem completa do WhatsApp
function verMensagemWhatsApp(index) {
    if (!whatsappHistoricoGlobal || !whatsappHistoricoGlobal[index]) {
        mostrarNotificacao('Mensagem não encontrada', 'error');
        return;
    }
    
    const envio = whatsappHistoricoGlobal[index];
    const mensagem = envio.mensagem || 'Mensagem não disponível';
    
    // Criar modal para exibir a mensagem
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4';
    modal.innerHTML = `
        <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-hidden flex flex-col">
            <div class="bg-green-600 text-white px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class="fab fa-whatsapp text-2xl"></i>
                    <div>
                        <h3 class="text-lg font-semibold">Mensagem WhatsApp</h3>
                        <p class="text-sm text-green-100">${envio.tipo} - ${formatarDataHora(envio.timestamp)}</p>
                    </div>
                </div>
                <button onclick="this.closest('.fixed').remove()" class="text-white hover:text-gray-200">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-6 overflow-y-auto flex-1">
                <div class="space-y-4">
                    ${envio.telefone ? `
                        <div class="flex items-center gap-2 text-gray-700">
                            <i class="fas fa-phone text-gray-500"></i>
                            <span class="font-medium">Telefone:</span>
                            <span>${envio.telefone}</span>
                        </div>
                    ` : ''}
                    ${envio.status === 'erro' && envio.erro ? `
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <div class="flex items-start gap-2">
                                <i class="fas fa-exclamation-triangle text-red-600 mt-1"></i>
                                <div>
                                    <p class="font-medium text-red-800 mb-1">Erro no envio:</p>
                                    <p class="text-sm text-red-700">${envio.erro}</p>
                                </div>
                            </div>
                        </div>
                    ` : ''}
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                        <p class="text-sm font-medium text-gray-700 mb-2">Mensagem enviada:</p>
                        <div class="bg-white rounded p-4 border border-gray-200 whitespace-pre-wrap text-sm text-gray-800">
                            ${mensagem.replace(/\n/g, '<br>')}
                        </div>
                    </div>
                    ${envio.detalhes ? `
                        <details class="mt-4">
                            <summary class="cursor-pointer text-sm text-gray-600 hover:text-gray-800">
                                <i class="fas fa-info-circle mr-2"></i>
                                Ver detalhes técnicos
                            </summary>
                            <div class="mt-2 bg-gray-50 rounded p-3 border border-gray-200">
                                <pre class="text-xs text-gray-700 overflow-x-auto">${JSON.stringify(envio.detalhes, null, 2)}</pre>
                            </div>
                        </details>
                    ` : ''}
                </div>
            </div>
            <div class="border-t border-gray-200 px-6 py-4 flex justify-end">
                <button onclick="this.closest('.fixed').remove()" 
                        class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg transition-colors">
                    Fechar
                </button>
            </div>
        </div>
    `;
    
    // Fechar ao clicar fora
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.remove();
        }
    });
    
    // Fechar com ESC
    const escHandler = function(e) {
        if (e.key === 'Escape') {
            modal.remove();
            document.removeEventListener('keydown', escHandler);
        }
    };
    document.addEventListener('keydown', escHandler);
    
    document.body.appendChild(modal);
}

// ======== Horários da Seguradora (Kanban) ========
function toggleAdicionarHorariosSeguradoraKanban(solicitacaoId, checked) {
    console.log('🔍 toggleAdicionarHorariosSeguradoraKanban chamado, solicitacaoId:', solicitacaoId, 'checked:', checked);
    
    const secao = document.getElementById('secao-adicionar-horarios-seguradora-kanban');
    
    if (!secao) {
        console.error('❌ Seção não encontrada!');
        return;
    }
    
    if (checked) {
        console.log('🔍 Mostrando seção...');
        secao.classList.remove('hidden');
        secao.style.display = 'block';
        // Atualizar status no banco
        atualizarHorariosIndisponiveisKanban(solicitacaoId, true);
    } else {
        console.log('🔍 Ocultando seção...');
        secao.classList.add('hidden');
        secao.style.display = 'none';
        // Atualizar status no banco
        atualizarHorariosIndisponiveisKanban(solicitacaoId, false);
    }
}

// Atualizar status de horários indisponíveis (Kanban) - APENAS VISUAL, não salva no banco
// O salvamento será feito quando clicar em "Salvar Alterações"
function atualizarHorariosIndisponiveisKanban(solicitacaoId, indisponivel) {
    // Apenas marcar que houve mudança, não salvar automaticamente
    hasUnsavedChanges = true;
    console.log('Horários indisponíveis alterados (será salvo ao clicar em "Salvar Alterações")');
}

// Confirmar horário selecionado pelo locatário
// Função removida - confirmação agora é feita diretamente pelos checkboxes na seção "Disponibilidade Informada"

// Adicionar horário da seguradora (Kanban) - APENAS VISUAL, não salva no banco
function adicionarHorarioSeguradoraKanban(solicitacaoId) {
    const data = document.getElementById('data-seguradora-kanban').value;
    const horarioRadio = document.querySelector('input[name="horario-seguradora-kanban"]:checked');
    
    if (!data) {
        mostrarNotificacao('Por favor, selecione uma data', 'error');
        return;
    }
    
    if (!horarioRadio) {
        mostrarNotificacao('Por favor, selecione um horário', 'error');
        return;
    }
    
    const horario = horarioRadio.value;
    const [horaInicio, horaFim] = horario.split('-');
    
    // Formatar horário: "dd/mm/yyyy - HH:00-HH:00"
    const dataObj = new Date(data + 'T' + horaInicio + ':00');
    const dia = String(dataObj.getDate()).padStart(2, '0');
    const mes = String(dataObj.getMonth() + 1).padStart(2, '0');
    const ano = dataObj.getFullYear();
    const horarioFormatado = `${dia}/${mes}/${ano} - ${horaInicio}:00-${horaFim}:00`;
    
    // Adicionar horário apenas visualmente (não salva no banco ainda)
    let listaHorarios = document.getElementById('lista-horarios-seguradora-kanban');
    let secaoHorarios = document.getElementById('secao-horarios-seguradora-kanban');
    
    // Se a seção não existe, criar ela
    if (!secaoHorarios) {
        // Buscar onde inserir (antes de "Anexar Documento")
        const anexarDocumento = Array.from(document.querySelectorAll('.bg-white.rounded-lg.p-5')).find(el => {
            return el.querySelector('.fa-paperclip');
        });
        if (anexarDocumento && anexarDocumento.parentNode) {
            const novaSecao = document.createElement('div');
            novaSecao.className = 'bg-white rounded-lg p-5';
            novaSecao.id = 'secao-horarios-seguradora-kanban';
            novaSecao.innerHTML = `
                <div class="flex items-center gap-2 mb-3">
                    <i class="fas fa-building text-blue-600"></i>
                    <h3 class="font-semibold text-gray-900">Disponibilidade Informada pela Seguradora</h3>
                </div>
                <div class="space-y-3" id="lista-horarios-seguradora-kanban">
                    <!-- Horários serão adicionados aqui dinamicamente -->
                </div>
            `;
            anexarDocumento.parentNode.insertBefore(novaSecao, anexarDocumento);
            listaHorarios = document.getElementById('lista-horarios-seguradora-kanban');
            secaoHorarios = document.getElementById('secao-horarios-seguradora-kanban');
        }
    }
    
    if (listaHorarios) {
        // Verificar se o horário já existe na lista
        const horariosExistentes = Array.from(listaHorarios.querySelectorAll('.text-sm.font-medium.text-blue-900'));
        const jaExiste = horariosExistentes.some(el => el.textContent.trim() === horarioFormatado);
        
        if (jaExiste) {
            mostrarNotificacao('Este horário já foi adicionado', 'error');
            return;
        }
        
        // Criar elemento do novo horário
        const horarioEscapado = horarioFormatado.replace(/'/g, "\\'").replace(/"/g, '&quot;');
        const novoHorario = document.createElement('div');
        novoHorario.className = 'bg-blue-50 border border-blue-200 rounded-lg p-3 flex items-center justify-between';
        novoHorario.setAttribute('data-horario-seguradora', horarioFormatado);
        novoHorario.innerHTML = `
            <div class="flex items-center gap-3">
                <i class="fas fa-clock text-blue-600"></i>
                <span class="text-sm font-medium text-blue-900">${horarioFormatado}</span>
            </div>
            <button onclick="removerHorarioSeguradoraKanban(${solicitacaoId}, '${horarioEscapado}')" 
                    class="text-red-600 hover:text-red-800">
                <i class="fas fa-times"></i>
            </button>
        `;
        listaHorarios.appendChild(novoHorario);
        
        // Mostrar a seção se estava oculta
        if (secaoHorarios) {
            secaoHorarios.classList.remove('hidden');
        }
        
        // Marcar que há mudanças não salvas
        hasUnsavedChanges = true;
        
        // Limpar formulário
        document.getElementById('data-seguradora-kanban').value = '';
        document.querySelectorAll('input[name="horario-seguradora-kanban"]').forEach(radio => radio.checked = false);
        
        mostrarNotificacao('Horário adicionado (será salvo ao clicar em "Salvar Alterações")', 'info');
    } else {
        mostrarNotificacao('Erro: Seção não encontrada', 'error');
    }
}

// Coletar horários da seguradora da lista visual
function coletarHorariosSeguradoraVisual() {
    const listaHorarios = document.getElementById('lista-horarios-seguradora-kanban');
    if (!listaHorarios) {
        return [];
    }
    
    const horarios = [];
    const elementos = listaHorarios.querySelectorAll('[data-horario-seguradora]');
    elementos.forEach(el => {
        const horario = el.getAttribute('data-horario-seguradora');
        if (horario) {
            horarios.push(horario);
        }
    });
    
    return horarios;
}

// Remover horário da seguradora (Kanban) - APENAS VISUAL, não salva no banco
function removerHorarioSeguradoraKanban(solicitacaoId, horario) {
    const listaHorarios = document.getElementById('lista-horarios-seguradora-kanban');
    if (!listaHorarios) {
        return;
    }
    
    // Encontrar e remover o elemento visual
    const elementos = listaHorarios.querySelectorAll('[data-horario-seguradora]');
    elementos.forEach(el => {
        const horarioAtual = el.getAttribute('data-horario-seguradora');
        if (horarioAtual === horario) {
            el.remove();
            hasUnsavedChanges = true;
            mostrarNotificacao('Horário removido (será salvo ao clicar em "Salvar Alterações")', 'info');
        }
    });
    
    // Se não há mais horários, ocultar a seção
    if (listaHorarios.children.length === 0) {
        const secaoHorarios = document.getElementById('secao-horarios-seguradora-kanban');
        if (secaoHorarios) {
            secaoHorarios.classList.add('hidden');
        }
    }
}

// Função para salvar condição no Kanban
function salvarCondicaoKanban(solicitacaoId, condicaoId) {
    if (!solicitacaoId) {
        mostrarNotificacao('Erro: Dados inválidos', 'error');
        return;
    }
    
    // Mostrar loading
    const select = document.getElementById('condicaoSelectKanban');
    if (select) {
        select.disabled = true;
    }
    
    fetch('<?= url('admin/kanban/atualizar-condicao') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            solicitacao_id: solicitacaoId,
            condicao_id: condicaoId || null
        })
    })
    .then(response => response.json())
    .then(data => {
        if (select) {
            select.disabled = false;
        }
        
        if (data.success) {
            mostrarNotificacao('Condição atualizada com sucesso!', 'success');
        } else {
            mostrarNotificacao('Erro: ' + (data.error || 'Não foi possível atualizar a condição'), 'error');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        if (select) {
            select.disabled = false;
        }
        mostrarNotificacao('Erro ao atualizar condição', 'error');
    });
}

// Função para salvar status no Kanban
function salvarStatusKanban(solicitacaoId, novoStatusId) {
    if (!solicitacaoId || !novoStatusId) {
        mostrarNotificacao('Erro: Dados inválidos', 'error');
        return;
    }
    
    // Mostrar loading
    const select = document.getElementById('statusSelectKanban');
    if (select) {
        select.disabled = true;
    }
    
    fetch(`<?= url('admin/solicitacoes/') ?>${solicitacaoId}/status`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            status_id: novoStatusId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarNotificacao('Status atualizado com sucesso!', 'success');
            
            // Fechar o modal
            fecharDetalhes();
            
            // Recarregar a página para atualizar o Kanban
            setTimeout(() => {
                window.location.reload();
            }, 500);
        } else {
            mostrarNotificacao('Erro: ' + (data.error || 'Não foi possível atualizar o status'), 'error');
            if (select) {
                select.disabled = false;
            }
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        mostrarNotificacao('Erro ao atualizar status', 'error');
        if (select) {
            select.disabled = false;
        }
    });
}

// Função para abrir foto em modal
window.abrirFotoModal = function(urlFoto, nomeArquivo = null) {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-90 z-50 flex items-center justify-center p-4';
    
    // Extrair nome do arquivo da URL para download se não foi fornecido
    if (!nomeArquivo) {
        nomeArquivo = urlFoto.split('/').pop() || 'foto.jpg';
    }
    
    // Criar elementos do modal
    const container = document.createElement('div');
    container.className = 'relative max-w-6xl max-h-full w-full';
    
    // Botão de download
    const btnDownload = document.createElement('button');
    btnDownload.className = 'text-white hover:text-gray-300 text-xl bg-black bg-opacity-50 rounded-full w-10 h-10 flex items-center justify-center transition-all hover:bg-opacity-70';
    btnDownload.title = 'Baixar foto';
    btnDownload.innerHTML = '<i class="fas fa-download"></i>';
    btnDownload.onclick = function(e) {
        e.stopPropagation();
        if (typeof window.baixarFoto === 'function') {
            window.baixarFoto(urlFoto, nomeArquivo);
        } else {
            console.error('Função baixarFoto não encontrada');
            // Fallback: abrir em nova aba
            window.open(urlFoto, '_blank');
        }
    };
    
    // Botão de fechar
    const btnFechar = document.createElement('button');
    btnFechar.className = 'text-white hover:text-gray-300 text-3xl bg-black bg-opacity-50 rounded-full w-10 h-10 flex items-center justify-center transition-all hover:bg-opacity-70';
    btnFechar.title = 'Fechar';
    btnFechar.innerHTML = '<i class="fas fa-times"></i>';
    btnFechar.onclick = function(e) {
        e.stopPropagation();
        modal.remove();
    };
    
    // Container dos botões
    const botoesContainer = document.createElement('div');
    botoesContainer.className = 'absolute -top-12 right-0 flex gap-2 z-10';
    botoesContainer.appendChild(btnDownload);
    botoesContainer.appendChild(btnFechar);
    
    // Imagem
    const img = document.createElement('img');
    img.src = urlFoto;
    img.alt = 'Foto ampliada';
    img.className = 'max-w-full max-h-[90vh] rounded-lg mx-auto block object-contain cursor-zoom-in';
    img.onclick = function(e) {
        e.stopPropagation();
    };
    img.onerror = function() {
        this.onerror = null;
        this.src = 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'400\' height=\'300\'%3E%3Crect fill=\'%23ddd\' width=\'400\' height=\'300\'/%3E%3Ctext fill=\'%23999\' font-family=\'sans-serif\' font-size=\'18\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dominant-baseline=\'middle\'%3EErro ao carregar imagem%3C/text%3E%3C/svg%3E';
    };
    
    container.appendChild(botoesContainer);
    container.appendChild(img);
    modal.appendChild(container);
    
    modal.onclick = function(e) {
        if (e.target === modal) {
            modal.remove();
        }
    };
    
    document.body.appendChild(modal);
    
    // Fechar com ESC
    const escHandler = function(e) {
        if (e.key === 'Escape') {
            modal.remove();
            document.removeEventListener('keydown', escHandler);
        }
    };
    document.addEventListener('keydown', escHandler);
}

// Função para baixar foto
window.baixarFoto = function(urlFoto, nomeArquivo) {
    fetch(urlFoto)
        .then(response => response.blob())
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = nomeArquivo;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        })
        .catch(error => {
            console.error('Erro ao baixar foto:', error);
            // Fallback: abrir em nova aba
            window.open(urlFoto, '_blank');
        });
}

// Event delegation para cliques em fotos do kanban
document.addEventListener('click', function(e) {
    // Verificar se o clique foi em um container de foto
    const fotoContainer = e.target.closest('.foto-container');
    if (fotoContainer) {
        const urlFoto = fotoContainer.getAttribute('data-foto-url');
        const nomeArquivo = fotoContainer.getAttribute('data-foto-nome');
        if (urlFoto) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Clique na foto detectado:', { urlFoto, nomeArquivo });
            if (typeof window.abrirFotoModal === 'function') {
                window.abrirFotoModal(urlFoto, nomeArquivo);
            } else {
                console.error('Função abrirFotoModal não encontrada');
                // Fallback: tentar abrir em nova aba
                window.open(urlFoto, '_blank');
            }
        }
    }
}, true); // Usar capture phase para garantir que seja executado

// Estilização dos cards de horário da seguradora (Kanban)
document.addEventListener('change', function(e) {
    if (e.target.name === 'horario-seguradora-kanban') {
        document.querySelectorAll('.horario-seguradora-card-kanban').forEach(card => {
            card.classList.remove('border-blue-500', 'bg-blue-100');
            card.classList.add('border-gray-200');
        });
        
        const selectedCard = e.target.closest('label').querySelector('.horario-seguradora-card-kanban');
        if (selectedCard) {
            selectedCard.classList.remove('border-gray-200');
            selectedCard.classList.add('border-blue-500', 'bg-blue-100');
        }
    }
});

// ============================================
// ATUALIZAÇÃO AUTOMÁTICA DE NOVAS SOLICITAÇÕES
// ============================================
(function() {
    'use strict';
    
    // Encontrar a coluna "Nova Solicitação"
    let colunaNovaSolicitacao = null;
    let statusNovaSolicitacaoId = null;
    let solicitacoesExistentes = new Set();
    let intervaloAtualizacao = null;
    
    // Função para encontrar a coluna "Nova Solicitação"
    function encontrarColunaNovaSolicitacao() {
        const colunas = document.querySelectorAll('.kanban-column');
        for (let coluna of colunas) {
            const titulo = coluna.querySelector('h3');
            if (titulo && titulo.textContent.trim() === 'Nova Solicitação') {
                colunaNovaSolicitacao = coluna;
                const cardsContainer = coluna.querySelector('.kanban-cards');
                if (cardsContainer) {
                    statusNovaSolicitacaoId = cardsContainer.getAttribute('data-status-id');
                }
                break;
            }
        }
    }
    
    // Função para coletar IDs das solicitações existentes
    function coletarIdsExistentes() {
        solicitacoesExistentes.clear();
        if (colunaNovaSolicitacao) {
            const cards = colunaNovaSolicitacao.querySelectorAll('[data-solicitacao-id]');
            cards.forEach(card => {
                const id = card.getAttribute('data-solicitacao-id');
                if (id) {
                    solicitacoesExistentes.add(parseInt(id));
                }
            });
        }
    }
    
    // Função para criar HTML do card
    function criarCardHTML(solicitacao, statusCor) {
        const numeroSolicitacao = solicitacao.numero_solicitacao || ('KSS' + solicitacao.id);
        const categoriaNome = solicitacao.categoria_nome || 'Sem categoria';
        const subcategoriaNome = solicitacao.subcategoria_nome || '';
        const locatarioNome = solicitacao.locatario_nome || 'Não informado';
        const endereco = solicitacao.imovel_endereco ? 
            (solicitacao.imovel_endereco + (solicitacao.imovel_numero ? ', ' + solicitacao.imovel_numero : '')) : 
            'Endereço não informado';
        const dataCriacao = new Date(solicitacao.created_at).toLocaleDateString('pt-BR');
        const logoUrl = solicitacao.imobiliaria_logo ? 
            `<?= url('Public/uploads/logos/') ?>${solicitacao.imobiliaria_logo}` : '';
        const imobiliariaNome = solicitacao.imobiliaria_nome || '';
        const condicaoNome = solicitacao.condicao_nome || '';
        const condicaoCor = solicitacao.condicao_cor || '#6B7280';
        const numeroContrato = solicitacao.numero_contrato || '';
        const protocoloSeguradora = solicitacao.protocolo_seguradora || '';
        const isEmergencial = solicitacao.is_emergencial_fora_horario || false;
        const isEmergencialSubcategoria = solicitacao.subcategoria_is_emergencial || false;
        const prioridade = solicitacao.prioridade || '';
        
        // Determinar se deve mostrar "Emergencial" ou prioridade
        let mostrarPrioridade = false;
        let textoPrioridade = '';
        let corPrioridade = '';
        
        if (isEmergencialSubcategoria || isEmergencial) {
            mostrarPrioridade = true;
            textoPrioridade = 'Emergencial';
            corPrioridade = 'bg-red-100 text-red-800';
        } else if (prioridade && prioridade !== 'NORMAL') {
            mostrarPrioridade = true;
            textoPrioridade = prioridade;
            corPrioridade = prioridade === 'ALTA' ? 'bg-red-100 text-red-800' : 
                           (prioridade === 'MEDIA' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800');
        }
        
        return `
            <div class="kanban-card bg-white rounded-lg shadow-sm p-4 cursor-pointer hover:shadow-md transition-shadow border-l-4" 
                 style="border-color: ${statusCor}"
                 data-solicitacao-id="${solicitacao.id}"
                 data-status-id="${solicitacao.status_id}"
                 id="kanban-card-${solicitacao.id}"
                 onclick="abrirDetalhes(${solicitacao.id})">
                
                <!-- Header do Card -->
                <div class="flex items-start justify-between mb-3">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <h4 class="font-semibold text-gray-900 text-sm">${numeroSolicitacao}</h4>
                            <span class="chat-badge-${solicitacao.id} hidden ml-1 px-1.5 py-0.5 text-xs font-bold text-white bg-red-500 rounded-full" title="Mensagens não lidas"></span>
                            ${solicitacao.chat_whatsapp_instance_id && solicitacao.chat_atendimento_ativo ? `
                                <i class="fab fa-whatsapp text-green-500 text-sm" 
                                   title="WhatsApp ativo: ${solicitacao.whatsapp_instance_nome || 'Instância ativa'}"></i>
                            ` : ''}
                            ${numeroContrato ? `
                                <span class="text-xs text-gray-500">Contrato: ${numeroContrato}</span>
                            ` : ''}
                        </div>
                        <div class="flex items-center text-xs text-gray-600 mt-1">
                            <i class="fas fa-wrench w-3 mr-1 text-gray-400"></i>
                            <span class="truncate">${categoriaNome}</span>
                        </div>
                    </div>
                    <div class="flex flex-col items-end gap-1">
                        ${logoUrl ? `
                            <img src="${logoUrl}" 
                                 alt="${imobiliariaNome}" 
                                 class="h-7 w-auto"
                                 onerror="this.style.display='none';">
                        ` : ''}
                        ${condicaoNome ? `
                            <span class="inline-block px-2 py-0.5 rounded-md text-xs font-medium" 
                                  style="background-color: ${condicaoCor}20; color: ${condicaoCor}">
                                ${condicaoNome}
                            </span>
                        ` : ''}
                    </div>
                </div>
                
                <!-- Informações do Card -->
                <div class="space-y-1 text-xs text-gray-600">
                    ${subcategoriaNome ? `
                        <div class="flex items-center">
                            <i class="fas fa-list w-4 mr-1 text-gray-400"></i>
                            <span class="truncate">${subcategoriaNome}</span>
                        </div>
                    ` : ''}
                    ${protocoloSeguradora ? `
                        <div class="flex items-center">
                            <i class="fas fa-hashtag w-4 mr-1 text-gray-400"></i>
                            <span class="truncate text-xs text-gray-500">Protocolo: ${protocoloSeguradora}</span>
                        </div>
                    ` : ''}
                    <div class="flex items-center">
                        <i class="fas fa-user w-4 mr-1 text-gray-400"></i>
                        <span class="truncate">${locatarioNome}</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-map-marker-alt w-4 mr-1 text-gray-400"></i>
                        <span class="truncate">${endereco}</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-calendar w-4 mr-1 text-gray-400"></i>
                        <span title="Data de Registro">${dataCriacao}</span>
                    </div>
                    
                    ${(() => {
                        // Processar horários para exibição
                        let horariosExibir = [];
                        
                        // Se houver horário confirmado, mostrar apenas esse
                        if (solicitacao.horario_confirmado_raw) {
                            horariosExibir.push(solicitacao.horario_confirmado_raw);
                        } else if (solicitacao.confirmed_schedules) {
                            try {
                                const confirmed = typeof solicitacao.confirmed_schedules === 'string' 
                                    ? JSON.parse(solicitacao.confirmed_schedules) 
                                    : solicitacao.confirmed_schedules;
                                if (Array.isArray(confirmed)) {
                                    confirmed.forEach(conf => {
                                        if (conf.raw) horariosExibir.push(conf.raw);
                                    });
                                }
                            } catch (e) {
                                console.error('Erro ao parsear confirmed_schedules:', e);
                            }
                        } else {
                            // Buscar horários do locatário
                            let horariosLocatario = [];
                            
                            if (solicitacao.horarios_indisponiveis && solicitacao.datas_opcoes) {
                                try {
                                    horariosLocatario = typeof solicitacao.datas_opcoes === 'string' 
                                        ? JSON.parse(solicitacao.datas_opcoes) 
                                        : solicitacao.datas_opcoes;
                                } catch (e) {
                                    console.error('Erro ao parsear datas_opcoes:', e);
                                }
                            } else if (solicitacao.horarios_opcoes) {
                                try {
                                    horariosLocatario = typeof solicitacao.horarios_opcoes === 'string' 
                                        ? JSON.parse(solicitacao.horarios_opcoes) 
                                        : solicitacao.horarios_opcoes;
                                } catch (e) {
                                    console.error('Erro ao parsear horarios_opcoes:', e);
                                }
                            }
                            
                            // Processar horários
                            if (Array.isArray(horariosLocatario)) {
                                horariosLocatario.forEach(horario => {
                                    if (typeof horario === 'string') {
                                        // Formato já esperado: "13/11/2025 - 08:00-11:00"
                                        if (horario.includes(' - ')) {
                                            horariosExibir.push(horario);
                                        }
                                        // Formato timestamp: "2025-11-13 08:00:00"
                                        else {
                                            const match = horario.match(/^(\d{4})-(\d{2})-(\d{2})\s+(\d{2}):(\d{2}):\d{2}$/);
                                            if (match) {
                                                const data = match[3] + '/' + match[2] + '/' + match[1];
                                                const horaInt = parseInt(match[4]);
                                                
                                                let faixa = '';
                                                if (horaInt >= 8 && horaInt < 11) {
                                                    faixa = '08:00-11:00';
                                                } else if (horaInt >= 11 && horaInt < 14) {
                                                    faixa = '11:00-14:00';
                                                } else if (horaInt >= 14 && horaInt < 17) {
                                                    faixa = '14:00-17:00';
                                                } else if (horaInt >= 17 && horaInt < 20) {
                                                    faixa = '17:00-20:00';
                                                } else {
                                                    faixa = match[4] + ':' + match[5] + '-20:00';
                                                }
                                                
                                                horariosExibir.push(data + ' - ' + faixa);
                                            }
                                        }
                                    }
                                });
                            }
                        }
                        
                        // Limitar a 3 horários
                        horariosExibir = horariosExibir.slice(0, 3);
                        
                        if (horariosExibir.length === 0) return '';
                        
                        const isAgendado = solicitacao.horario_confirmado_raw || solicitacao.confirmed_schedules;
                        
                        let html = '<div class="mt-2 pt-2 border-t border-gray-200">';
                        
                        if (isAgendado) {
                            // Quando já foi agendado
                            html += '<div class="text-xs">';
                            html += '<div class="text-gray-500 mb-1">Serviço agendado em:</div>';
                            horariosExibir.forEach(horario => {
                                const partes = horario.split(' - ');
                                const data = partes[0] || '';
                                let horarioTexto = partes[1] || '';
                                
                                // Converter formato de horário se necessário
                                const matchHorario = horarioTexto.match(/^(\d{2}):(\d{2})-(\d{2}):(\d{2})$/);
                                if (matchHorario) {
                                    horarioTexto = matchHorario[1] + 'h' + matchHorario[2] + ' às ' + matchHorario[3] + 'h' + matchHorario[4];
                                }
                                
                                html += `<div class="font-medium text-gray-900">${data}</div>`;
                                html += `<div class="text-gray-600">${horarioTexto}</div>`;
                            });
                            html += '</div>';
                        } else {
                            // Quando ainda não foi agendado (múltiplas opções)
                            // Linha de Datas
                            html += '<div class="grid grid-cols-3 gap-2 text-xs mb-1">';
                            horariosExibir.forEach(horario => {
                                const partes = horario.split(' - ');
                                const data = partes[0] || '';
                                html += `<div class="font-medium text-gray-900 text-center">${data}</div>`;
                            });
                            // Preencher espaços vazios se houver menos de 3
                            for (let i = horariosExibir.length; i < 3; i++) {
                                html += '<div></div>';
                            }
                            html += '</div>';
                            
                            // Linha de Horários
                            html += '<div class="grid grid-cols-3 gap-2 text-xs">';
                            horariosExibir.forEach(horario => {
                                const partes = horario.split(' - ');
                                let horarioTexto = partes[1] || '';
                                
                                // Converter formato de horário se necessário
                                const matchHorario = horarioTexto.match(/^(\d{2}):(\d{2})-(\d{2}):(\d{2})$/);
                                if (matchHorario) {
                                    horarioTexto = matchHorario[1] + 'h' + matchHorario[2] + ' às ' + matchHorario[3] + 'h' + matchHorario[4];
                                }
                                
                                html += `<div class="text-gray-600 text-center whitespace-nowrap truncate" title="${horarioTexto}">${horarioTexto}</div>`;
                            });
                            // Preencher espaços vazios se houver menos de 3
                            for (let i = horariosExibir.length; i < 3; i++) {
                                html += '<div></div>';
                            }
                            html += '</div>';
                        }
                        
                        html += '</div>';
                        return html;
                    })()}
                </div>
                
                ${mostrarPrioridade ? `
                    <div class="mt-3 flex items-center gap-2 flex-wrap">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${corPrioridade}">
                            <i class="fas fa-${(isEmergencialSubcategoria || isEmergencial) ? 'exclamation-triangle' : 'exclamation-circle'} mr-1"></i>
                            ${textoPrioridade}
                        </span>
                        ${solicitacao.is_emergencial_fora_horario ? `
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                <i class="fas fa-phone mr-1"></i>
                                0800
                            </span>
                        ` : ''}
                    </div>
                ` : ''}
            </div>
        `;
    }
    
    // Função para atualizar a coluna com novas solicitações
    function atualizarColunaNovaSolicitacao(solicitacoes) {
        if (!colunaNovaSolicitacao || !solicitacoes || solicitacoes.length === 0) {
            return;
        }
        
        const cardsContainer = colunaNovaSolicitacao.querySelector('.kanban-cards');
        if (!cardsContainer) {
            return;
        }
        
        // Coletar IDs existentes antes de adicionar novas
        coletarIdsExistentes();
        
        // Encontrar status cor
        const statusCor = colunaNovaSolicitacao.querySelector('.w-3.h-3')?.style.backgroundColor || '#3B82F6';
        
        // Filtrar apenas novas solicitações (que não existem ainda)
        const novasSolicitacoes = solicitacoes.filter(s => !solicitacoesExistentes.has(parseInt(s.id)));
        
        if (novasSolicitacoes.length === 0) {
            return; // Nenhuma nova solicitação
        }
        
        // Remover mensagem "Nenhuma solicitação" se existir
        const mensagemVazia = cardsContainer.querySelector('.text-center.py-8');
        if (mensagemVazia) {
            mensagemVazia.remove();
        }
        
        // Adicionar novas solicitações no topo (mais recentes primeiro)
        novasSolicitacoes.reverse().forEach(solicitacao => {
            const cardHTML = criarCardHTML(solicitacao, statusCor);
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = cardHTML.trim();
            const novoCard = tempDiv.firstElementChild;
            
            // Adicionar no início do container
            if (cardsContainer.firstChild) {
                cardsContainer.insertBefore(novoCard, cardsContainer.firstChild);
            } else {
                cardsContainer.appendChild(novoCard);
            }
            
            // Adicionar ao Set de existentes
            solicitacoesExistentes.add(parseInt(solicitacao.id));
            
            // Adicionar animação de entrada
            novoCard.style.opacity = '0';
            novoCard.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                novoCard.style.transition = 'all 0.3s ease-in-out';
                novoCard.style.opacity = '1';
                novoCard.style.transform = 'translateY(0)';
            }, 10);
        });
        
        // Atualizar contador
        const contador = colunaNovaSolicitacao.querySelector('.bg-gray-200');
        if (contador) {
            const totalCards = cardsContainer.querySelectorAll('.kanban-card').length;
            contador.textContent = totalCards;
        }
        
        // Mostrar notificação discreta
        if (novasSolicitacoes.length > 0) {
            console.log(`✅ ${novasSolicitacoes.length} nova(s) solicitação(ões) adicionada(s)`);
        }
    }
    
    // Função para buscar novas solicitações via AJAX
    function buscarNovasSolicitacoes() {
        const imobiliariaId = new URLSearchParams(window.location.search).get('imobiliaria_id') || '';
        const url = `<?= url('admin/kanban/novas-solicitacoes') ?>${imobiliariaId ? '?imobiliaria_id=' + imobiliariaId : ''}`;
        
        fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.solicitacoes) {
                atualizarColunaNovaSolicitacao(data.solicitacoes);
            }
        })
        .catch(error => {
            console.error('Erro ao buscar novas solicitações:', error);
        });
    }
    
    // Inicializar quando a página carregar
    document.addEventListener('DOMContentLoaded', function() {
        encontrarColunaNovaSolicitacao();
        
        if (colunaNovaSolicitacao) {
            // Coletar IDs iniciais
            coletarIdsExistentes();
            
            // Iniciar polling a cada 3 segundos
            intervaloAtualizacao = setInterval(buscarNovasSolicitacoes, 3000);
            
            console.log('✅ Atualização automática de novas solicitações ativada (a cada 3 segundos)');
        } else {
            console.warn('⚠️ Coluna "Nova Solicitação" não encontrada');
        }
    });
    
    // Parar polling quando a página for escondida (otimização)
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            if (intervaloAtualizacao) {
                clearInterval(intervaloAtualizacao);
                intervaloAtualizacao = null;
            }
        } else {
            if (!intervaloAtualizacao && colunaNovaSolicitacao) {
                intervaloAtualizacao = setInterval(buscarNovasSolicitacoes, 3000);
            }
        }
    });
})();

    // ==================== FUNÇÕES DO CHAT ====================
    let chatSolicitacaoId = null;
    let chatPollingInterval = null;

    function mostrarAba(aba) {
        const detalhesContent = document.getElementById('detalhesContent');
        const chatContent = document.getElementById('chatContent');
        const tabDetalhes = document.getElementById('tabDetalhes');
        const tabChat = document.getElementById('tabChat');

        if (aba === 'detalhes') {
            detalhesContent.classList.remove('hidden');
            chatContent.classList.add('hidden');
            tabDetalhes.classList.add('border-blue-600', 'text-blue-600');
            tabDetalhes.classList.remove('border-transparent', 'text-gray-600');
            tabChat.classList.remove('border-blue-600', 'text-blue-600');
            tabChat.classList.add('border-transparent', 'text-gray-600');
            if (chatPollingInterval) {
                clearInterval(chatPollingInterval);
                chatPollingInterval = null;
            }
        } else if (aba === 'chat') {
            detalhesContent.classList.add('hidden');
            chatContent.classList.remove('hidden');
            tabChat.classList.add('border-blue-600', 'text-blue-600');
            tabChat.classList.remove('border-transparent', 'text-gray-600');
            tabDetalhes.classList.remove('border-blue-600', 'text-blue-600');
            tabDetalhes.classList.add('border-transparent', 'text-gray-600');
            
            // Carregar chat quando abrir a aba
            if (chatSolicitacaoId) {
                carregarInstanciasWhatsApp();
                carregarMensagensChat();
                
                // Não esconder o campo de input aqui - deixar a lógica de carregarMensagensChat decidir
                // O carregarMensagensChat já vai verificar o estado do atendimento e mostrar/esconder corretamente
                iniciarPollingMensagens();
            }
        }
    }

    function carregarInstanciasWhatsApp() {
        if (!chatSolicitacaoId) return;
        
        const url = `<?= url('admin/chat/instancias') ?>?solicitacao_id=${chatSolicitacaoId}`;
        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro ao carregar instâncias: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('Instâncias recebidas:', data);
                if (data.success) {
                    const select = document.getElementById('chatWhatsappInstance');
                    if (!select) {
                        console.error('Select chatWhatsappInstance não encontrado');
                        return;
                    }
                    // Preservar valor atual antes de limpar
                    const instanceIdAtual = select.value;
                    select.innerHTML = '<option value="">Selecione uma instância...</option>';
                    
                    if (data.instancias && data.instancias.length > 0) {
                        data.instancias.forEach(instancia => {
                            const option = document.createElement('option');
                            option.value = instancia.id;
                            let texto = `${instancia.nome} (${instancia.status})`;
                            if (!instancia.disponivel && instancia.id != instanceIdAtual) {
                                texto += ' - EM USO';
                                option.disabled = true;
                                option.classList.add('text-red-500');
                            }
                            option.textContent = texto;
                            // Selecionar a instância atual se existir
                            if (instanceIdAtual && instancia.id == instanceIdAtual) {
                                option.selected = true;
                            } else if (!instanceIdAtual && instancia.is_padrao && instancia.disponivel) {
                                option.selected = true;
                            }
                            select.appendChild(option);
                        });
                    } else {
                        console.warn('Nenhuma instância encontrada');
                        const option = document.createElement('option');
                        option.value = '';
                        option.textContent = 'Nenhuma instância disponível';
                        option.disabled = true;
                        select.appendChild(option);
                    }
                    
                    // Verificar se já existe instância definida na solicitação
                    carregarMensagensChat(); // Isso vai atualizar o select com a instância atual
                } else {
                    console.error('Resposta não foi bem-sucedida:', data);
                }
            })
            .catch(error => {
                console.error('Erro ao carregar instâncias:', error);
                const select = document.getElementById('chatWhatsappInstance');
                if (select) {
                    select.innerHTML = '<option value="">Erro ao carregar instâncias</option>';
                }
            });
    }
    
    function abrirChatDireto() {
        if (!chatSolicitacaoId) {
            alert('Nenhuma solicitação selecionada');
            return;
        }
        
        // Verificar se já existe atendimento ativo
        fetch(`<?= url('admin/chat/') ?>${chatSolicitacaoId}/mensagens`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const atendimentoAtivo = data.solicitacao.chat_atendimento_ativo;
                    const instanceId = data.solicitacao.chat_whatsapp_instance_id;
                    
                    if (atendimentoAtivo && instanceId) {
                        // Já existe atendimento ativo, abrir chat diretamente
                        mostrarAba('chat');
                        carregarInstanciasWhatsApp();
                        carregarMensagensChat();
                    } else {
                        // Não há atendimento ativo, abrir modal para selecionar instância
                        abrirModalSelecionarInstancia();
                    }
                } else {
                    // Se houver erro, abrir modal mesmo assim
                    abrirModalSelecionarInstancia();
                }
            })
            .catch(error => {
                console.error('Erro ao verificar atendimento:', error);
                abrirModalSelecionarInstancia();
            });
    }
    
    function abrirModalSelecionarInstancia() {
        const modal = document.getElementById('modalSelecionarInstancia');
        const content = document.getElementById('instanciasDisponiveisList');
        
        modal.classList.remove('hidden');
        
        // Carregar instâncias disponíveis
        const url = `<?= url('admin/chat/instancias') ?>?solicitacao_id=${chatSolicitacaoId}`;
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.instancias) {
                    // Separar instâncias disponíveis e indisponíveis
                    const instanciasDisponiveis = data.instancias.filter(i => i.disponivel);
                    const instanciasIndisponiveis = data.instancias.filter(i => !i.disponivel);
                    
                    if (instanciasDisponiveis.length === 0 && instanciasIndisponiveis.length === 0) {
                        content.innerHTML = `
                            <div class="text-center py-8">
                                <i class="fas fa-exclamation-triangle text-4xl text-yellow-500 mb-3"></i>
                                <p class="text-gray-700 font-medium mb-2">Nenhuma instância encontrada</p>
                            </div>
                        `;
                    } else {
                        let html = '';
                        
                        // Mostrar instâncias disponíveis primeiro
                        if (instanciasDisponiveis.length > 0) {
                            html += instanciasDisponiveis.map(instancia => `
                                <button onclick="selecionarInstanciaNoModal(${instancia.id})" 
                                        class="w-full text-left p-4 border-2 border-green-200 rounded-lg hover:border-green-500 hover:bg-green-50 transition-colors mb-2">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="font-medium text-gray-900">${instancia.nome}</p>
                                            <p class="text-sm text-gray-500 mt-1">${instancia.instance_name}</p>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="px-2 py-1 text-xs font-medium rounded bg-green-100 text-green-800">
                                                ${instancia.status}
                                            </span>
                                            <i class="fas fa-chevron-right text-green-500"></i>
                                        </div>
                                    </div>
                                </button>
                            `).join('');
                        }
                        
                        // Mostrar instâncias indisponíveis (desabilitadas)
                        if (instanciasIndisponiveis.length > 0) {
                            html += instanciasIndisponiveis.map(instancia => `
                                <div class="w-full text-left p-4 border-2 border-gray-200 rounded-lg bg-gray-50 opacity-60 cursor-not-allowed mb-2">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="font-medium text-gray-500">${instancia.nome}</p>
                                            <p class="text-sm text-gray-400 mt-1">${instancia.instance_name}</p>
                                            <p class="text-xs text-red-600 mt-1 font-medium">⚠️ Em uso em outro atendimento</p>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="px-2 py-1 text-xs font-medium rounded bg-red-100 text-red-800">
                                                ${instancia.status} - EM USO
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            `).join('');
                        }
                        
                        content.innerHTML = html;
                    }
                } else {
                    content.innerHTML = `
                        <div class="text-center py-8">
                            <i class="fas fa-exclamation-circle text-4xl text-red-500 mb-3"></i>
                            <p class="text-gray-700">Erro ao carregar instâncias</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Erro ao carregar instâncias:', error);
                content.innerHTML = `
                    <div class="text-center py-8">
                        <i class="fas fa-exclamation-circle text-4xl text-red-500 mb-3"></i>
                        <p class="text-gray-700">Erro ao carregar instâncias</p>
                    </div>
                `;
            });
    }
    
    function fecharModalInstancia() {
        const modal = document.getElementById('modalSelecionarInstancia');
        modal.classList.add('hidden');
    }
    
    function selecionarInstanciaNoModal(instanceId) {
        if (!chatSolicitacaoId) {
            alert('Nenhuma solicitação selecionada');
            return;
        }
        
        // Iniciar atendimento enviando uma mensagem inicial
        // O backend vai iniciar o atendimento automaticamente quando receber a primeira mensagem
        const formData = new FormData();
        formData.append('mensagem', '👋 Atendimento iniciado');
        formData.append('whatsapp_instance_id', instanceId);
        
        fetch(`<?= url('admin/chat/') ?>${chatSolicitacaoId}/enviar`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                fecharModalInstancia();
                mostrarAba('chat');
                // Aguardar um pouco para garantir que o atendimento foi iniciado
                setTimeout(() => {
                    carregarInstanciasWhatsApp();
                    // Aguardar mais um pouco para garantir que as instâncias foram carregadas
                    setTimeout(() => {
                        carregarMensagensChat();
                        mostrarNotificacao('Atendimento iniciado com sucesso', 'success');
                    }, 300);
                }, 500);
            } else {
                alert('Erro ao iniciar atendimento: ' + (data.message || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao iniciar atendimento');
        });
    }
    
    function abrirModalHistorico() {
        if (!chatSolicitacaoId) {
            alert('Nenhuma solicitação selecionada');
            return;
        }
        
        const modal = document.getElementById('modalHistoricoAtendimentos');
        const content = document.getElementById('historicoAtendimentosContent');
        
        modal.classList.remove('hidden');
        
        // Buscar histórico de atendimentos desta solicitação
        fetch(`<?= url('admin/solicitacoes/') ?>${chatSolicitacaoId}/api`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.solicitacao) {
                    const solicitacao = data.solicitacao;
                    
                    // Buscar todas as mensagens para montar histórico
                    fetch(`<?= url('admin/chat/') ?>${chatSolicitacaoId}/mensagens`)
                        .then(response => response.json())
                        .then(chatData => {
                            if (chatData.success) {
                                const mensagens = chatData.mensagens;
                                
                                if (mensagens.length === 0) {
                                    content.innerHTML = `
                                        <div class="text-center py-8">
                                            <i class="fas fa-inbox text-4xl text-gray-300 mb-3"></i>
                                            <p class="text-gray-600">Nenhuma mensagem no histórico</p>
                                        </div>
                                    `;
                                } else {
                                    // Agrupar mensagens por data
                                    const mensagensPorData = {};
                                    mensagens.forEach(msg => {
                                        const data = new Date(msg.created_at).toLocaleDateString('pt-BR');
                                        if (!mensagensPorData[data]) {
                                            mensagensPorData[data] = [];
                                        }
                                        mensagensPorData[data].push(msg);
                                    });
                                    
                                    content.innerHTML = Object.keys(mensagensPorData).map(data => `
                                        <div class="mb-6">
                                            <h4 class="text-sm font-semibold text-gray-700 mb-3">${data}</h4>
                                            <div class="space-y-2">
                                                ${mensagensPorData[data].map(msg => {
                                                    const isEnviada = msg.tipo === 'ENVIADA';
                                                    const hora = new Date(msg.created_at).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
                                                    return `
                                                        <div class="flex ${isEnviada ? 'justify-end' : 'justify-start'}">
                                                            <div class="max-w-[80%] ${isEnviada ? 'bg-green-100' : 'bg-gray-100'} rounded-lg p-3">
                                                                <p class="text-sm text-gray-800 whitespace-pre-wrap">${escapeHtml(msg.mensagem)}</p>
                                                                <p class="text-xs text-gray-500 mt-1">${hora}</p>
                                                            </div>
                                                        </div>
                                                    `;
                                                }).join('')}
                                            </div>
                                        </div>
                                    `).join('');
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Erro ao carregar mensagens:', error);
                            content.innerHTML = `
                                <div class="text-center py-8">
                                    <i class="fas fa-exclamation-circle text-4xl text-red-500 mb-3"></i>
                                    <p class="text-gray-700">Erro ao carregar histórico</p>
                                </div>
                            `;
                        });
                }
            })
            .catch(error => {
                console.error('Erro ao carregar histórico:', error);
                content.innerHTML = `
                    <div class="text-center py-8">
                        <i class="fas fa-exclamation-circle text-4xl text-red-500 mb-3"></i>
                        <p class="text-gray-700">Erro ao carregar histórico</p>
                    </div>
                `;
            });
    }
    
    function fecharModalHistorico() {
        const modal = document.getElementById('modalHistoricoAtendimentos');
        modal.classList.add('hidden');
    }

    function carregarMensagensChat() {
        if (!chatSolicitacaoId) return;

        fetch(`<?= url('admin/chat/') ?>${chatSolicitacaoId}/mensagens`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderizarMensagens(data.mensagens);
                    atualizarBadgeChat(data.mensagens);
                    // Atualizar badge no card do kanban - marcar como 0 já que o chat foi aberto
                    atualizarBadgeCardEspecifico(chatSolicitacaoId);
                    // Atualizar todos os badges também
                    atualizarBadgesMensagensNaoLidas();
                    
                    const select = document.getElementById('chatWhatsappInstance');
                    const btnEncerrar = document.getElementById('btnEncerrarAtendimento');
                    const infoText = document.getElementById('chatInstanceInfo');
                    
                    // Verificar se já existe instância definida e atendimento ativo
                    const instanceId = data.solicitacao.chat_whatsapp_instance_id;
                    const atendimentoAtivo = data.solicitacao.chat_atendimento_ativo;
                    
                    const chatInputContainer = document.getElementById('chatInputContainer');
                    
                    console.log('🔍 carregarMensagensChat - Debug:', {
                        instanceId: instanceId,
                        atendimentoAtivo: atendimentoAtivo,
                        instanceIdType: typeof instanceId,
                        atendimentoAtivoType: typeof atendimentoAtivo,
                        instanceIdValue: instanceId,
                        atendimentoAtivoValue: atendimentoAtivo,
                        hasInstanceId: !!instanceId,
                        hasAtendimentoAtivo: atendimentoAtivo === true || atendimentoAtivo === 1 || atendimentoAtivo === '1'
                    });
                    
                    // Verificar se atendimento está ativo (pode vir como boolean, int ou string)
                    const isAtendimentoAtivo = atendimentoAtivo === true || atendimentoAtivo === 1 || atendimentoAtivo === '1' || atendimentoAtivo === 'true';
                    
                    if (instanceId && isAtendimentoAtivo) {
                        // Instância já definida e atendimento ativo - bloquear mudança
                        console.log('✅ Atendimento ATIVO - Mostrando campo e botão encerrar');
                        select.value = instanceId;
                        select.disabled = true;
                        select.classList.add('bg-gray-100', 'cursor-not-allowed');
                        
                        // Mostrar botão encerrar
                        if (btnEncerrar) {
                            btnEncerrar.classList.remove('hidden');
                            console.log('✅ Botão encerrar REMOVIDO hidden');
                        } else {
                            console.error('❌ btnEncerrar não encontrado!');
                        }
                        
                        infoText.textContent = 'Instância bloqueada para este atendimento. Encerre o atendimento para usar outra instância.';
                        infoText.classList.add('text-yellow-600');
                        infoText.classList.remove('text-orange-600');
                        
                        // Mostrar campo de input e botão enviar
                        if (chatInputContainer) {
                            chatInputContainer.classList.remove('hidden');
                            console.log('✅ Campo de input REMOVIDO hidden');
                        } else {
                            console.error('❌ chatInputContainer não encontrado!');
                        }
                        
                        // Buscar nome da instância para exibir
                        carregarInstanciasWhatsApp();
                    } else if (instanceId && !isAtendimentoAtivo) {
                        // Instância definida mas atendimento encerrado
                        console.log('⚠️ Atendimento ENCERRADO');
                        select.value = instanceId;
                        select.disabled = true;
                        select.classList.add('bg-gray-100', 'cursor-not-allowed');
                        
                        // Esconder botão encerrar
                        if (btnEncerrar) {
                            btnEncerrar.classList.add('hidden');
                        }
                        
                        infoText.textContent = 'Atendimento encerrado. Clique no botão WhatsApp para iniciar um novo atendimento.';
                        infoText.classList.remove('text-yellow-600');
                        infoText.classList.add('text-orange-600');
                        
                        // Esconder campo de input
                        if (chatInputContainer) {
                            chatInputContainer.classList.add('hidden');
                        }
                    } else {
                        // Nenhuma instância definida
                        console.log('❌ Nenhuma instância definida');
                        select.disabled = true;
                        select.classList.add('bg-gray-100', 'cursor-not-allowed');
                        
                        // Esconder botão encerrar
                        if (btnEncerrar) {
                            btnEncerrar.classList.add('hidden');
                        }
                        
                        infoText.textContent = 'Clique no botão WhatsApp para selecionar uma instância e iniciar o atendimento.';
                        infoText.classList.remove('text-yellow-600', 'text-orange-600');
                        
                        // Esconder campo de input
                        if (chatInputContainer) {
                            chatInputContainer.classList.add('hidden');
                        }
                    }
                    
                    // Verificar novamente se atendimento está ativo (pode vir como boolean, int ou string)
                    const isAtendimentoAtivoFinal = atendimentoAtivo === true || atendimentoAtivo === 1 || atendimentoAtivo === '1' || atendimentoAtivo === 'true';
                    
                    // Se não houver mensagens
                    if (data.mensagens.length === 0) {
                        document.getElementById('chatMessages').classList.add('hidden');
                        
                        // Se tiver instância selecionada e atendimento ativo, mostrar estado vazio mas com input
                        if (instanceId && isAtendimentoAtivoFinal) {
                            document.getElementById('chatEmptyState').classList.remove('hidden');
                            document.getElementById('chatEmptyState').innerHTML = `
                                <div class="text-center">
                                    <i class="fab fa-whatsapp text-6xl mb-4 text-gray-300"></i>
                                    <p class="text-gray-600 mb-2">Nenhuma mensagem ainda</p>
                                    <p class="text-sm text-gray-500">Digite uma mensagem abaixo para iniciar a conversa</p>
                                </div>
                            `;
                            // Campo de input já está visível pela lógica acima
                        } else {
                            // Se não tiver instância ou atendimento encerrado, mostrar mensagem para selecionar
                            document.getElementById('chatEmptyState').classList.remove('hidden');
                            document.getElementById('chatEmptyState').innerHTML = `
                                <div class="text-center">
                                    <i class="fab fa-whatsapp text-6xl mb-4 text-gray-300"></i>
                                    <p class="text-gray-600 mb-2">Nenhuma conversa iniciada</p>
                                    <p class="text-sm text-gray-500">Selecione uma instância WhatsApp acima para começar a conversar</p>
                                </div>
                            `;
                        }
                    } else {
                        // Se houver mensagens, mostrar normalmente
                        document.getElementById('chatMessages').classList.remove('hidden');
                        document.getElementById('chatEmptyState').classList.add('hidden');
                        // Campo de input já está visível pela lógica acima se atendimento ativo
                    }
                }
            })
            .catch(error => {
                console.error('Erro ao carregar mensagens:', error);
            });
    }
    
    // Adicionar evento para encerrar atendimento
    document.addEventListener('DOMContentLoaded', function() {
        const btnEncerrar = document.getElementById('btnEncerrarAtendimento');
        if (btnEncerrar) {
            btnEncerrar.addEventListener('click', function() {
                if (!chatSolicitacaoId) return;
                
                if (!confirm('Tem certeza que deseja encerrar o atendimento? A instância será liberada para uso em outros chamados.')) {
                    return;
                }
                
                fetch(`<?= url('admin/chat/') ?>${chatSolicitacaoId}/encerrar`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        mostrarNotificacao('Atendimento encerrado com sucesso. A instância foi liberada.', 'success');
                        // Recarregar mensagens para atualizar o estado
                        carregarMensagensChat();
                        // O select já estará desabilitado, mas vamos garantir que está correto
                        const select = document.getElementById('chatWhatsappInstance');
                        if (select) {
                            select.disabled = true;
                            select.classList.add('bg-gray-100', 'cursor-not-allowed');
                        }
                    } else {
                        mostrarNotificacao(data.message || 'Erro ao encerrar atendimento', 'error');
                    }
                })
                .catch(error => {
                    console.error('Erro ao encerrar atendimento:', error);
                    mostrarNotificacao('Erro ao encerrar atendimento', 'error');
                });
            });
        }
    });

    function renderizarMensagens(mensagens) {
        const container = document.getElementById('chatMessages');
        
        if (mensagens.length === 0) {
            return;
        }

        // Verificar se o usuário está próximo do final antes de atualizar
        const estavaNoFinal = container.scrollHeight - container.scrollTop <= container.clientHeight + 100;

        container.innerHTML = mensagens.map(msg => {
            const isEnviada = msg.tipo === 'ENVIADA';
            const dataHora = new Date(msg.created_at).toLocaleString('pt-BR');
            // Para mensagens enviadas, usar status. Para recebidas, usar is_lida
            const statusIcon = isEnviada 
                ? (msg.status === 'LIDA' ? '✓✓' : msg.status === 'ENTREGUE' ? '✓' : '')
                : (msg.is_lida ? '✓✓' : '');
            
            return `
                <div class="flex ${isEnviada ? 'justify-end' : 'justify-start'}">
                    <div class="max-w-[70%] ${isEnviada ? 'bg-green-100' : 'bg-gray-100'} rounded-lg p-3">
                        <p class="text-sm text-gray-800 whitespace-pre-wrap">${escapeHtml(msg.mensagem)}</p>
                        <div class="flex items-center justify-end mt-1 text-xs text-gray-500">
                            <span>${dataHora}</span>
                            ${isEnviada ? `<span class="ml-2">${statusIcon}</span>` : ''}
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        // Só fazer scroll automático se o usuário estava no final ou se for a primeira renderização
        if (estavaNoFinal || container.scrollTop === 0) {
            // Usar setTimeout para garantir que o DOM foi atualizado
            setTimeout(() => {
                container.scrollTop = container.scrollHeight;
            }, 0);
        }
    }

    function atualizarBadgeChat(mensagens) {
        // Após abrir o chat, todas as mensagens foram marcadas como lidas
        // Então sempre esconder o badge quando o chat está aberto
        const badge = document.getElementById('chatBadge');
        const badgeHeader = document.getElementById('chatBadgeHeader');
        
        if (badge) badge.classList.add('hidden');
        if (badgeHeader) badgeHeader.classList.add('hidden');
    }
    
    // Função para atualizar ícone do WhatsApp nos cards
    function atualizarIconeWhatsApp(solicitacaoId, hasInstance, isActive, instanceName) {
        const card = document.querySelector(`.kanban-card[data-solicitacao-id="${solicitacaoId}"]`);
        if (!card) return;
        
        // Buscar ou criar o ícone do WhatsApp
        const header = card.querySelector('.flex.items-center.gap-2.mb-1');
        if (!header) return;
        
        let icon = header.querySelector('.fab.fa-whatsapp');
        
        if (hasInstance && isActive) {
            // Mostrar ícone
            if (!icon) {
                icon = document.createElement('i');
                icon.className = 'fab fa-whatsapp text-green-500 text-sm';
                icon.title = `WhatsApp ativo: ${instanceName || 'Instância ativa'}`;
                // Inserir após o badge de mensagens não lidas
                const badge = header.querySelector(`.chat-badge-${solicitacaoId}`);
                if (badge && badge.nextSibling) {
                    header.insertBefore(icon, badge.nextSibling);
                } else {
                    header.appendChild(icon);
                }
            } else {
                icon.classList.remove('hidden');
                icon.title = `WhatsApp ativo: ${instanceName || 'Instância ativa'}`;
            }
        } else {
            // Esconder ícone
            if (icon) {
                icon.classList.add('hidden');
            }
        }
    }
    
    // Função para atualizar ícones do WhatsApp de todos os cards
    function atualizarIconesWhatsApp() {
        const cards = document.querySelectorAll('.kanban-card[data-solicitacao-id]');
        const solicitacaoIds = Array.from(cards).map(card => card.getAttribute('data-solicitacao-id')).filter(Boolean);
        
        if (solicitacaoIds.length === 0) {
            return;
        }
        
        fetch(`<?= url('admin/chat/whatsapp-status') ?>?solicitacao_ids=${solicitacaoIds.join(',')}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.status) {
                    solicitacaoIds.forEach(solicitacaoId => {
                        const status = data.status[solicitacaoId];
                        if (status) {
                            atualizarIconeWhatsApp(
                                solicitacaoId,
                                status.has_instance,
                                status.is_active,
                                status.instance_name
                            );
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Erro ao buscar status do WhatsApp:', error);
            });
    }

    function handleChatInputKeydown(event) {
        // Se for Enter sem Shift, envia a mensagem
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            enviarMensagemChat();
        }
        // Se for Shift+Enter, permite o comportamento padrão (quebra de linha)
        // Não fazemos nada, deixando o navegador processar normalmente
    }

    function enviarMensagemChat() {
        if (!chatSolicitacaoId) return;

        const input = document.getElementById('chatMessageInput');
        const mensagem = input.value.trim();
        const instanceId = document.getElementById('chatWhatsappInstance').value;

        if (!mensagem) {
            alert('Digite uma mensagem');
            return;
        }

        if (!instanceId) {
            alert('Selecione uma instância WhatsApp');
            return;
        }

        const formData = new FormData();
        formData.append('mensagem', mensagem);
        formData.append('whatsapp_instance_id', instanceId);

        fetch(`<?= url('admin/chat/') ?>${chatSolicitacaoId}/enviar`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                input.value = '';
                input.style.height = 'auto';
                input.style.height = '42px';
                carregarMensagensChat();
                // Atualizar badge do card imediatamente após enviar mensagem
                // (pode haver uma resposta não lida)
                setTimeout(() => {
                    atualizarBadgeCardEspecifico(chatSolicitacaoId);
                }, 1000);
            } else {
                alert('Erro ao enviar mensagem: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao enviar mensagem');
        });
    }

    function iniciarPollingMensagens() {
        if (chatPollingInterval) {
            clearInterval(chatPollingInterval);
        }
        
        // Atualizar mensagens a cada 5 segundos
        chatPollingInterval = setInterval(() => {
            if (chatSolicitacaoId && !document.getElementById('chatContent').classList.contains('hidden')) {
                carregarMensagensChat();
            }
        }, 5000);
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Atualizar chatSolicitacaoId quando abrir detalhes
    const abrirDetalhesOriginal = window.abrirDetalhes;
    window.abrirDetalhes = function(solicitacaoId) {
        chatSolicitacaoId = solicitacaoId;
        // Atualizar badge do card específico quando clicado
        atualizarBadgeCardEspecifico(solicitacaoId);
        if (abrirDetalhesOriginal) {
            abrirDetalhesOriginal(solicitacaoId);
        }
    };
    
    // Função para atualizar badge de um card específico
    function atualizarBadgeCardEspecifico(solicitacaoId) {
        if (!solicitacaoId) return;
        
        fetch(`<?= url('admin/chat/mensagens-nao-lidas') ?>?solicitacao_ids=${solicitacaoId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.contagens) {
                    const count = data.contagens[solicitacaoId] || 0;
                    const badge = document.querySelector(`.chat-badge-${solicitacaoId}`);
                    const card = document.getElementById(`kanban-card-${solicitacaoId}`);
                    
                    if (badge) {
                        if (count > 0) {
                            badge.textContent = count > 99 ? '99+' : count;
                            badge.classList.remove('hidden');
                        } else {
                            badge.classList.add('hidden');
                        }
                    }
                    
                    // Atualizar borda do card
                    if (card) {
                        if (count > 0) {
                            card.classList.add('kanban-card-mensagem-nao-lida');
                        } else {
                            card.classList.remove('kanban-card-mensagem-nao-lida');
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Erro ao atualizar badge do card:', error);
            });
    }
    
    // Função para atualizar badges de mensagens não lidas nos cards do kanban
    function atualizarBadgesMensagensNaoLidas() {
        // Coletar todos os IDs de solicitações visíveis no kanban
        const cards = document.querySelectorAll('.kanban-card[data-solicitacao-id]');
        const solicitacaoIds = Array.from(cards).map(card => card.getAttribute('data-solicitacao-id')).filter(Boolean);
        
        if (solicitacaoIds.length === 0) {
            return;
        }
        
        // Buscar contagens de mensagens não lidas
        fetch(`<?= url('admin/chat/mensagens-nao-lidas') ?>?solicitacao_ids=${solicitacaoIds.join(',')}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.contagens) {
                    // Atualizar badges e bordas em cada card
                    solicitacaoIds.forEach(solicitacaoId => {
                        const count = data.contagens[solicitacaoId] || 0;
                        const badge = document.querySelector(`.chat-badge-${solicitacaoId}`);
                        const card = document.getElementById(`kanban-card-${solicitacaoId}`);
                        
                        if (badge) {
                            if (count > 0) {
                                badge.textContent = count > 99 ? '99+' : count;
                                badge.classList.remove('hidden');
                            } else {
                                badge.classList.add('hidden');
                            }
                        }
                        
                        // Atualizar borda do card
                        if (card) {
                            if (count > 0) {
                                card.classList.add('kanban-card-mensagem-nao-lida');
                            } else {
                                card.classList.remove('kanban-card-mensagem-nao-lida');
                            }
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Erro ao buscar mensagens não lidas:', error);
            });
    }
    
    // Atualizar badges e ícones do WhatsApp periodicamente (a cada 3 segundos para ser mais responsivo)
    setInterval(() => {
        atualizarBadgesMensagensNaoLidas();
        atualizarIconesWhatsApp();
    }, 3000);
    
    // Atualizar badges e ícones quando a página carregar
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                atualizarBadgesMensagensNaoLidas();
                atualizarIconesWhatsApp();
            }, 2000);
        });
    } else {
        setTimeout(() => {
            atualizarBadgesMensagensNaoLidas();
            atualizarIconesWhatsApp();
        }, 2000);
    }
    
    // Função para carregar contagem de histórico de utilização
    function carregarContagemHistorico(solicitacaoId, numeroContrato, categoriaId) {
        let url = `<?= url('admin/solicitacoes/historico-utilizacao') ?>?numero_contrato=${encodeURIComponent(numeroContrato)}`;
        if (categoriaId) {
            url += `&categoria_id=${categoriaId}`;
        }
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const badge = document.getElementById(`badgeHistorico-${solicitacaoId}`);
                    const button = document.getElementById(`btnHistorico-${solicitacaoId}`);
                    if (badge) {
                        const total = data.total || 0;
                        badge.textContent = total;
                        badge.title = `${total} solicitação${total !== 1 ? 'ões' : ''} no período de 12 meses`;
                        if (button) {
                            button.title = `Ver histórico: ${total} solicitação${total !== 1 ? 'ões' : ''} no período de 12 meses`;
                        }
                    }
                } else {
                    const badge = document.getElementById(`badgeHistorico-${solicitacaoId}`);
                    if (badge) {
                        badge.textContent = '?';
                        badge.title = 'Erro ao carregar';
                    }
                }
            })
            .catch(error => {
                console.error('Erro ao carregar contagem de histórico:', error);
                const badge = document.getElementById(`badgeHistorico-${solicitacaoId}`);
                if (badge) {
                    badge.textContent = '?';
                    badge.title = 'Erro ao carregar';
                }
            });
    }
    
    // Função para abrir relatório com filtro de contrato e categoria
    function abrirHistoricoUtilizacao(solicitacaoId, numeroContrato, categoriaId) {
        // Calcular data de 12 meses atrás
        const dataFim = new Date();
        const dataInicio = new Date();
        dataInicio.setMonth(dataInicio.getMonth() - 12);
        
        const dataInicioStr = dataInicio.toISOString().split('T')[0];
        const dataFimStr = dataFim.toISOString().split('T')[0];
        
        // Abrir relatório em nova aba com filtros
        let url = `<?= url('admin/relatorios') ?>?numero_contrato=${encodeURIComponent(numeroContrato)}&data_inicio=${dataInicioStr}&data_fim=${dataFimStr}`;
        if (categoriaId) {
            url += `&categoria_id=${categoriaId}`;
        }
        window.open(url, '_blank');
    }
</script>


