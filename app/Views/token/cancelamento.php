<?php
$title = $title ?? 'Cancelar Horário';
ob_start();
?>

<div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <!-- Header -->
            <div class="text-center mb-6">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-calendar-times text-red-600 text-2xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Cancelar Horário</h2>
                <p class="text-gray-600 mt-2">Informe o motivo do cancelamento</p>
            </div>

            <!-- Informações da Solicitação -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <h3 class="font-semibold text-gray-900 mb-3">Informações do Agendamento</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Nº de Atendimento:</span>
                        <span class="font-semibold text-gray-900"><?= htmlspecialchars($tokenData['protocol'] ?? $solicitacao['numero_solicitacao'] ?? 'N/A') ?></span>
                    </div>
                    <?php if ($tokenData['scheduled_date']): ?>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Data:</span>
                        <span class="font-semibold text-gray-900"><?= date('d/m/Y', strtotime($tokenData['scheduled_date'])) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($tokenData['scheduled_time']): ?>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Horário:</span>
                        <span class="font-semibold text-gray-900"><?= htmlspecialchars($tokenData['scheduled_time']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Formulário de Cancelamento -->
            <form method="POST" action="<?= url('cancelamento-horario?token=' . urlencode($token)) ?>" class="space-y-4">
                <div>
                    <label for="motivo" class="block text-sm font-medium text-gray-700 mb-2">
                        Motivo do Cancelamento <span class="text-red-500">*</span>
                    </label>
                    <textarea 
                        id="motivo" 
                        name="motivo" 
                        rows="4" 
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                        placeholder="Informe o motivo do cancelamento..."
                    ></textarea>
                </div>

                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                    <p class="text-sm text-yellow-800">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Ao cancelar, entraremos em contato para reagendar em outro horário.
                    </p>
                </div>

                <div class="flex gap-3">
                    <a href="<?= url('confirmacao-horario?token=' . urlencode($token)) ?>" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-3 px-4 rounded-lg transition-colors duration-200 text-center">
                        Voltar
                    </a>
                    <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-4 rounded-lg transition-colors duration-200">
                        <i class="fas fa-times-circle mr-2"></i>
                        Cancelar Horário
                    </button>
                </div>
            </form>
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

