<?php
/**
 * View: Detalhes da Solicitação
 */
$title = 'Solicitação #' . ($solicitacao['numero_solicitacao'] ?? $solicitacao['id']);
$currentPage = 'solicitacoes';
$pageTitle = 'Detalhes da Solicitação';
ob_start();

// Helper para valores seguros
function safe($value, $default = 'Não informado') {
    return !empty($value) ? htmlspecialchars($value) : $default;
}
?>

<style>
.section-card {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.section-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.timeline-item {
    position: relative;
    padding-left: 2rem;
    padding-bottom: 1.5rem;
}
.timeline-item:last-child {
    padding-bottom: 0;
}
.timeline-dot {
    position: absolute;
    left: 0;
    top: 0;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #3B82F6;
    border: 3px solid white;
    box-shadow: 0 0 0 2px #3B82F6;
}
.timeline-line {
    position: absolute;
    left: 5px;
    top: 12px;
    bottom: -1.5rem;
    width: 2px;
    background: #E5E7EB;
}
</style>

<!-- Header -->
<div class="bg-gray-800 -mx-6 -mt-6 px-6 py-4 mb-6 rounded-t-lg">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-white">Detalhes da Solicitação</h1>
        <div class="flex gap-2">
            <button onclick="copiarInformacoes()" class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600 text-sm">
                <i class="fas fa-copy mr-2"></i>
                Copiar Informações
            </button>
            <a href="<?= url('admin/solicitacoes') ?>" class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600">
                <i class="fas fa-times"></i>
            </a>
        </div>
    </div>
</div>

<!-- Info Box -->
<div class="bg-gray-50 p-4 rounded-lg mb-6 flex items-start justify-between">
    <div class="flex-1">
        <div class="flex items-center gap-3 mb-2">
            <h2 class="text-xl font-bold text-gray-900">
                <?= safe($solicitacao['numero_solicitacao'] ?? '#'.$solicitacao['id'], '#'.$solicitacao['id']) ?>
            </h2>
            <?php if (!empty($solicitacao['numero_contrato'])): ?>
                <span class="text-sm text-gray-500">
                    Contrato: <?= htmlspecialchars($solicitacao['numero_contrato']) ?>
                </span>
            <?php endif; ?>
            <span class="px-3 py-1 rounded-full text-sm font-medium" 
                  style="background-color: <?= $solicitacao['status_cor'] ?? '#3B82F6' ?>20; color: <?= $solicitacao['status_cor'] ?? '#3B82F6' ?>">
                <?= safe($solicitacao['status_nome'], 'Sem status') ?>
            </span>
        </div>
        <p class="text-lg font-medium text-gray-700">
            <?= safe($solicitacao['categoria_nome'], 'Sem categoria') ?>
            <?php 
            // Usar helper para buscar todas as subcategorias
            $subcategoriasHeader = getSubcategorias($solicitacao);
            $temMultiplasHeader = count($subcategoriasHeader) > 1;
            if ($temMultiplasHeader): ?>
            - <?= count($subcategoriasHeader) ?> serviços
            <?php elseif (!empty($subcategoriasHeader)): ?>
            - <?= htmlspecialchars($subcategoriasHeader[0]) ?>
            <?php elseif (!empty($solicitacao['subcategoria_nome'])): ?>
            - <?= safe($solicitacao['subcategoria_nome']) ?>
            <?php endif; ?>
        </p>
    </div>
    <div class="text-right text-sm text-gray-500">
        <i class="fas fa-calendar mr-1"></i>
        <?= date('d/m/Y', strtotime($solicitacao['created_at'])) ?>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Coluna Principal (2/3) -->
    <div class="lg:col-span-2 space-y-4">
        
        <!-- Informações do Cliente -->
        <div class="section-card">
            <div class="section-title">
                <i class="fas fa-user text-blue-600"></i>
                Informações do Cliente
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Nome</label>
                    <p class="text-sm font-medium text-gray-900"><?= safe($solicitacao['locatario_nome']) ?></p>
                </div>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">CPF</label>
                    <p class="text-sm font-medium text-gray-900"><?= safe($solicitacao['locatario_cpf']) ?></p>
                </div>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Telefone</label>
                    <p class="text-sm font-medium text-gray-900">
                        <?php if (!empty($solicitacao['locatario_telefone'])): ?>
                        <a href="https://wa.me/55<?= preg_replace('/[^0-9]/', '', $solicitacao['locatario_telefone']) ?>" 
                           target="_blank" class="text-green-600 hover:text-green-800">
                            <i class="fab fa-whatsapp mr-1"></i>
                            <?= safe($solicitacao['locatario_telefone']) ?>
                        </a>
                        <?php else: ?>
                        Não informado
                        <?php endif; ?>
                    </p>
                </div>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Imobiliária</label>
                    <p class="text-sm font-medium text-gray-900"><?= safe($solicitacao['imobiliaria_nome']) ?></p>
                </div>
            </div>
        </div>

        <!-- Descrição do Problema -->
        <div class="section-card">
            <div class="section-title">
                <i class="fas fa-clipboard-list text-blue-600"></i>
                Descrição do Problema
            </div>
            
            <?php 
            // Verificar se há horário confirmado
            $temHorarioConfirmado = !empty($solicitacao['data_agendamento']) && !empty($solicitacao['horario_agendamento']);
            ?>
            
            <div class="<?= $temHorarioConfirmado ? 'bg-green-50 border-2 border-green-500' : 'bg-gray-50 border border-gray-200' ?> p-4 rounded transition-all">
                <?php if ($temHorarioConfirmado): ?>
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-check-circle text-green-600 text-lg"></i>
                            <span class="text-xs text-green-700 font-semibold">
                                <i class="fas fa-calendar-check mr-1"></i>Serviço Agendado - Descrição Confirmada
                            </span>
                        </div>
                    </div>
                <?php endif; ?>
                
                <p class="text-sm <?= $temHorarioConfirmado ? 'text-green-900' : 'text-gray-900' ?> whitespace-pre-wrap">
                    <?= safe($solicitacao['descricao_problema'], 'Nenhuma descrição fornecida.') ?>
                </p>
            </div>
        </div>

        <!-- Disponibilidade Informada pelo Locatário -->
        <?php 
        // Usar helper para buscar horários do locatário
        $horariosOpcoes = getHorariosLocatario($solicitacao);
        if (!empty($horariosOpcoes)): 
        ?>
        <div class="section-card">
            <div class="section-title">
                <i class="fas fa-clock text-blue-600"></i>
                <div>
                    <div class="font-semibold">Disponibilidade Informada pelo Locatário</div>
                    <div class="text-xs font-normal text-gray-500 mt-0.5">Horários da Solicitação Inicial</div>
                </div>
            </div>
            
            <div class="space-y-3" id="lista-horarios">
                <?php foreach ($horariosOpcoes as $index => $horario): 
                    // Normalizar horário para formato padrão
                    $horarioFormatado = normalizarHorario(is_string($horario) ? $horario : (string)$horario);
                    
                    // Se não conseguiu normalizar, usar o original
                    if ($horarioFormatado === null) {
                        $horarioFormatado = is_string($horario) ? $horario : '';
                                }
                                
                    // Verificar se está confirmado usando helper
                    $horarioConfirmado = isHorarioConfirmado($horarioFormatado, $solicitacao);
                        
                    // Formatar para exibição (converter hífen para "às")
                    $horarioExibicao = formatarHorarioParaExibicao($horarioFormatado);
                ?>
                <div class="<?= $horarioConfirmado ? 'bg-green-50 border-2 border-green-500' : 'bg-blue-50 border border-blue-200' ?> rounded-lg p-4 flex items-center justify-between transition-all">
                    <div class="flex items-center gap-3">
                        <?php if ($horarioConfirmado): ?>
                            <i class="fas fa-check-circle text-green-600 text-lg"></i>
                        <?php else: ?>
                            <i class="fas fa-clock text-blue-600"></i>
                        <?php endif; ?>
                        <div>
                            <span class="text-sm font-medium <?= $horarioConfirmado ? 'text-green-900' : '' ?>">
                                <?= htmlspecialchars($horarioExibicao) ?>
                            </span>
                            <?php if ($horarioConfirmado): ?>
                                <span class="block text-xs text-green-700 font-semibold mt-1">
                                    <i class="fas fa-calendar-check mr-1"></i>Horário Confirmado
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($horarioConfirmado): 
                        // Preparar horário para desconfirmação (usar formato normalizado)
                        $horarioEscapadoDesconfirmar = htmlspecialchars($horarioFormatado, ENT_QUOTES, 'UTF-8');
                    ?>
                        <button onclick="desconfirmarHorario(<?= $solicitacao['id'] ?>, '<?= $horarioEscapadoDesconfirmar ?>')" class="px-3 py-1.5 bg-red-600 text-white text-xs rounded hover:bg-red-700">
                            <i class="fas fa-times mr-1"></i>Desconfirmar
                        </button>
                    <?php else: 
                        // Preparar horário para JavaScript (usar formato normalizado)
                        $horarioEscapado = htmlspecialchars($horarioFormatado, ENT_QUOTES, 'UTF-8');
                    ?>
                        <button onclick="confirmarHorario(<?= $solicitacao['id'] ?>, '<?= $horarioEscapado ?>')" class="px-3 py-1.5 bg-green-600 text-white text-xs rounded hover:bg-green-700">
                            <i class="fas fa-check mr-1"></i>Confirmar horário
                        </button>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            
            
            <!-- Checkbox: Horários Indisponíveis -->
            <div class="mt-4 border-t pt-4">
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" id="horarios-indisponiveis" 
                           <?= ($solicitacao['horarios_indisponiveis'] ?? 0) ? 'checked' : '' ?>
                           onchange="console.log('🔍 Checkbox alterado:', this.checked); toggleAdicionarHorariosSeguradora(<?= $solicitacao['id'] ?>);"
                           class="w-4 h-4 text-blue-600 rounded">
                    <span class="ml-2 text-sm text-gray-700">Nenhum horário está disponível</span>
                </label>
            </div>
            
            <!-- Seção: Adicionar Horários da Seguradora (aparece quando checkbox está marcado) -->
            <div id="secao-adicionar-horarios-seguradora" class="mt-4" style="<?= ($solicitacao['horarios_indisponiveis'] ?? 0) ? 'display: block;' : 'display: none;' ?>">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="text-sm font-semibold text-blue-900 mb-2">
                        <i class="fas fa-clock mr-2"></i>Adicionar Horário da Seguradora
                    </h3>
                    <p class="text-xs text-blue-700 mb-4">
                        Adicione horários alternativos que a seguradora pode oferecer
                    </p>
                    
                    <!-- Formulário para adicionar horário -->
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Data</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-calendar-alt text-gray-400"></i>
                                </div>
                                <input type="date" id="data-seguradora" 
                                       class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
                                       min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                                       max="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-2">Horário</label>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                <label class="relative cursor-pointer">
                                    <input type="radio" name="horario-seguradora" value="08:00-11:00" class="sr-only">
                                    <div class="border-2 border-gray-200 rounded-lg p-2 text-center hover:border-blue-300 transition-colors horario-seguradora-card">
                                        <div class="text-xs font-medium text-gray-900">08h00 às 11h00</div>
                                    </div>
                                </label>
                                
                                <label class="relative cursor-pointer">
                                    <input type="radio" name="horario-seguradora" value="11:00-14:00" class="sr-only">
                                    <div class="border-2 border-gray-200 rounded-lg p-2 text-center hover:border-blue-300 transition-colors horario-seguradora-card">
                                        <div class="text-xs font-medium text-gray-900">11h00 às 14h00</div>
                                    </div>
                                </label>
                                
                                <label class="relative cursor-pointer">
                                    <input type="radio" name="horario-seguradora" value="14:00-17:00" class="sr-only">
                                    <div class="border-2 border-gray-200 rounded-lg p-2 text-center hover:border-blue-300 transition-colors horario-seguradora-card">
                                        <div class="text-xs font-medium text-gray-900">14h00 às 17h00</div>
                                    </div>
                                </label>
                                
                                <label class="relative cursor-pointer">
                                    <input type="radio" name="horario-seguradora" value="17:00-20:00" class="sr-only">
                                    <div class="border-2 border-gray-200 rounded-lg p-2 text-center hover:border-blue-300 transition-colors horario-seguradora-card">
                                        <div class="text-xs font-medium text-gray-900">17h00 às 20h00</div>
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <button type="button" onclick="adicionarHorarioSeguradora(<?= $solicitacao['id'] ?>)" 
                                class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium">
                            <i class="fas fa-plus mr-2"></i>Salvar Horário
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Disponibilidade Informada pela Seguradora -->
        <?php 
        // Buscar horários sugeridos pela seguradora (armazenados em horarios_opcoes quando horarios_indisponiveis = 1)
        $horariosSeguradora = [];
        if (!empty($solicitacao['horarios_indisponiveis']) && !empty($solicitacao['horarios_opcoes'])) {
            $horariosSeguradora = json_decode($solicitacao['horarios_opcoes'], true) ?? [];
            if (!is_array($horariosSeguradora)) {
                $horariosSeguradora = [];
            }
        }
        if (!empty($horariosSeguradora)): 
        ?>
        <div class="section-card">
            <div class="section-title">
                <i class="fas fa-building text-blue-600"></i>
                Disponibilidade Informada pela Seguradora
            </div>
            
            <div class="space-y-3" id="lista-horarios-seguradora">
                <?php foreach ($horariosSeguradora as $index => $horario): 
                    // Formatar horário para exibição
                    $horarioFormatado = is_string($horario) ? $horario : '';
                    
                    // Se já está no formato "dd/mm/yyyy - HH:00-HH:00", usar diretamente
                    if (preg_match('/^\d{2}\/\d{2}\/\d{4}\s*-\s*\d{2}:\d{2}-\d{2}:\d{2}$/', $horarioFormatado)) {
                        // Já está formatado corretamente
                    } elseif (preg_match('/(\d{4}-\d{2}-\d{2})[T ](\d{2}):(\d{2})/', $horarioFormatado, $matches)) {
                        // Formato ISO: converter para "dd/mm/yyyy - HH:00-HH:00"
                        try {
                            $dt = new \DateTime($matches[1] . ' ' . $matches[2] . ':' . $matches[3]);
                            $horaInicio = (int)$matches[2];
                            $horaFim = $horaInicio + 3;
                            $horarioFormatado = $dt->format('d/m/Y') . ' - ' . sprintf('%02d:00', $horaInicio) . '-' . sprintf('%02d:00', $horaFim);
                        } catch (\Exception $e) {
                            // Manter formato original se houver erro
                        }
                    } elseif (preg_match('/(\d{2})\/(\d{2})\/(\d{4})[ -](\d{2}):(\d{2})/', $horarioFormatado, $matches)) {
                        // Formato parcial: completar
                        $horaInicio = (int)$matches[4];
                        $horaFim = $horaInicio + 3;
                        $horarioFormatado = $matches[1] . '/' . $matches[2] . '/' . $matches[3] . ' - ' . sprintf('%02d:00', $horaInicio) . '-' . sprintf('%02d:00', $horaFim);
                    }
                    
                    // Escapar para JavaScript
                    $horarioEscapado = htmlspecialchars($horario, ENT_QUOTES, 'UTF-8');
                ?>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-clock text-blue-600"></i>
                        <span class="text-sm font-medium text-blue-900">
                            <?= htmlspecialchars($horarioFormatado) ?>
                        </span>
                    </div>
                    <button onclick="removerHorarioSeguradora(<?= $solicitacao['id'] ?>, '<?= $horarioEscapado ?>')" 
                            class="text-red-600 hover:text-red-800">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Serviço -->
        <div class="section-card">
            <div class="section-title">
                <i class="fas fa-tools text-blue-600"></i>
                Informações do Serviço
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Categoria</label>
                    <p class="text-sm font-medium text-gray-900"><?= safe($solicitacao['categoria_nome']) ?></p>
                </div>
                <div>
                    <?php 
                    // Usar helper para buscar todas as subcategorias
                    $subcategorias = getSubcategorias($solicitacao);
                    $temMultiplas = count($subcategorias) > 1;
                    
                    // Debug temporário (remover depois)
                    if (defined('DEBUG') && DEBUG) {
                        error_log("DEBUG show.php - Subcategorias encontradas: " . json_encode($subcategorias));
                        error_log("DEBUG show.php - Observações: " . substr($solicitacao['observacoes'] ?? '', 0, 500));
                    }
                    ?>
                    <label class="text-xs text-gray-500 block mb-1"><?= $temMultiplas ? 'Serviços' : 'Subcategoria' ?></label>
                    <?php if ($temMultiplas): ?>
                        <ul class="list-disc list-inside space-y-1 text-sm font-medium text-gray-900">
                            <?php foreach ($subcategorias as $nome): ?>
                                <li><?= htmlspecialchars($nome) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php elseif (!empty($subcategorias) && count($subcategorias) > 0): ?>
                        <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($subcategorias[0]) ?></p>
                    <?php else: ?>
                        <p class="text-sm font-medium text-gray-900"><?= safe($solicitacao['subcategoria_nome'] ?? 'Não informado') ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Prioridade</label>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                        <?= ($solicitacao['prioridade'] ?? 'NORMAL') == 'ALTA' ? 'bg-red-100 text-red-800' : 
                           (($solicitacao['prioridade'] ?? 'NORMAL') == 'MEDIA' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') ?>">
                        <?= safe($solicitacao['prioridade'], 'NORMAL') ?>
                    </span>
                </div>
                <?php 
                $tipoQualificacao = $solicitacao['tipo_qualificacao'] ?? null;
                $observacaoQualificacao = $solicitacao['observacao_qualificacao'] ?? null;
                if ($tipoQualificacao === 'BOLSAO' || (!empty($solicitacao['validacao_bolsao']) && $solicitacao['validacao_bolsao'] == 1)):
                ?>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Validação</label>
                    <div class="flex items-start gap-2 flex-wrap">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 border border-blue-200" title="CPF validado na listagem da imobiliária">
                        <i class="fas fa-check-circle mr-1"></i>
                        Bolsão
                        </span>
                    </div>
                </div>
                <?php elseif ($tipoQualificacao === 'CORTESIA'): ?>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Validação</label>
                    <div class="flex items-start gap-2 flex-wrap">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                            <i class="fas fa-gift mr-1"></i>
                            Cortesia
                        </span>
                        <?php if (!empty($observacaoQualificacao)): ?>
                            <div class="flex-1 min-w-[200px] text-xs text-gray-700 bg-green-50 border-l-3 border-green-400 p-2 rounded-r">
                                <strong class="text-green-900">OBS:</strong> <?= htmlspecialchars($observacaoQualificacao) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php elseif ($tipoQualificacao === 'REGRA_2'): ?>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Validação</label>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 border border-purple-200">
                        <i class="fas fa-calendar-check mr-1"></i>
                        Dt Assinatura
                    </span>
                </div>
                <?php elseif ($tipoQualificacao === 'NAO_QUALIFICADA'): ?>
                <div>
                    <label class="text-xs text-gray-500 block mb-1">Validação</label>
                    <div class="flex items-start gap-2 flex-wrap">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-200">
                            <i class="fas fa-times-circle mr-1"></i>
                            Recusado
                        </span>
                        <?php if (!empty($observacaoQualificacao)): ?>
                            <div class="flex-1 min-w-[200px] text-xs text-gray-700 bg-red-50 border-l-3 border-red-400 p-2 rounded-r">
                                <strong class="text-red-900">OBS:</strong> <?= htmlspecialchars($observacaoQualificacao) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Agendamento -->
        <?php if (!empty($solicitacao['data_agendamento'])): ?>
        <div class="section-card">
            <div class="section-title">
                <i class="fas fa-calendar-check text-blue-600"></i>
                Disponibilidade Informada
            </div>
            <div class="bg-gray-50 p-3 rounded border border-gray-200 flex items-center">
                <i class="fas fa-clock text-gray-400 mr-3"></i>
                <span class="text-sm font-medium">
                    <?= date('d/m/Y', strtotime($solicitacao['data_agendamento'])) ?>
                    <?php if (!empty($solicitacao['horario_agendamento'])): ?>
                    - <?= safe($solicitacao['horario_agendamento']) ?>
                    <?php endif; ?>
                </span>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Coluna Lateral (1/3) -->
    <div class="space-y-4">
        
        <!-- Endereço -->
        <div class="section-card">
            <div class="section-title">
                <i class="fas fa-map-marker-alt text-blue-600"></i>
                Endereço
            </div>
            <div class="text-sm text-gray-900">
                <p class="font-medium">
                    <?= safe($solicitacao['imovel_endereco']) ?>, <?= safe($solicitacao['imovel_numero'], 's/n') ?>
                </p>
                <?php if (!empty($solicitacao['imovel_complemento'])): ?>
                <p class="text-gray-600"><?= safe($solicitacao['imovel_complemento']) ?></p>
                <?php endif; ?>
                <p class="text-gray-600 mt-1">
                    <?= safe($solicitacao['imovel_bairro'], '') ?><br>
                    <?= safe($solicitacao['imovel_cidade'], '') ?><?= !empty($solicitacao['imovel_estado']) ? '/' . safe($solicitacao['imovel_estado']) : '' ?>
                    <?php if (!empty($solicitacao['imovel_cep'])): ?>
                    <br>CEP: <?= safe($solicitacao['imovel_cep']) ?>
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <!-- Motivo do Cancelamento (se cancelado) -->
        <?php if (!empty($solicitacao['status_nome']) && stripos($solicitacao['status_nome'], 'Cancelado') !== false && !empty($solicitacao['motivo_cancelamento'])): ?>
        <div class="section-card bg-red-50 border border-red-200">
            <div class="section-title text-red-700">
                <i class="fas fa-times-circle"></i>
                Motivo do Cancelamento
            </div>
            <p class="text-sm text-red-900"><?= htmlspecialchars($solicitacao['motivo_cancelamento']) ?></p>
        </div>
        <?php endif; ?>
        
        <!-- Observações da Seguradora -->
        <div class="section-card">
            <div class="section-title">
                <i class="fas fa-sticky-note text-blue-600"></i>
                Observações da Seguradora
            </div>
            <form method="POST" action="<?= url("admin/solicitacoes/{$solicitacao['id']}/observacoes") ?>">
                <textarea name="observacoes" rows="4" 
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
                          placeholder="Adicione observações da seguradora..."><?= safe($solicitacao['observacoes'] ?? '', '') ?></textarea>
                <button type="submit" class="mt-2 w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">
                    <i class="fas fa-save mr-2"></i>
                    Salvar Observações
                </button>
            </form>
        </div>

        <!-- Status -->
        <div class="section-card">
            <div class="section-title">
                <i class="fas fa-exchange-alt text-blue-600"></i>
                Status da Solicitação
            </div>
            <button onclick="abrirModalStatus()" 
                    class="w-full px-4 py-2 border-2 border-dashed border-gray-300 rounded-lg hover:border-blue-500 hover:bg-blue-50 text-sm text-gray-600 hover:text-blue-600">
                <i class="fas fa-edit mr-2"></i>
                Alterar Status
            </button>
        </div>

        <!-- Prestador -->
        <?php if (!empty($solicitacao['prestador_nome'])): ?>
        <div class="section-card">
            <div class="section-title">
                <i class="fas fa-user-tie text-blue-600"></i>
                Prestador de Serviço
            </div>
            <div class="text-sm">
                <p class="font-medium text-gray-900"><?= safe($solicitacao['prestador_nome']) ?></p>
                <?php if (!empty($solicitacao['prestador_telefone'])): ?>
                <p class="text-gray-600 mt-1">
                    <i class="fas fa-phone mr-1"></i>
                    <?= safe($solicitacao['prestador_telefone']) ?>
                </p>
                <?php endif; ?>
                <?php if (!empty($solicitacao['valor_orcamento']) && $solicitacao['valor_orcamento'] > 0): ?>
                <p class="text-green-600 font-semibold mt-2">
                    R$ <?= number_format($solicitacao['valor_orcamento'], 2, ',', '.') ?>
                </p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Timeline -->
        <div class="section-card">
            <div class="section-title">
                <i class="fas fa-history text-blue-600"></i>
                Linha do Tempo
            </div>
            <?php if (!empty($historico)): ?>
            <div class="space-y-0 max-h-96 overflow-y-auto pr-2">
                <?php foreach ($historico as $index => $item): ?>
                <div class="timeline-item">
                    <?php if ($index < count($historico) - 1): ?>
                    <div class="timeline-line"></div>
                    <?php endif; ?>
                    <div class="timeline-dot" style="background-color: <?= $item['status_cor'] ?? '#3B82F6' ?>; box-shadow: 0 0 0 2px <?= $item['status_cor'] ?? '#3B82F6' ?>;"></div>
                    <div>
                        <p class="text-sm font-medium text-gray-900"><?= safe($item['status_nome'] ?? '', 'Status') ?></p>
                        <?php if (!empty($item['observacao'])): ?>
                        <p class="text-xs text-gray-500 mt-1"><?= safe($item['observacao']) ?></p>
                        <?php endif; ?>
                        <p class="text-xs text-gray-400 mt-1">
                            <?= date('d/m/Y, H:i', strtotime($item['created_at'])) ?>
                        </p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-sm text-gray-500">Nenhum histórico disponível</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Status -->
<div id="modalStatus" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-8 border w-96 shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-semibold text-gray-900">Alterar Status</h3>
            <button onclick="fecharModalStatus()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form onsubmit="salvarStatus(event)">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Novo Status</label>
                <select id="novoStatus" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500">
                    <option value="">Selecione um status</option>
                    <?php foreach ($statusDisponiveis as $status): ?>
                    <option value="<?= $status['id'] ?>" <?= $status['id'] == $solicitacao['status_id'] ? 'disabled' : '' ?>>
                        <?= safe($status['nome'] ?? '') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Observação (opcional)</label>
                <textarea id="observacaoStatus" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"
                          placeholder="Adicione uma observação..."></textarea>
            </div>
            
            <div class="flex gap-3">
                <button type="button" onclick="fecharModalStatus()"
                        class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit"
                        class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Salvar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalStatus() {
    document.getElementById('modalStatus').classList.remove('hidden');
}

function fecharModalStatus() {
    document.getElementById('modalStatus').classList.add('hidden');
}

function salvarStatus(event) {
    event.preventDefault();
    
    const statusId = document.getElementById('novoStatus').value;
    const observacao = document.getElementById('observacaoStatus').value;
    
    if (!statusId) {
        alert('Por favor, selecione um status');
        return;
    }
    
    fetch(`<?= url("admin/solicitacoes/{$solicitacao['id']}/status") ?>`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            status_id: statusId,
            observacao: observacao
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
}

function copiarInformacoes() {
    // Montar informações completas do locatário para enviar ao prestador
    <?php
    // Formatar data de criação
    $dataCriacao = date('d/m/Y', strtotime($solicitacao['created_at']));
    $horaCriacao = date('H:i', strtotime($solicitacao['created_at']));
    $dataCriacaoFormatada = $dataCriacao . ' às ' . $horaCriacao;
    
    // Formatar endereço
    $endereco = [];
    if (!empty($solicitacao['imovel_endereco'])) {
        $endereco[] = $solicitacao['imovel_endereco'];
        if (!empty($solicitacao['imovel_numero'])) {
            $endereco[] = $solicitacao['imovel_numero'];
        }
    }
    $enderecoCompleto = implode(', ', $endereco);
    
    // Formatar localização (Bairro/Cidade/Estado)
    $localizacao = [];
    if (!empty($solicitacao['imovel_bairro'])) {
        $localizacao[] = $solicitacao['imovel_bairro'];
    }
    if (!empty($solicitacao['imovel_cidade'])) {
        $localizacao[] = $solicitacao['imovel_cidade'];
        if (!empty($solicitacao['imovel_estado'])) {
            $localizacao[] = $solicitacao['imovel_estado'];
        }
    }
    $localizacaoCompleta = implode(' - ', $localizacao);
    
    // Buscar horários informados pelo locatário
    $horariosLocatario = [];
    if (!empty($solicitacao['horarios_indisponiveis'])) {
        $horariosLocatario = !empty($solicitacao['datas_opcoes']) 
            ? json_decode($solicitacao['datas_opcoes'], true) : [];
    } else {
        $horariosLocatario = !empty($solicitacao['horarios_opcoes']) 
            ? json_decode($solicitacao['horarios_opcoes'], true) : [];
    }
    $horariosTexto = '';
    if (!empty($horariosLocatario) && is_array($horariosLocatario)) {
        $horariosTexto = implode("\n", array_filter($horariosLocatario));
    }
    ?>
    const info = `═══════════════════════════════════════

📋 INFORMAÇÕES DA SOLICITAÇÃO

═══════════════════════════════════════



🔢 Número da Solicitação: <?= $solicitacao['numero_solicitacao'] ?? 'KS'.$solicitacao['id'] ?>

📊 Status: <?= $solicitacao['status_nome'] ?? 'Não informado' ?>

📅 Data de Criação: <?= $dataCriacaoFormatada ?>



═══════════════════════════════════════

👤 DADOS DO LOCATÁRIO

═══════════════════════════════════════



Nome: <?= $solicitacao['locatario_nome'] ?? 'Não informado' ?>

<?php if (!empty($solicitacao['locatario_cpf'])): ?>
CPF: <?= $solicitacao['locatario_cpf'] ?>

<?php endif; ?>
<?php if (!empty($solicitacao['locatario_telefone'])): ?>
Telefone: <?= $solicitacao['locatario_telefone'] ?>

<?php endif; ?>
Nº do Contrato: <?= !empty($solicitacao['numero_contrato']) ? $solicitacao['numero_contrato'] : '' ?>

<?php if (!empty($solicitacao['imobiliaria_nome'])): ?>
Imobiliária: <?= $solicitacao['imobiliaria_nome'] ?>

<?php endif; ?>



<?php if (!empty($horariosTexto)): ?>
═══════════════════════════════════════

📅 Data Informada pelo Locatário

═══════════════════════════════════════
<?= $horariosTexto ?>

═══════════════════════════════════════

<?php endif; ?>
📍 ENDEREÇO DO IMÓVEL

═══════════════════════════════════════



<?php if (!empty($enderecoCompleto)): ?>
Endereço: <?= $enderecoCompleto ?>

<?php endif; ?>
<?php if (!empty($localizacaoCompleta)): ?>
Bairro/Cidade/Estado: <?= $localizacaoCompleta ?>

<?php endif; ?>
<?php if (!empty($solicitacao['imovel_cep'])): ?>
CEP: <?= $solicitacao['imovel_cep'] ?>

<?php endif; ?>



═══════════════════════════════════════

📝 DESCRIÇÃO DO PROBLEMA

═══════════════════════════════════════



<?= !empty($solicitacao['descricao_problema']) ? $solicitacao['descricao_problema'] : 'Nenhuma descrição fornecida.' ?>`.trim();
    
    navigator.clipboard.writeText(info).then(() => {
        alert('✅ Informações copiadas para a área de transferência!');
    }).catch(err => {
        console.error('Erro ao copiar:', err);
        alert('Erro ao copiar informações. Por favor, tente novamente.');
    });
}

document.getElementById('modalStatus')?.addEventListener('click', function(e) {
    if (e.target === this) fecharModalStatus();
});

function confirmarHorario(solicitacaoId, horario) {
    // ✅ Solicitar protocolo da seguradora antes de confirmar
    const protocoloSeguradora = prompt('Para confirmar o horário, é necessário informar o protocolo da seguradora:\n\nDigite o protocolo:');
    
    if (!protocoloSeguradora || protocoloSeguradora.trim() === '') {
        alert('O protocolo da seguradora é obrigatório para confirmar o horário.');
        return;
    }
    
    if (!confirm('Confirmar este horário? O status será alterado para "Serviço Agendado".')) {
        return;
    }
    
    fetch(`<?= url('admin/solicitacoes/') ?>${solicitacaoId}/confirmar-horario`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ 
            horario: horario,
            protocolo_seguradora: protocoloSeguradora.trim()
        })
    })
    .then(async response => {
        // ✅ Verificar se a resposta é JSON válido antes de parsear
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Resposta não é JSON:', text);
            throw new Error('Resposta do servidor não é JSON válido. Verifique o console para mais detalhes.');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert('Horário confirmado com sucesso!');
            location.reload();
        } else {
            alert('Erro: ' + (data.error || 'Não foi possível confirmar'));
        }
    })
    .catch(error => {
        console.error('Erro ao confirmar horário:', error);
        alert('Erro ao confirmar horário: ' + error.message);
    });
}

// (interações extras removidas - confirmação/desconfirmação ocorre por botão de cada item ou via Kanban)

function desconfirmarHorario(solicitacaoId, horario) {
    if (!confirm('Desconfirmar este horário? O agendamento será removido.')) {
        return;
    }
    
    fetch(`<?= url('admin/solicitacoes/') ?>${solicitacaoId}/desconfirmar-horario`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ horario: horario })
    })
    .then(async response => {
        // ✅ Verificar se a resposta é JSON válido antes de parsear
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Resposta não é JSON:', text);
            throw new Error('Resposta do servidor não é JSON válido. Verifique o console para mais detalhes.');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert('Horário desconfirmado com sucesso!');
            location.reload();
        } else {
            alert('Erro: ' + (data.error || 'Não foi possível desconfirmar'));
        }
    })
    .catch(error => {
        console.error('Erro ao desconfirmar horário:', error);
        alert('Erro ao desconfirmar horário: ' + error.message);
    });
}

// Toggle da seção de adicionar horários da seguradora
function toggleAdicionarHorariosSeguradora(solicitacaoId) {
    console.log('🔍 toggleAdicionarHorariosSeguradora chamado, solicitacaoId:', solicitacaoId);
    
    const checkbox = document.getElementById('horarios-indisponiveis');
    const secao = document.getElementById('secao-adicionar-horarios-seguradora');
    
    console.log('🔍 Checkbox encontrado:', checkbox);
    console.log('🔍 Seção encontrada:', secao);
    
    if (!checkbox) {
        console.error('❌ Checkbox não encontrado!');
        return;
    }
    
    if (!secao) {
        console.error('❌ Seção não encontrada!');
        return;
    }
    
    const checked = checkbox.checked;
    console.log('🔍 Checkbox checked:', checked);
    
    if (checked) {
        console.log('🔍 Mostrando seção...');
        secao.classList.remove('hidden');
        secao.style.display = 'block';
        console.log('🔍 Seção display:', secao.style.display);
        console.log('🔍 Seção classes:', secao.className);
        // Atualizar status no banco
        atualizarHorariosIndisponiveis(solicitacaoId, true);
    } else {
        console.log('🔍 Ocultando seção...');
        secao.classList.add('hidden');
        secao.style.display = 'none';
        // Atualizar status no banco
        atualizarHorariosIndisponiveis(solicitacaoId, false);
    }
}

// Inicializar visibilidade da seção ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔍 DOM carregado, verificando checkbox...');
    const checkbox = document.getElementById('horarios-indisponiveis');
    const secao = document.getElementById('secao-adicionar-horarios-seguradora');
    
    if (checkbox && secao) {
        const solicitacaoId = <?= $solicitacao['id'] ?>;
        console.log('🔍 Checkbox inicial:', checkbox.checked);
        console.log('🔍 Seção inicial display:', secao.style.display);
        
        // Ajustar visibilidade inicial baseado no estado do checkbox
        if (checkbox.checked) {
            secao.style.display = 'block';
            secao.classList.remove('hidden');
        } else {
            secao.style.display = 'none';
            secao.classList.add('hidden');
        }
    }
});

// Atualizar status de horários indisponíveis
function atualizarHorariosIndisponiveis(solicitacaoId, indisponivel) {
    fetch(`<?= url('admin/solicitacoes/') ?>${solicitacaoId}/atualizar`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ 
            horarios_indisponiveis: indisponivel ? 1 : 0
        })
    })
    .then(async response => {
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Resposta não é JSON:', text);
            return { success: false, error: 'Resposta inválida' };
        }
        return response.json();
    })
    .then(data => {
        if (!data.success) {
            console.error('Erro ao atualizar:', data.error);
        }
    })
    .catch(error => {
        console.error('Erro ao atualizar horários indisponíveis:', error);
    });
}

// Adicionar horário da seguradora
function adicionarHorarioSeguradora(solicitacaoId) {
    const data = document.getElementById('data-seguradora').value;
    const horarioRadio = document.querySelector('input[name="horario-seguradora"]:checked');
    
    if (!data) {
        alert('Por favor, selecione uma data');
        return;
    }
    
    if (!horarioRadio) {
        alert('Por favor, selecione um horário');
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
    
    fetch(`<?= url('admin/solicitacoes/') ?>${solicitacaoId}/adicionar-horario-seguradora`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ 
            horario: horarioFormatado,
            data: data,
            hora_inicio: horaInicio,
            hora_fim: horaFim
        })
    })
    .then(async response => {
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Resposta não é JSON:', text);
            throw new Error('Resposta inválida');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Limpar formulário
            document.getElementById('data-seguradora').value = '';
            document.querySelectorAll('input[name="horario-seguradora"]').forEach(radio => radio.checked = false);
            // Recarregar página para mostrar novo horário
            location.reload();
        } else {
            alert('Erro: ' + (data.error || 'Não foi possível adicionar horário'));
        }
    })
    .catch(error => {
        console.error('Erro ao adicionar horário:', error);
        alert('Erro ao adicionar horário: ' + error.message);
    });
}

// Remover horário da seguradora
function removerHorarioSeguradora(solicitacaoId, horario) {
    if (!confirm('Deseja remover este horário?')) {
        return;
    }
    
    fetch(`<?= url('admin/solicitacoes/') ?>${solicitacaoId}/remover-horario-seguradora`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ horario: horario })
    })
    .then(async response => {
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Resposta não é JSON:', text);
            throw new Error('Resposta inválida');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erro: ' + (data.error || 'Não foi possível remover horário'));
        }
    })
    .catch(error => {
        console.error('Erro ao remover horário:', error);
        alert('Erro ao remover horário: ' + error.message);
    });
}

// Estilização dos cards de horário da seguradora
document.addEventListener('change', function(e) {
    if (e.target.name === 'horario-seguradora') {
        document.querySelectorAll('.horario-seguradora-card').forEach(card => {
            card.classList.remove('border-blue-500', 'bg-blue-100');
            card.classList.add('border-gray-200');
        });
        
        const selectedCard = e.target.closest('label').querySelector('.horario-seguradora-card');
        if (selectedCard) {
            selectedCard.classList.remove('border-gray-200');
            selectedCard.classList.add('border-blue-500', 'bg-blue-100');
        }
    }
});
</script>

<?php
$content = ob_get_clean();
include 'app/Views/layouts/admin.php';
?>
