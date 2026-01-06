<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $app['name'] ?></title>
    
    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    </style>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#2563eb">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="<?= $app['name'] ?>">
    
    <!-- Manifest -->
    <link rel="manifest" href="/manifest.json">
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-10 w-10 bg-blue-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-shield-alt text-white text-xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h1 class="text-2xl font-bold text-gray-900"><?= $app['name'] ?></h1>
                        <p class="text-sm text-gray-500">Sistema de Assistências Residenciais</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="/login" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-150 ease-in-out">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Login Admin
                    </a>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Hero Section -->
    <section class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div class="text-center">
                <h2 class="text-4xl font-extrabold sm:text-5xl md:text-6xl">
                    Assistências Residenciais
                </h2>
                <p class="mt-3 max-w-md mx-auto text-base sm:text-lg md:mt-5 md:text-xl md:max-w-3xl">
                    Solicite assistências para seu imóvel de forma rápida e eficiente. 
                    Nosso sistema conecta locatários, imobiliárias e prestadores de serviço.
                </p>
                <div class="mt-5 max-w-md mx-auto sm:flex sm:justify-center md:mt-8">
                    <div class="rounded-md shadow">
                        <a href="/pwa" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-blue-600 bg-white hover:bg-gray-50 md:py-4 md:text-lg md:px-10 transition duration-150 ease-in-out">
                            <i class="fas fa-mobile-alt mr-2"></i>
                            Acessar como Locatário
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Features Section -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h3 class="text-3xl font-extrabold text-gray-900">
                    Como Funciona
                </h3>
                <p class="mt-4 text-lg text-gray-500">
                    Processo simples e automatizado para solicitar assistências
                </p>
            </div>
            
            <div class="mt-16 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                <div class="text-center">
                    <div class="mx-auto h-16 w-16 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-mobile-alt text-blue-600 text-2xl"></i>
                    </div>
                    <h4 class="mt-6 text-xl font-semibold text-gray-900">1. Acesse o PWA</h4>
                    <p class="mt-2 text-gray-500">
                        Use seu celular ou computador para acessar nossa aplicação web progressiva
                    </p>
                </div>
                
                <div class="text-center">
                    <div class="mx-auto h-16 w-16 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-clipboard-list text-green-600 text-2xl"></i>
                    </div>
                    <h4 class="mt-6 text-xl font-semibold text-gray-900">2. Faça sua Solicitação</h4>
                    <p class="mt-2 text-gray-500">
                        Descreva o problema, adicione fotos e escolha o melhor horário para atendimento
                    </p>
                </div>
                
                <div class="text-center">
                    <div class="mx-auto h-16 w-16 bg-purple-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-tools text-purple-600 text-2xl"></i>
                    </div>
                    <h4 class="mt-6 text-xl font-semibold text-gray-900">3. Receba Atendimento</h4>
                    <p class="mt-2 text-gray-500">
                        Um prestador qualificado será enviado no horário agendado para resolver seu problema
                    </p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Benefits Section -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h3 class="text-3xl font-extrabold text-gray-900">
                    Benefícios do Sistema
                </h3>
            </div>
            
            <div class="mt-16 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <div class="text-center">
                        <i class="fas fa-clock text-blue-600 text-3xl"></i>
                        <h4 class="mt-4 text-lg font-semibold text-gray-900">Rapidez</h4>
                        <p class="mt-2 text-gray-500 text-sm">
                            Redução de até 70% no tempo de abertura de chamados
                        </p>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <div class="text-center">
                        <i class="fas fa-comments text-green-600 text-3xl"></i>
                        <h4 class="mt-4 text-lg font-semibold text-gray-900">Comunicação</h4>
                        <p class="mt-2 text-gray-500 text-sm">
                            Notificações automáticas via WhatsApp
                        </p>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <div class="text-center">
                        <i class="fas fa-chart-line text-purple-600 text-3xl"></i>
                        <h4 class="mt-4 text-lg font-semibold text-gray-900">Controle</h4>
                        <p class="mt-2 text-gray-500 text-sm">
                            Acompanhamento completo do histórico de atendimentos
                        </p>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <div class="text-center">
                        <i class="fas fa-shield-alt text-red-600 text-3xl"></i>
                        <h4 class="mt-4 text-lg font-semibold text-gray-900">Segurança</h4>
                        <p class="mt-2 text-gray-500 text-sm">
                            Sistema seguro com controle de acesso
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- CTA Section -->
    <section class="bg-blue-600">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="text-center">
                <h3 class="text-3xl font-extrabold text-white">
                    Pronto para começar?
                </h3>
                <p class="mt-4 text-xl text-blue-100">
                    Acesse nossa aplicação e solicite sua primeira assistência
                </p>
                <div class="mt-8">
                    <a href="/pwa" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-blue-600 bg-white hover:bg-gray-50 transition duration-150 ease-in-out">
                        <i class="fas fa-rocket mr-2"></i>
                        Começar Agora
                    </a>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="bg-gray-900 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <div class="mb-4">
                        <?= kss_logo('', 'KSS ASSISTÊNCIA 360°', 40) ?>
                    </div>
                    <p class="text-gray-400">
                        Sistema completo para gestão de assistências residenciais, 
                        conectando locatários, imobiliárias e prestadores de serviço.
                    </p>
                </div>
                
                <div>
                    <h4 class="text-lg font-semibold mb-4">Links Úteis</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="/pwa" class="hover:text-white">PWA Locatário</a></li>
                        <li><a href="/login" class="hover:text-white">Login Admin</a></li>
                        <li><a href="/condicoes-gerais" class="hover:text-white">Condições Gerais</a></li>
                        <li><a href="#" class="hover:text-white">Suporte</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-lg font-semibold mb-4">Contato</h4>
                    <div class="space-y-2 text-gray-400">
                        <p><i class="fas fa-envelope mr-2"></i> contato@kssseguros.com</p>
                        <p><i class="fas fa-phone mr-2"></i> (11) 9999-9999</p>
                    </div>
                </div>
            </div>
            
            <div class="mt-8 pt-8 border-t border-gray-800 text-center text-gray-400">
                <p>&copy; <?= date('Y') ?> <?= $app['name'] ?>. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>
    
    <!-- PWA Install Prompt -->
    <div id="install-prompt" class="fixed bottom-4 left-4 right-4 bg-blue-600 text-white p-4 rounded-lg shadow-lg hidden">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-download mr-3"></i>
                <div>
                    <p class="font-semibold">Instalar App</p>
                    <p class="text-sm text-blue-100">Instale nossa aplicação para melhor experiência</p>
                </div>
            </div>
            <div class="flex space-x-2">
                <button id="install-btn" class="bg-white text-blue-600 px-4 py-2 rounded text-sm font-medium">
                    Instalar
                </button>
                <button id="dismiss-install" class="text-blue-100 hover:text-white">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>
    
    <script>
        // PWA Install functionality
        let deferredPrompt;
        
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            document.getElementById('install-prompt').classList.remove('hidden');
        });
        
        document.getElementById('install-btn').addEventListener('click', async () => {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                console.log(`User response to the install prompt: ${outcome}`);
                deferredPrompt = null;
                document.getElementById('install-prompt').classList.add('hidden');
            }
        });
        
        document.getElementById('dismiss-install').addEventListener('click', () => {
            document.getElementById('install-prompt').classList.add('hidden');
        });
    </script>
</body>
</html>
