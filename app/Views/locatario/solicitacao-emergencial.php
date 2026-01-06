<?php
/**
 * View: Tela de Emergência - Telefone 0800
 */
$title = 'Atendimento Emergencial - Assistência 360°';
$currentPage = 'locatario-emergencial';
ob_start();
?>

<!-- Card Principal -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden max-w-2xl mx-auto">
    <!-- Header do Card -->
    <div class="bg-red-600 px-4 sm:px-6 py-4 sm:py-5 text-white text-center">
        <div class="flex flex-col items-center">
            <i class="fas fa-exclamation-triangle text-3xl sm:text-4xl mb-3"></i>
            <div>
                <h2 class="text-lg sm:text-xl font-bold">Solicitação Emergencial Criada</h2>
                <p class="text-red-100 text-xs sm:text-sm mt-1">Nº Solicitação: <?= htmlspecialchars($solicitacao['numero_solicitacao'] ?? 'KSS' . $solicitacao['id']) ?></p>
            </div>
        </div>
    </div>
    
    <!-- Conteúdo -->
    <div class="p-4 sm:p-6">
        <!-- Aviso Fora do Horário Comercial -->
        <div class="bg-red-50 border-2 border-red-200 rounded-lg p-4 sm:p-6 mb-4 sm:mb-6 text-center">
            <i class="fas fa-clock text-red-600 text-3xl sm:text-4xl mb-3"></i>
            <h3 class="text-base sm:text-lg font-bold text-red-900 mb-3">Fora do Horário Comercial</h3>
            <p class="text-sm sm:text-base text-red-800 mb-4 leading-relaxed max-w-xl mx-auto">
                Sua solicitação foi registrada, mas como está sendo feita fora do horário comercial (08h00 às 17h30) ou em final de semana, 
                é necessário entrar em contato pelo telefone de emergência para garantir um atendimento imediato.
            </p>
            
            <?php if ($telefoneEmergencia): ?>
                <div class="bg-white rounded-lg p-4 sm:p-5 border border-red-300 max-w-md mx-auto">
                    <p class="text-sm sm:text-base font-medium text-gray-700 mb-4">Ligue agora para o nosso atendimento de emergência:</p>
                    <div class="flex items-center justify-center">
                        <a href="tel:<?= preg_replace('/[^0-9+]/', '', $telefoneEmergencia['numero']) ?>" 
                           class="inline-flex flex-col items-center justify-center px-6 sm:px-8 py-4 sm:py-5 bg-red-600 text-white font-bold text-lg sm:text-xl rounded-lg hover:bg-red-700 transition-colors shadow-lg w-full"
                           onclick="if(navigator.userAgent.match(/iPhone|iPad|iPod/i)) { window.location.href='tel:<?= preg_replace('/[^0-9+]/', '', $telefoneEmergencia['numero']) ?>'; return false; }">
                            <i class="fas fa-phone text-2xl sm:text-3xl mb-2"></i>
                            <span>Ligar</span>
                            <span class="text-base sm:text-lg mt-1"><?= htmlspecialchars($telefoneEmergencia['numero']) ?></span>
                        </a>
                    </div>
                    <?php if (!empty($telefoneEmergencia['descricao'])): ?>
                        <p class="text-xs sm:text-sm text-gray-600 mt-3">
                            <?= htmlspecialchars($telefoneEmergencia['descricao']) ?>
                        </p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="bg-yellow-50 border border-yellow-300 rounded-lg p-4 max-w-md mx-auto">
                    <p class="text-sm text-yellow-800 text-center">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Telefone de emergência não configurado. Entre em contato com a administração.
                    </p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Informações da Solicitação -->
        <div class="bg-gray-50 rounded-lg p-4 sm:p-6 mb-4 sm:mb-6 text-center">
            <h3 class="text-base sm:text-lg font-medium text-gray-900 mb-3 sm:mb-4">
                <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                Informações da Solicitação
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 max-w-xl mx-auto">
                <div>
                    <p class="text-xs sm:text-sm text-gray-500 mb-1">Número da Solicitação</p>
                    <p class="text-sm sm:text-base font-medium text-gray-900"><?= htmlspecialchars($solicitacao['numero_solicitacao'] ?? 'KSS' . $solicitacao['id']) ?></p>
                </div>
                <div>
                    <p class="text-xs sm:text-sm text-gray-500 mb-1">Categoria</p>
                    <p class="text-sm sm:text-base font-medium text-gray-900"><?= htmlspecialchars($solicitacao['categoria_nome'] ?? 'Não informado') ?></p>
                </div>
                <div>
                    <p class="text-xs sm:text-sm text-gray-500 mb-1">Subcategoria</p>
                    <p class="text-sm sm:text-base font-medium text-gray-900"><?= htmlspecialchars($solicitacao['subcategoria_nome'] ?? 'Não informado') ?></p>
                </div>
                <div>
                    <p class="text-xs sm:text-sm text-gray-500 mb-1">Data de Criação</p>
                    <p class="text-sm sm:text-base font-medium text-gray-900"><?= date('d/m/Y H:i', strtotime($solicitacao['created_at'])) ?></p>
                </div>
            </div>
        </div>
        
        <!-- Instruções -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 sm:p-5 mb-4 sm:mb-6 text-center">
            <h4 class="text-sm sm:text-base font-medium text-blue-900 mb-3">
                <i class="fas fa-lightbulb mr-2"></i>
                O que fazer agora?
            </h4>
            <ul class="text-sm sm:text-base text-blue-800 space-y-2 list-none max-w-md mx-auto">
                <li class="flex items-center justify-center">
                    <i class="fas fa-check-circle text-blue-600 mr-2"></i>
                    Ligue para o telefone de emergência acima
                </li>
                <li class="flex items-center justify-center">
                    <i class="fas fa-check-circle text-blue-600 mr-2"></i>
                    Informe o número da sua solicitação: <strong class="ml-1"><?= htmlspecialchars($solicitacao['numero_solicitacao'] ?? 'KSS' . $solicitacao['id']) ?></strong>
                </li>
                <li class="flex items-center justify-center">
                    <i class="fas fa-check-circle text-blue-600 mr-2"></i>
                    Nossa equipe entrará em contato em até 120 minutos
                </li>
                <li class="flex items-center justify-center">
                    <i class="fas fa-check-circle text-blue-600 mr-2"></i>
                    Você receberá atualizações via WhatsApp
                </li>
            </ul>
        </div>
        
        <!-- Botão para Dashboard -->
        <div class="flex justify-center">
            <a href="<?= url($instancia . '/dashboard') ?>" 
               class="inline-flex items-center justify-center px-6 py-3 bg-gray-600 text-white font-medium rounded-lg hover:bg-gray-700 transition-colors w-full sm:w-auto">
                <i class="fas fa-arrow-left mr-2"></i>
                Voltar para o Dashboard
            </a>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'app/Views/layouts/locatario.php';
?>

