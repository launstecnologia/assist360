<?php
$title = $title ?? 'Confirmar Horário';
ob_start();
?>

<div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <!-- Header -->
            <div class="text-center mb-6">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                    <i class="fas fa-calendar-check text-green-600 text-2xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Confirmar Horário</h2>
                <p class="text-gray-600 mt-2">Confirme seu horário de atendimento</p>
            </div>

            <!-- Informações da Solicitação -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <h3 class="font-semibold text-gray-900 mb-3">Informações do Agendamento</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Nº de Atendimento:</span>
                        <span class="font-semibold text-gray-900"><?= htmlspecialchars($tokenData['protocol'] ?? $solicitacao['numero_solicitacao'] ?? 'N/A') ?></span>
                    </div>
                </div>
            </div>

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
                    <?php if ($solicitacao['imovel_cep']): ?> - CEP: <?= htmlspecialchars($solicitacao['imovel_cep']) ?><?php endif; ?>
                </p>
            </div>
            <?php endif; ?>

            <!-- Formulário de Confirmação -->
            <form method="POST" action="<?= url('confirmacao-horario?token=' . urlencode($token)) ?>" class="space-y-4">
                <?php 
                // Garantir que horariosDisponiveis sempre seja um array
                $horariosDisponiveis = $horariosDisponiveis ?? [];
                
                // Debug: Log na view para verificar se os horários estão chegando
                error_log("DEBUG confirmacao.php - horariosDisponiveis count: " . count($horariosDisponiveis));
                error_log("DEBUG confirmacao.php - horariosDisponiveis: " . json_encode($horariosDisponiveis));
                
                if (!empty($horariosDisponiveis)): ?>
                    <!-- Sempre mostrar horários para seleção, mesmo que seja apenas um -->
                    <div class="mb-6">
                        <h3 class="font-semibold text-gray-900 mb-3">
                            <?= count($horariosDisponiveis) > 1 ? 'Selecione o horário desejado:' : 'Horário disponível:' ?>
                        </h3>
                        <div class="space-y-3">
                            <?php foreach ($horariosDisponiveis as $index => $horario): ?>
                                <?php 
                                // Debug: Log de cada horário
                                error_log("DEBUG confirmacao.php - Horário [{$index}]: " . json_encode($horario));
                                ?>
                                <label class="flex items-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-green-500 hover:bg-green-50 transition-colors <?= $index === 0 ? 'border-green-500 bg-green-50' : '' ?>">
                                    <input type="radio" 
                                           name="horario_selecionado" 
                                           value="<?= htmlspecialchars(json_encode($horario)) ?>"
                                           class="mr-3 h-4 w-4 text-green-600 focus:ring-green-500"
                                           <?= $index === 0 ? 'checked' : '' ?>
                                           required>
                                    <div class="flex-1">
                                        <div class="flex items-center text-gray-900 font-medium">
                                            <i class="fas fa-clock mr-2 text-green-600"></i>
                                            <span><?= htmlspecialchars($horario['raw'] ?? '') ?></span>
                                        </div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Mensagem quando não há horários disponíveis -->
                    <div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <p class="text-sm text-yellow-800">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Nenhum horário disponível para seleção. Por favor, entre em contato conosco.
                        </p>
                    </div>
                <?php endif; ?>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <p class="text-sm text-blue-800">
                        <i class="fas fa-info-circle mr-2"></i>
                        Ao confirmar, você estará concordando com o horário agendado. Certifique-se de estar disponível no local durante o período informado.
                    </p>
                </div>

                <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    Confirmar Horário
                </button>
            </form>

            <!-- Opções Adicionais -->
            <div class="mt-4 space-y-2">
                <a href="<?= url('reagendamento-horario?token=' . urlencode($token)) ?>" 
                   class="w-full flex items-center justify-center gap-2 bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-3 px-4 rounded-lg transition-colors duration-200">
                    <i class="fas fa-calendar-alt mr-2"></i>
                    Reagendar Horário
                </a>
                
                <a href="<?= url('cancelamento-horario?token=' . urlencode($token)) ?>" 
                   class="w-full flex items-center justify-center gap-2 bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-4 rounded-lg transition-colors duration-200">
                    <i class="fas fa-times-circle mr-2"></i>
                    Cancelar Solicitação
                </a>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center text-sm text-gray-500">
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

