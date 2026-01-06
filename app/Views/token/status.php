<?php
$title = $title ?? 'Status do Serviço';
ob_start();
?>

<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <!-- Header -->
            <div class="text-center mb-6">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-blue-100 mb-4">
                    <i class="fas fa-info-circle text-blue-600 text-2xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Status do Serviço</h2>
                <p class="text-gray-600 mt-2">Acompanhe o andamento da sua solicitação</p>
            </div>

            <!-- Status Atual -->
            <div class="bg-gray-50 rounded-lg p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-900">Status Atual</h3>
                    <?php if ($solicitacao['status_cor']): ?>
                    <span class="px-3 py-1 rounded-full text-sm font-semibold" style="background-color: <?= htmlspecialchars($solicitacao['status_cor']) ?>20; color: <?= htmlspecialchars($solicitacao['status_cor']) ?>">
                        <?= htmlspecialchars($solicitacao['status_nome'] ?? 'Pendente') ?>
                    </span>
                    <?php else: ?>
                    <span class="px-3 py-1 bg-gray-200 text-gray-800 rounded-full text-sm font-semibold">
                        <?= htmlspecialchars($solicitacao['status_nome'] ?? 'Pendente') ?>
                    </span>
                    <?php endif; ?>
                </div>

                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Nº de Atendimento:</span>
                        <span class="font-semibold text-gray-900"><?= htmlspecialchars($solicitacao['numero_solicitacao'] ?? 'N/A') ?></span>
                    </div>
                    <?php if ($solicitacao['data_agendamento']): ?>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Data Agendada:</span>
                        <span class="font-semibold text-gray-900"><?= date('d/m/Y', strtotime($solicitacao['data_agendamento'])) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($solicitacao['horario_agendamento']): ?>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Horário:</span>
                        <span class="font-semibold text-gray-900"><?= htmlspecialchars($solicitacao['horario_agendamento']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Histórico -->
            <?php if (!empty($historico)): ?>
            <div class="mb-6">
                <h3 class="font-semibold text-gray-900 mb-4">Histórico de Atualizações</h3>
                <div class="space-y-3">
                    <?php foreach ($historico as $item): ?>
                    <div class="border-l-4 border-blue-500 pl-4 py-2">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-semibold text-gray-900"><?= htmlspecialchars($item['status_nome'] ?? 'Atualização') ?></p>
                                <?php if ($item['observacoes']): ?>
                                <p class="text-sm text-gray-600 mt-1"><?= htmlspecialchars($item['observacoes']) ?></p>
                                <?php endif; ?>
                            </div>
                            <span class="text-xs text-gray-500 whitespace-nowrap ml-4">
                                <?= date('d/m/Y H:i', strtotime($item['created_at'])) ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Endereço -->
            <?php if ($solicitacao['imovel_endereco']): ?>
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <h3 class="font-semibold text-gray-900 mb-2">Endereço do Atendimento</h3>
                <p class="text-sm text-gray-700">
                    <?= htmlspecialchars($solicitacao['imovel_endereco']) ?>
                    <?php if ($solicitacao['imovel_numero']): ?>, nº <?= htmlspecialchars($solicitacao['imovel_numero']) ?><?php endif; ?>
                    <?php if ($solicitacao['imovel_complemento']): ?>, <?= htmlspecialchars($solicitacao['imovel_complemento']) ?><?php endif; ?>
                    <br>
                    <?php if ($solicitacao['imovel_bairro']): ?><?= htmlspecialchars($solicitacao['imovel_bairro']) ?>, <?php endif; ?>
                    <?php if ($solicitacao['imovel_cidade']): ?><?= htmlspecialchars($solicitacao['imovel_cidade']) ?><?php endif; ?>
                    <?php if ($solicitacao['imovel_estado']): ?>/<?= htmlspecialchars($solicitacao['imovel_estado']) ?><?php endif; ?>
                </p>
            </div>
            <?php endif; ?>

            <!-- Ações -->
            <div class="flex gap-3">
                <?php if ($solicitacao['data_agendamento'] && !$solicitacao['horario_confirmado']): ?>
                <a href="<?= url('confirmacao-horario?token=' . urlencode($token)) ?>" class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-lg transition-colors duration-200 text-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    Confirmar Horário
                </a>
                <?php endif; ?>
                <a href="<?= url('cancelamento-horario?token=' . urlencode($token)) ?>" class="flex-1 bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-4 rounded-lg transition-colors duration-200 text-center">
                    <i class="fas fa-times-circle mr-2"></i>
                    Cancelar Horário
                </a>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center text-sm text-gray-500 mt-6">
            <div class="flex items-center justify-center mb-2">
                <?= kss_logo('', 'KSS ASSISTÊNCIA 360°', 24) ?>
            </div>
            <p>KSS Seguros - Assistência 360°</p>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'app/Views/layouts/public.php';
?>

