<?php
/**
 * View: Tela de Emergência - Solicitação Manual
 */
$title = 'Atendimento Emergencial - Assistência 360°';

// Dados da solicitação
$solicitacaoId = $solicitacao_id ?? 0;
$telefoneEmergencia = $telefone_emergencia ?? null;

// Formatar telefone para link tel:
$telefoneLink = '';
$telefoneFormatado = '';
if ($telefoneEmergencia && !empty($telefoneEmergencia['numero'])) {
    $telefoneLink = preg_replace('/[^0-9+]/', '', $telefoneEmergencia['numero']);
    $telefoneFormatado = $telefoneEmergencia['numero'];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @keyframes pulse-ring {
            0% { transform: scale(0.8); opacity: 1; }
            100% { transform: scale(1.5); opacity: 0; }
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes shake {
            0%, 100% { transform: rotate(0deg); }
            10%, 30%, 50%, 70%, 90% { transform: rotate(-10deg); }
            20%, 40%, 60%, 80% { transform: rotate(10deg); }
        }
        .animate-fade-in {
            animation: fadeIn 0.5s ease-out forwards;
        }
        .animate-fade-in-delay-1 {
            animation: fadeIn 0.5s ease-out 0.2s forwards;
            opacity: 0;
        }
        .animate-fade-in-delay-2 {
            animation: fadeIn 0.5s ease-out 0.4s forwards;
            opacity: 0;
        }
        .animate-fade-in-delay-3 {
            animation: fadeIn 0.5s ease-out 0.6s forwards;
            opacity: 0;
        }
        .pulse-ring {
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: rgba(239, 68, 68, 0.3);
            animation: pulse-ring 1.5s ease-out infinite;
        }
        .shake-animation {
            animation: shake 0.8s ease-in-out infinite;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <!-- Header Vermelho -->
            <div class="bg-red-600 px-6 py-6 text-white text-center animate-fade-in">
                <div class="flex flex-col items-center">
                    <i class="fas fa-exclamation-triangle text-4xl mb-3"></i>
                    <h1 class="text-xl font-bold">Solicitação Emergencial</h1>
                    <?php if ($solicitacaoId): ?>
                        <p class="text-red-100 text-sm mt-1">Nº Solicitação: #<?= $solicitacaoId ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Conteúdo -->
            <div class="p-6">
                <!-- Aviso Fora do Horário -->
                <div class="animate-fade-in-delay-1 mb-6">
                    <div class="bg-red-50 border-2 border-red-200 rounded-xl p-5 text-center">
                        <i class="fas fa-clock text-red-600 text-3xl mb-3"></i>
                        <h3 class="text-lg font-bold text-red-900 mb-2">Fora do Horário Comercial</h3>
                        <p class="text-sm text-red-800 leading-relaxed">
                            Sua solicitação foi registrada. Como está fora do horário comercial, 
                            ligue para o telefone de emergência para atendimento imediato.
                        </p>
                    </div>
                </div>
                
                <!-- Telefone de Emergência -->
                <?php if ($telefoneEmergencia && !empty($telefoneLink)): ?>
                <div class="animate-fade-in-delay-2 mb-6">
                    <p class="text-center text-gray-700 font-medium mb-4">
                        Ligue agora para o nosso atendimento:
                    </p>
                    <div class="relative flex justify-center">
                        <div class="pulse-ring"></div>
                        <a href="tel:<?= $telefoneLink ?>" 
                           id="btn-ligar"
                           class="relative inline-flex flex-col items-center justify-center w-full px-6 py-5 bg-red-600 text-white font-bold text-xl rounded-xl hover:bg-red-700 transition-colors shadow-lg">
                            <div class="shake-animation mb-2">
                                <i class="fas fa-phone text-3xl"></i>
                            </div>
                            <span class="text-lg">Ligar Agora</span>
                            <span class="text-2xl font-bold mt-1"><?= htmlspecialchars($telefoneFormatado) ?></span>
                        </a>
                    </div>
                    <?php if (!empty($telefoneEmergencia['descricao'])): ?>
                        <p class="text-center text-xs text-gray-500 mt-3">
                            <?= htmlspecialchars($telefoneEmergencia['descricao']) ?>
                        </p>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="animate-fade-in-delay-2 mb-6">
                    <div class="bg-yellow-50 border border-yellow-300 rounded-lg p-4">
                        <p class="text-sm text-yellow-800 text-center">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Telefone de emergência não configurado. Aguarde nosso contato.
                        </p>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Instruções -->
                <div class="animate-fade-in-delay-3">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <h4 class="text-sm font-medium text-blue-900 mb-2 text-center">
                            <i class="fas fa-info-circle mr-1"></i>
                            Ao ligar, informe:
                        </h4>
                        <ul class="text-sm text-blue-800 space-y-1">
                            <li class="flex items-center">
                                <i class="fas fa-check text-blue-600 mr-2 text-xs"></i>
                                Número da solicitação: <strong class="ml-1">#<?= $solicitacaoId ?></strong>
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-blue-600 mr-2 text-xs"></i>
                                Que se trata de uma emergência
                            </li>
                        </ul>
                    </div>
                    
                    <!-- Botão Voltar -->
                    <a href="<?= url($locatario['instancia'] ?? '') ?>" 
                       class="w-full inline-flex items-center justify-center px-6 py-3 bg-gray-600 text-white font-medium rounded-lg hover:bg-gray-700 transition-colors">
                        <i class="fas fa-home mr-2"></i>
                        Voltar ao Início
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Rodapé -->
        <p class="text-center text-sm text-gray-500 mt-6">
            Assistência 360° - Atendimento Emergencial 24h
        </p>
    </div>
    
    <script>
        // Tentar abrir o app de telefone automaticamente após 1 segundo
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($telefoneEmergencia && !empty($telefoneLink)): ?>
            setTimeout(function() {
                // Perguntar se deseja ligar
                if (confirm('Deseja ligar agora para o atendimento emergencial?\n\nNúmero: <?= $telefoneFormatado ?>')) {
                    window.location.href = 'tel:<?= $telefoneLink ?>';
                }
            }, 1500);
            <?php endif; ?>
        });
    </script>
</body>
</html>

