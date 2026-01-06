<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Condições Gerais - <?= $app['name'] ?? 'KSS Assistência' ?></title>
    
    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-6">
                <div class="flex items-center">
                    <div class="h-10 w-10 bg-blue-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-shield-alt text-white text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <h1 class="text-2xl font-bold text-gray-900"><?= $app['name'] ?? 'KSS Assistência' ?></h1>
                        <p class="text-sm text-gray-500">Condições Gerais dos Serviços</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <button onclick="voltarPagina()" class="px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>Voltar
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Content -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-extrabold text-gray-900 mb-4">Condições Gerais dos Serviços</h2>
            <p class="text-lg text-gray-600">Baixe o documento completo com todas as condições, limites e termos de utilização</p>
        </div>

        <!-- Download Cards -->
        <div class="grid md:grid-cols-2 gap-6">
            <!-- Residencial -->
            <div class="bg-white rounded-lg shadow-lg p-8 hover:shadow-xl transition-shadow">
                <div class="text-center">
                    <div class="mx-auto w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mb-6">
                        <i class="fas fa-home text-blue-600 text-3xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Residencial</h3>
                    <p class="text-gray-600 mb-2">Perfil Top Plus</p>
                    <p class="text-sm text-gray-500 mb-6">Manual completo dos serviços de assistência para residências</p>
                    <a href="<?= url('Public/assets/pdf/CONDICOES_GERAIS_ASSIST_KSS_RESIDENCIAL.pdf') ?>" 
                       download="CONDICOES_GERAIS_ASSIST_KSS_RESIDENCIAL.pdf"
                       class="inline-flex items-center justify-center w-full px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-download mr-2"></i>
                        Baixar PDF Residencial
                    </a>
                </div>
            </div>

            <!-- Comercial -->
            <div class="bg-white rounded-lg shadow-lg p-8 hover:shadow-xl transition-shadow">
                <div class="text-center">
                    <div class="mx-auto w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mb-6">
                        <i class="fas fa-building text-green-600 text-3xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Comercial</h3>
                    <p class="text-gray-600 mb-2">Perfil Plus</p>
                    <p class="text-sm text-gray-500 mb-6">Manual completo dos serviços de assistência para empresas</p>
                    <a href="<?= url('Public/assets/pdf/CONDICOES_GERAIS_ASSIST_KSS_COMERCIAL.pdf') ?>" 
                       download="CONDICOES_GERAIS_ASSIST_KSS_COMERCIAL.pdf"
                       class="inline-flex items-center justify-center w-full px-6 py-3 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-download mr-2"></i>
                        Baixar PDF Comercial
                    </a>
                </div>
            </div>
        </div>

        <!-- Informação adicional -->
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6 text-center">
            <i class="fas fa-info-circle text-blue-600 text-2xl mb-3"></i>
            <p class="text-sm text-blue-800">
                Os documentos PDF contêm todas as informações sobre limites, quantidades, âmbito de atendimento, exclusões e condições gerais dos serviços.
            </p>
        </div>
    </div>

    <script>
        function voltarPagina() {
            // Verifica se há parâmetro 'return' na URL
            const urlParams = new URLSearchParams(window.location.search);
            const returnUrl = urlParams.get('return');
            
            if (returnUrl) {
                // Se houver parâmetro return, redireciona para essa URL
                window.location.href = decodeURIComponent(returnUrl);
                return;
            }
            
            // Se não houver parâmetro, tenta usar document.referrer
            const referrer = document.referrer;
            
            if (referrer) {
                // Verifica se o referrer é uma página de nova solicitação (etapa 2)
                if (referrer.includes('/nova-solicitacao/etapa/2') || referrer.includes('/etapa/2')) {
                    // Volta para a página de serviços
                    window.location.href = referrer;
                    return;
                }
                
                // Se for outra página relacionada, também volta
                if (referrer.includes('/nova-solicitacao') || referrer.includes('/solicitacao-manual')) {
                    window.location.href = referrer;
                    return;
                }
            }
            
            // Se não conseguir identificar, tenta fechar a janela se foi aberta por window.open
            if (window.opener) {
                window.close();
            } else {
                // Se não conseguir fechar, volta na história do navegador
                if (window.history.length > 1) {
                    window.history.back();
                } else {
                    // Se não houver histórico, redireciona para página inicial
                    window.location.href = '/';
                }
            }
        }
    </script>
</body>
</html>
