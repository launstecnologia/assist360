<?php
$title = $title ?? 'Sucesso';
ob_start();
?>

<div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div class="bg-white rounded-lg shadow-lg p-8 text-center">
            <!-- Ícone de Sucesso -->
            <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full <?= $action === 'confirmacao' ? 'bg-green-100' : 'bg-red-100' ?> mb-6">
                <?php if ($action === 'confirmacao'): ?>
                <i class="fas fa-check-circle text-green-600 text-4xl"></i>
                <?php else: ?>
                <i class="fas fa-times-circle text-red-600 text-4xl"></i>
                <?php endif; ?>
            </div>

            <!-- Título e Mensagem -->
            <h2 class="text-2xl font-bold text-gray-900 mb-4"><?= htmlspecialchars($title) ?></h2>
            <p class="text-gray-600 mb-6"><?= htmlspecialchars($message) ?></p>

            <!-- Informações da Solicitação -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6 text-left">
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Nº de Atendimento:</span>
                        <span class="font-semibold text-gray-900"><?= htmlspecialchars($solicitacao['numero_solicitacao'] ?? 'N/A') ?></span>
                    </div>
                    <?php if ($solicitacao['data_agendamento']): ?>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Data:</span>
                        <span class="font-semibold text-gray-900"><?= date('d/m/Y', strtotime($solicitacao['data_agendamento'])) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Mensagem Final -->
            <?php if ($action === 'confirmacao'): ?>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <p class="text-sm text-green-800">
                    <i class="fas fa-info-circle mr-2"></i>
                    Aguarde o contato da equipe de atendimento. Caso tenha dúvidas, entre em contato conosco.
                </p>
            </div>
            <?php else: ?>
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <p class="text-sm text-yellow-800">
                    <i class="fas fa-info-circle mr-2"></i>
                    Entraremos em contato em breve para reagendar o atendimento em outro horário.
                </p>
            </div>
            <?php endif; ?>

            <!-- Botão de Ação -->
            <a href="<?= url('/') ?>" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors duration-200">
                <i class="fas fa-home mr-2"></i>
                Voltar ao Início
            </a>
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

