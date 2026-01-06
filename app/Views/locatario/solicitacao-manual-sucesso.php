<?php
/**
 * View: Tela de Sucesso - Solicitação Manual
 */
$title = 'Solicitação Enviada - Assistência 360°';

// Dados da solicitação
$solicitacaoId = $solicitacao_id ?? 0;
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
        @keyframes checkmark {
            0% { transform: scale(0); opacity: 0; }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); opacity: 1; }
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        .animate-checkmark {
            animation: checkmark 0.5s ease-out forwards;
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
        .animate-pulse-slow {
            animation: pulse 2s ease-in-out infinite;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <div class="bg-white rounded-2xl shadow-xl p-8 text-center">
            <!-- Ícone de Sucesso -->
            <div class="mb-6 animate-checkmark">
                <div class="w-24 h-24 mx-auto bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-check text-green-600 text-5xl"></i>
                </div>
            </div>
            
            <!-- Título -->
            <h1 class="text-2xl font-bold text-gray-900 mb-2 animate-fade-in">
                Solicitação Enviada!
            </h1>
            
            <!-- ID da Solicitação -->
            <?php if ($solicitacaoId): ?>
            <div class="animate-fade-in-delay-1">
                <p class="text-gray-600 mb-4">
                    Sua solicitação foi registrada com sucesso.
                </p>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                    <p class="text-sm text-green-700">Número da Solicitação</p>
                    <p class="text-2xl font-bold text-green-800">#<?= $solicitacaoId ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Aviso Principal - Entraremos em Contato -->
            <div class="animate-fade-in-delay-2 mb-8">
                <div class="bg-blue-50 border-2 border-blue-300 rounded-xl p-6 animate-pulse-slow">
                    <div class="flex flex-col items-center">
                        <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-headset text-blue-600 text-3xl"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-blue-800 mb-2">
                            Aguarde nosso contato!
                        </h3>
                        <p class="text-blue-700">
                            Nossa equipe irá analisar sua solicitação e entrará em contato em breve para dar continuidade ao atendimento.
                        </p>
                    </div>
                </div>
                
                <div class="mt-4">
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-left">
                        <div class="flex items-start">
                            <i class="fab fa-whatsapp text-green-600 mr-3 mt-0.5 text-xl"></i>
                            <div>
                                <h4 class="text-sm font-medium text-gray-800">Via WhatsApp</h4>
                                <p class="text-sm text-gray-600 mt-1">
                                    Você poderá receber contato pelo WhatsApp cadastrado.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Botão Fechar -->
            <div class="animate-fade-in-delay-3">
                <a href="<?= url($locatario['instancia'] ?? '') ?>" 
                   class="w-full inline-flex items-center justify-center px-6 py-3 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-home mr-2"></i>
                    Voltar ao Início
                </a>
            </div>
        </div>
        
        <!-- Rodapé -->
        <p class="text-center text-sm text-gray-500 mt-6">
            Assistência 360° - Estamos aqui para ajudar!
        </p>
    </div>
</body>
</html>

