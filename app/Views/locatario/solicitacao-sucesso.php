<?php
/**
 * View: Tela de Sucesso - Nova Solicitação
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
            
            <!-- Informações -->
            <div class="animate-fade-in-delay-2 space-y-4 mb-8">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-left">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-blue-600 mr-3 mt-0.5"></i>
                        <div>
                            <h4 class="text-sm font-medium text-blue-800">Próximos Passos</h4>
                            <p class="text-sm text-blue-700 mt-1">
                                Você receberá uma notificação via WhatsApp e no aplicativo com a confirmação do agendamento e os detalhes do atendimento.
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-left">
                    <div class="flex items-start">
                        <i class="fas fa-bell text-yellow-600 mr-3 mt-0.5"></i>
                        <div>
                            <h4 class="text-sm font-medium text-yellow-800">Fique Atento</h4>
                            <p class="text-sm text-yellow-700 mt-1">
                                Acompanhe o status da sua solicitação pelo painel. Você pode visualizar todas as atualizações em tempo real.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Botão Voltar ao Dashboard -->
            <div class="animate-fade-in-delay-3">
                <a href="<?= url($locatario['instancia'] . '/dashboard') ?>" 
                   class="w-full inline-flex items-center justify-center px-6 py-3 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-home mr-2"></i>
                    Voltar ao Dashboard
                </a>
                
                <a href="<?= url($locatario['instancia'] . '/solicitacoes') ?>" 
                   class="w-full inline-flex items-center justify-center px-6 py-3 mt-3 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                    <i class="fas fa-list mr-2"></i>
                    Ver Minhas Solicitações
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

