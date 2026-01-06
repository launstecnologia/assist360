<?php
/**
 * View: Dashboard do Locatário
 */
$title = 'Dashboard - Assistência 360°';
$currentPage = 'locatario-dashboard';
ob_start();
?>

<!-- Welcome Banner -->
<div class="locatario-gradient rounded-lg p-8 mb-8 text-white text-center">
    <div>
        <h1 class="text-3xl font-bold mb-2">
            Olá, <?= htmlspecialchars(explode(' ', $locatario['nome'])[0]) ?>.
        </h1>
        <p class="text-lg opacity-90 mb-6">
            Bem-vindo ao seu portal de assistência.
        </p>
    </div>
    
    <div class="flex justify-center">
        <?php 
        $acessoPermitido = $locatario['acesso_permitido'] ?? true; // Por padrão, permite acesso (compatibilidade)
        if ($acessoPermitido): ?>
            <a href="<?= url($locatario['instancia'] . '/nova-solicitacao') ?>" 
               class="inline-flex items-center px-6 py-3 bg-white text-green-600 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                <i class="fas fa-plus mr-2"></i>
                Nova Solicitação
            </a>
        <?php else: ?>
            <div class="inline-flex items-center px-6 py-3 bg-gray-200 text-gray-500 font-medium rounded-lg cursor-not-allowed" 
                 title="<?= htmlspecialchars($locatario['motivo_bloqueio'] ?? 'Acesso bloqueado') ?>">
                <i class="fas fa-lock mr-2"></i>
                Nova Solicitação Indisponível
            </div>
        <?php endif; ?>
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


<!-- Recent Solicitations -->
<div class="bg-white rounded-lg shadow-sm">
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-medium text-gray-900">
                <i class="fas fa-list mr-2"></i>
                Suas Solicitações
            </h2>
            <a href="<?= url($locatario['instancia'] . '/solicitacoes') ?>" 
               class="text-sm text-blue-600 hover:text-blue-500">
                Ver todas
            </a>
        </div>
    </div>
    
    <div class="p-6">
        <?php if (empty($solicitacoes)): ?>
            <div class="text-center py-12">
                <i class="fas fa-inbox text-4xl text-gray-300 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Nenhuma solicitação encontrada</h3>
                <p class="text-gray-500 mb-6">Você ainda não possui solicitações de assistência.</p>
                <?php 
                $acessoPermitido = $locatario['acesso_permitido'] ?? true;
                if ($acessoPermitido): ?>
                    <a href="<?= url($locatario['instancia'] . '/nova-solicitacao') ?>" 
                       class="inline-flex items-center px-4 py-2 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-plus mr-2"></i>
                        Criar Primeira Solicitação
                    </a>
                <?php else: ?>
                    <div class="inline-flex items-center px-4 py-2 bg-gray-300 text-gray-500 font-medium rounded-lg cursor-not-allowed" 
                         title="<?= htmlspecialchars($locatario['motivo_bloqueio'] ?? 'Acesso bloqueado') ?>">
                        <i class="fas fa-lock mr-2"></i>
                        Nova Solicitação Indisponível
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach (array_slice($solicitacoes, 0, 5) as $solicitacao): 
                    $numeroSolicitacao = $solicitacao['numero_solicitacao'] ?? ('KSS' . $solicitacao['id']);
                    $protocolo = $solicitacao['protocolo_seguradora'] ?? '-';
                    
                    // Formatar data criada com horário em português
                    $dataCriada = date('d/m/Y \à\s H:i', strtotime($solicitacao['created_at']));
                    
                    // Formatar data de última interação (updated_at)
                    $dataUltimaInteracao = !empty($solicitacao['updated_at']) ? date('d/m/Y \à\s H:i', strtotime($solicitacao['updated_at'])) : '-';
                    
                    // Formatar data agendada com horário (priorizar horário escolhido pelo locatário)
                    $dataAgendada = 'Aguardando confirmação do prestador';
                    $dataConcluido = '';
                    
                    // Prioridade 1: horario_confirmado_raw (horário escolhido pelo locatário)
                    if (!empty($solicitacao['horario_confirmado_raw'])) {
                        // Formato esperado: "dd/mm/yyyy - HH:MM às HH:MM" ou "dd/mm/yyyy - HH:MM-HH:MM"
                        if (preg_match('/(\d{2}\/\d{2}\/\d{4})\s*-\s*(\d{2}:\d{2})\s*(?:às|-)\s*(\d{2}:\d{2})/', $solicitacao['horario_confirmado_raw'], $match)) {
                            $dataAgendada = $match[1] . ' das ' . $match[2] . ' às ' . $match[3];
                        } elseif (preg_match('/(\d{2}\/\d{2}\/\d{4})\s*-\s*(\d{2}:\d{2})/', $solicitacao['horario_confirmado_raw'], $match)) {
                            $dataAgendada = $match[1] . ' às ' . $match[2];
                        } else {
                            $dataAgendada = $solicitacao['horario_confirmado_raw'];
                        }
                    }
                    // Prioridade 2: confirmed_schedules
                    elseif (!empty($solicitacao['confirmed_schedules'])) {
                        $confirmed = is_string($solicitacao['confirmed_schedules']) 
                            ? json_decode($solicitacao['confirmed_schedules'], true) 
                            : $solicitacao['confirmed_schedules'];
                        if (is_array($confirmed) && !empty($confirmed)) {
                            $primeiro = $confirmed[0];
                            if (!empty($primeiro['raw'])) {
                                if (preg_match('/(\d{2}\/\d{2}\/\d{4})\s*-\s*(\d{2}:\d{2})-(\d{2}:\d{2})/', $primeiro['raw'], $match)) {
                                    $dataAgendada = $match[1] . ' das ' . $match[2] . ' às ' . $match[3];
                                } elseif (preg_match('/(\d{2}\/\d{2}\/\d{4})\s*-\s*(\d{2}:\d{2})/', $primeiro['raw'], $match)) {
                                    $dataAgendada = $match[1] . ' às ' . $match[2];
                                } else {
                                    $dataAgendada = $primeiro['raw'];
                                }
                            }
                        }
                    }
                    // Prioridade 3: data_agendamento e horario_agendamento
                    elseif (!empty($solicitacao['data_agendamento']) && !empty($solicitacao['horario_agendamento'])) {
                        $dataAg = new \DateTime($solicitacao['data_agendamento']);
                        $dataFormatada = $dataAg->format('d/m/Y');
                        $horaFormatada = substr($solicitacao['horario_agendamento'], 0, 5); // HH:MM
                        $dataAgendada = $dataFormatada . ' às ' . $horaFormatada;
                    }
                    
                    // Data de conclusão (se status for Concluído)
                    if (stripos($solicitacao['status_nome'] ?? '', 'Concluído') !== false || stripos($solicitacao['status_nome'] ?? '', 'Concluido') !== false) {
                        if (!empty($solicitacao['updated_at'])) {
                            $dataConcluido = date('d/m/Y', strtotime($solicitacao['updated_at']));
                        }
                    }
                ?>
                    <a href="<?= url($locatario['instancia'] . '/solicitacoes/' . $solicitacao['id']) ?>" 
                       class="block border border-gray-200 rounded-lg p-3 hover:bg-gray-50 hover:border-green-300 transition-all cursor-pointer">
                        <div class="mb-2">
                            <!-- Nº Solicitação e Contrato -->
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-semibold text-gray-900 text-sm"><?= htmlspecialchars($numeroSolicitacao) ?></span>
                                <?php if (!empty($solicitacao['numero_contrato'])): ?>
                                    <span class="text-xs text-gray-500">Contrato: <?= htmlspecialchars($solicitacao['numero_contrato']) ?></span>
                                <?php endif; ?>
                            </div>
                            <!-- Datas abaixo do contrato e número -->
                            <div class="text-xs text-gray-500 space-y-0.5">
                                <div>Data de registro: <span class="text-gray-700"><?= $dataCriada ?></span></div>
                                <div>Data da última interação: <span class="text-gray-700"><?= $dataUltimaInteracao ?></span></div>
                            </div>
                        </div>
                        <div class="text-xs space-y-1">
                            <!-- Nº Protocolo -->
                            <div>
                                <span class="text-gray-500">Nº Protocolo:</span>
                                <span class="font-semibold text-gray-900 ml-1"><?= htmlspecialchars($protocolo) ?></span>
                            </div>
                            
                            <!-- Categoria -->
                            <div>
                                <span class="text-gray-500">Categoria:</span>
                                <span class="font-medium text-gray-900 ml-1"><?= htmlspecialchars($solicitacao['categoria_nome'] ?? '-') ?></span>
                            </div>
                            
                            <!-- Data Agendada -->
                            <div>
                                <span class="text-gray-500">Data Agendada:</span>
                                <span class="font-medium ml-1 <?= $dataAgendada === 'Aguardando confirmação do prestador' ? 'text-red-600' : 'text-gray-900' ?>"><?= $dataAgendada ?></span>
                            </div>
                            
                            <!-- Status - Data Concluído -->
                            <div class="flex items-center gap-2">
                                <div class="flex-1">
                                    <span class="text-gray-500">Status:</span>
                                    <span class="status-badge status-<?= strtolower(str_replace([' ', '(', ')'], ['-', '', ''], $solicitacao['status_nome'])) ?> ml-1 text-xs">
                                        <?= htmlspecialchars($solicitacao['status_nome']) ?>
                                    </span>
                                </div>
                                <?php if (!empty($dataConcluido)): ?>
                                <div class="flex-1">
                                    <span class="text-gray-500">(<?= $dataConcluido ?>)</span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <?php if (count($solicitacoes) > 5): ?>
                <div class="mt-6 text-center">
                    <a href="<?= url($locatario['instancia'] . '/solicitacoes') ?>" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium text-gray-700 bg-white rounded-lg hover:bg-gray-50 transition-colors">
                        <i class="fas fa-list mr-2"></i>
                        Ver Todas as Solicitações (<?= count($solicitacoes) ?>)
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Seus Dados -->
<div class="mt-8 bg-white rounded-lg shadow-sm p-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-semibold text-gray-900 flex items-center">
            <i class="fas fa-user mr-2"></i>
            Seus Dados
        </h2>
        <a href="<?= url($locatario['instancia'] . '/perfil') ?>" 
           class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center">
            <i class="fas fa-edit mr-2"></i>
            Editar
        </a>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Nome -->
        <div>
            <h3 class="text-sm font-medium text-gray-500 mb-2 flex items-center">
                <i class="fas fa-user mr-2"></i>
                Nome Completo
            </h3>
            <p class="text-gray-900 font-medium"><?= htmlspecialchars($locatario['nome']) ?></p>
        </div>
        
        <!-- WhatsApp -->
        <div>
            <h3 class="text-sm font-medium text-gray-500 mb-2 flex items-center">
                <i class="fab fa-whatsapp mr-2 text-green-500"></i>
                WhatsApp
            </h3>
            <p class="text-gray-900 font-medium">
                <?= !empty($locatario['whatsapp']) ? htmlspecialchars($locatario['whatsapp']) : 'Não cadastrado' ?>
            </p>
            <?php if (empty($locatario['whatsapp'])): ?>
            <p class="text-xs text-gray-500 mt-1">Usado para enviar notificações importantes sobre suas solicitações</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Seus Imóveis e Status da Conta -->
<div class="mt-8 space-y-6">
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-2">
            <i class="fas fa-home mr-2 text-blue-600"></i>
            Seus Imóveis
        </h3>
        <p class="text-sm text-gray-500 mb-4">Imóveis vinculados ao seu contrato</p>
        
        <?php if (!empty($locatario['imoveis'])): ?>
            <?php foreach ($locatario['imoveis'] as $imovel): ?>
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-home mr-2 text-gray-600"></i>
                                <span class="text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($imovel['endereco']) ?>, <?= htmlspecialchars($imovel['numero']) ?>
                                </span>
                            </div>
                            <div class="text-sm text-gray-600 mb-1">
                                <?= htmlspecialchars($imovel['bairro']) ?>, <?= htmlspecialchars($imovel['cidade']) ?> - <?= htmlspecialchars($imovel['uf']) ?>
                            </div>
                            <div class="text-sm text-gray-600">
                                CEP: <?= htmlspecialchars($imovel['cep']) ?>
                            </div>
                        </div>
                        <div class="text-right">
                            <?php if (!empty($imovel['contratos'])): ?>
                                <?php 
                                $contratoAtivo = null;
                                foreach ($imovel['contratos'] as $contrato) {
                                    if ($contrato['CtrStatus'] !== 'RESCINDIDO') {
                                        $contratoAtivo = $contrato;
                                        break;
                                    }
                                }
                                if (!$contratoAtivo && !empty($imovel['contratos'])) {
                                    $contratoAtivo = $imovel['contratos'][0]; // Pega o primeiro se não houver ativo
                                }
                                ?>
                                <?php if ($contratoAtivo): ?>
                                    <div class="text-xs text-gray-500 mb-1">
                                        Contrato: <?= htmlspecialchars($contratoAtivo['CtrCod']) ?>-<?= htmlspecialchars($contratoAtivo['CtrDV']) ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            <div class="text-xs text-gray-400">
                                Cód: <?= htmlspecialchars($imovel['codigo']) ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-home text-3xl mb-2"></i>
                <p>Nenhum imóvel encontrado</p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Status da Conta -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">
            <i class="fas fa-user-check mr-2 text-green-600"></i>
            Status da Conta
        </h3>
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                    <i class="fas fa-check-circle mr-1"></i>
                    Ativo
                </span>
            </div>
            <div class="text-right text-sm text-gray-500">
                <div>Última sincronização: <?= date('d/m/Y') ?></div>
                <div>Dados sincronizados em: <?= date('d/m/Y, H:i:s') ?></div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'app/Views/layouts/locatario.php';
?>
