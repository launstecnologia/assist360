<?php
$title = $title ?? 'Cancelar Solicitação';
ob_start();
?>

<div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <!-- Header -->
            <div class="text-center mb-6">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-times-circle text-red-600 text-2xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Cancelar Solicitação</h2>
                <p class="text-gray-600 mt-2">Informe o motivo do cancelamento</p>
            </div>

            <!-- Informações da Solicitação -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <h3 class="font-semibold text-gray-900 mb-3">Informações da Solicitação</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Nº de Atendimento:</span>
                        <span class="font-semibold text-gray-900"><?= htmlspecialchars($solicitacao['numero_solicitacao'] ?? 'N/A') ?></span>
                    </div>
                    <?php if (!empty($solicitacao['categoria_nome'])): ?>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Categoria:</span>
                        <span class="font-semibold text-gray-900"><?= htmlspecialchars($solicitacao['categoria_nome']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($solicitacao['status_nome'])): ?>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Status:</span>
                        <span class="font-semibold text-gray-900"><?= htmlspecialchars($solicitacao['status_nome']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Formulário de Cancelamento -->
            <form method="POST" action="<?= url('cancelar-solicitacao?token=' . urlencode($token)) ?>" class="space-y-4">
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
                        Ao cancelar esta solicitação, ela será encerrada e não poderá ser reaberta. Se precisar de assistência, será necessário criar uma nova solicitação.
                    </p>
                </div>

                <div class="flex gap-3">
                    <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 text-white font-semibold py-3 px-4 rounded-lg transition-colors duration-200">
                        <i class="fas fa-times-circle mr-2"></i>
                        Cancelar Solicitação
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

