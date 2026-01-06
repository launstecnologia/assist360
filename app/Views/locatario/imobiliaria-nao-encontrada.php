<?php
/**
 * View: Imobiliária Não Encontrada
 */
$title = 'Imobiliária Não Encontrada - Assistência 360°';
$currentPage = 'imobiliaria-nao-encontrada';
ob_start();
?>

<div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <!-- Logo KSS -->
        <div class="text-center mb-8">
            <div class="mx-auto h-20 w-20 bg-blue-600 rounded-lg flex items-center justify-center">
                <i class="fas fa-building text-white text-2xl"></i>
            </div>
        </div>
        
        <!-- Título Principal -->
        <h1 class="text-center text-3xl font-bold text-gray-900 mb-2">
            Assistência 360°
        </h1>
    </div>

    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
            <!-- Ícone de Erro -->
            <div class="text-center mb-6">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-3xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">
                    Imobiliária Não Encontrada
                </h2>
                <p class="text-sm text-gray-600">
                    A instância solicitada não foi encontrada em nosso sistema.
                </p>
            </div>
            
            <!-- Mensagem Informativa -->
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded-r mb-6">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-600 mr-3 mt-0.5"></i>
                    <div>
                        <p class="text-sm text-gray-700 leading-relaxed">
                            A URL que você está tentando acessar não corresponde a nenhuma imobiliária cadastrada em nosso sistema.
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Informações Adicionais -->
            <div class="space-y-3 mb-6">
                <div class="flex items-start text-sm text-gray-600">
                    <i class="fas fa-check-circle text-green-500 mr-2 mt-0.5"></i>
                    <span>Verifique se a URL está correta</span>
                </div>
                <div class="flex items-start text-sm text-gray-600">
                    <i class="fas fa-check-circle text-green-500 mr-2 mt-0.5"></i>
                    <span>Entre em contato com sua imobiliária para obter o link correto</span>
                </div>
                <div class="flex items-start text-sm text-gray-600">
                    <i class="fas fa-check-circle text-green-500 mr-2 mt-0.5"></i>
                    <span>Ou utilize a opção de solicitação manual abaixo</span>
                </div>
            </div>
            
            <!-- Instância Tentada (se disponível) -->
            <?php if (!empty($instancia)): ?>
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 mb-6">
                <p class="text-xs text-gray-500 mb-1">Instância tentada:</p>
                <p class="text-sm font-mono text-gray-900"><?= htmlspecialchars($instancia) ?></p>
            </div>
            <?php endif; ?>
            
            <!-- Botão de Ação -->
            <div>
                <a href="<?= url('') ?>" 
                   class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-home mr-2"></i>
                    Voltar ao Início
                </a>
            </div>
        </div>
    </div>
    
    <!-- Logo KSS pequena abaixo do card -->
    <div class="mt-6 flex justify-center items-center">
        <?= kss_logo('mx-auto', 'KSS ASSISTÊNCIA 360°', 20) ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'app/Views/layouts/locatario.php';
?>

