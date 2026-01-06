<?php
$title = $title ?? 'Erro';
ob_start();
?>

<div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div class="bg-white rounded-lg shadow-lg p-8 text-center">
            <!-- Ícone de Erro -->
            <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-red-100 mb-6">
                <i class="fas fa-exclamation-triangle text-red-600 text-4xl"></i>
            </div>

            <!-- Título e Mensagem -->
            <h2 class="text-2xl font-bold text-gray-900 mb-4"><?= htmlspecialchars($title) ?></h2>
            <p class="text-gray-600 mb-6"><?= htmlspecialchars($message) ?></p>

            <!-- Mensagem de Ajuda -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <p class="text-sm text-blue-800">
                    <i class="fas fa-info-circle mr-2"></i>
                    Se você acredita que isso é um erro, entre em contato conosco através do WhatsApp ou telefone.
                </p>
            </div>

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

