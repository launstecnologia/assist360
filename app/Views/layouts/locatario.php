<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Assistência 360°' ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= asset('favicon.ico') ?>">
    
    <style>
        /* Customizações específicas para o locatário */
        .locatario-gradient {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
        }
        
        .card-hover {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .status-badge {
            font-size: 0.75rem;
            font-weight: 500;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
        }
        
        .status-nova { @apply bg-blue-100 text-blue-800; }
        .status-buscando { @apply bg-yellow-100 text-yellow-800; }
        .status-agendado { @apply bg-green-100 text-green-800; }
        .status-pendencias { @apply bg-red-100 text-red-800; }
        .status-concluido { @apply bg-gray-100 text-gray-800; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <?php 
    $isLocatarioLogged = isset($_SESSION['locatario']) && 
                         !empty($_SESSION['locatario']) && 
                         is_array($_SESSION['locatario']) &&
                         isset($_SESSION['locatario']['id']) &&
                         !empty($_SESSION['locatario']['id']);
    if ($isLocatarioLogged): ?>
    <header class="bg-white shadow-sm border-b sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-3">
                <!-- Logo da Imobiliária (Esquerda) -->
                <div class="flex items-center">
                    <?php 
                    if (isset($_SESSION['locatario']['imobiliaria_id'])) {
                        // Buscar logo da imobiliária do banco de dados
                        $imobiliariaModel = new \App\Models\Imobiliaria();
                        $imobiliaria = $imobiliariaModel->find($_SESSION['locatario']['imobiliaria_id']);
                        
                        if ($imobiliaria && !empty($imobiliaria['logo'])): ?>
                            <img src="<?= url('Public/uploads/logos/' . $imobiliaria['logo']) ?>" 
                                 alt="<?= htmlspecialchars($imobiliaria['nome'] ?? 'Imobiliária') ?>" 
                                 class="h-10 w-auto"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="h-10 w-10 bg-blue-600 rounded flex items-center justify-center" style="display: none;">
                                <i class="fas fa-building text-white text-sm"></i>
                            </div>
                        <?php elseif ($imobiliaria && !empty($imobiliaria['nome'])): ?>
                            <div class="h-10 w-10 bg-blue-600 rounded flex items-center justify-center">
                                <i class="fas fa-building text-white text-sm"></i>
                            </div>
                        <?php endif;
                    }
                    ?>
                </div>
                
                <!-- Logo KSS e Botão Logout (Direita) -->
                <div class="flex items-center gap-4">
                    <?= kss_logo('', 'KSS ASSISTÊNCIA 360°', 20) ?>
                    <?php 
                    $instancia = $_SESSION['locatario']['instancia'] ?? '';
                    ?>
                    <a href="<?= url($instancia . '/logout') ?>" 
                       class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors"
                       title="Sair">
                        <i class="fas fa-sign-out-alt mr-2"></i>
                        <span class="hidden sm:inline">Sair</span>
                    </a>
                </div>
            </div>
        </div>
    </header>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 pb-24">
        <?= $content ?>
    </main>

    <!-- NavBottom - Barra de navegação inferior estilo app -->
    <?php if ($isLocatarioLogged): 
        $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $instancia = $_SESSION['locatario']['instancia'] ?? '';
    ?>
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-lg z-50 md:hidden">
        <div class="flex justify-around items-center h-16">
            <!-- Dashboard -->
            <a href="<?= url($instancia . '/dashboard') ?>" 
               class="flex flex-col items-center justify-center flex-1 h-full <?= strpos($currentPath, '/dashboard') !== false ? 'text-green-600' : 'text-gray-500' ?>">
                <i class="fas fa-home text-xl mb-1"></i>
                <span class="text-xs">Início</span>
            </a>
            
            <!-- Solicitações -->
            <a href="<?= url($instancia . '/solicitacoes') ?>" 
               class="flex flex-col items-center justify-center flex-1 h-full <?= strpos($currentPath, '/solicitacoes') !== false ? 'text-green-600' : 'text-gray-500' ?>">
                <i class="fas fa-list text-xl mb-1"></i>
                <span class="text-xs">Solicitações</span>
            </a>
            
            <!-- Nova Solicitação -->
            <?php 
            $locatario = $_SESSION['locatario'] ?? [];
            $acessoPermitido = $locatario['acesso_permitido'] ?? true; // Por padrão, permite acesso (compatibilidade)
            if ($acessoPermitido): ?>
                <a href="<?= url($instancia . '/nova-solicitacao') ?>" 
                   class="flex flex-col items-center justify-center flex-1 h-full text-green-600">
                    <div class="w-12 h-12 bg-green-600 rounded-full flex items-center justify-center mb-1 shadow-lg">
                        <i class="fas fa-plus text-white text-xl"></i>
                    </div>
                    <span class="text-xs">Nova</span>
                </a>
            <?php else: ?>
                <div class="flex flex-col items-center justify-center flex-1 h-full text-gray-400 cursor-not-allowed" 
                     title="<?= htmlspecialchars($locatario['motivo_bloqueio'] ?? 'Acesso bloqueado') ?>">
                    <div class="w-12 h-12 bg-gray-300 rounded-full flex items-center justify-center mb-1">
                        <i class="fas fa-lock text-white text-xl"></i>
                    </div>
                    <span class="text-xs">Bloqueado</span>
                </div>
            <?php endif; ?>
            
            <!-- Perfil -->
            <a href="<?= url($instancia . '/perfil') ?>" 
               class="flex flex-col items-center justify-center flex-1 h-full <?= strpos($currentPath, '/perfil') !== false ? 'text-green-600' : 'text-gray-500' ?>">
                <i class="fas fa-user text-xl mb-1"></i>
                <span class="text-xs">Perfil</span>
            </a>
            
            <!-- Menu -->
            <div class="flex flex-col items-center justify-center flex-1 h-full text-gray-500 relative">
                <button onclick="toggleNavMenu()" class="flex flex-col items-center">
                    <i class="fas fa-ellipsis-v text-xl mb-1"></i>
                    <span class="text-xs">Mais</span>
                </button>
                
                <!-- Menu Dropdown -->
                <div id="nav-menu" class="hidden absolute bottom-full right-0 mb-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 py-2">
                    <a href="<?= url($instancia . '/dashboard') ?>" 
                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <i class="fas fa-home mr-2"></i>
                        Dashboard
                    </a>
                    <a href="<?= url($instancia . '/solicitacoes') ?>" 
                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <i class="fas fa-list mr-2"></i>
                        Minhas Solicitações
                    </a>
                    <div class="border-t border-gray-100 my-1"></div>
                    <a href="<?= url($instancia . '/logout') ?>" 
                       class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                        <i class="fas fa-sign-out-alt mr-2"></i>
                        Sair
                    </a>
                </div>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <script>
        // Toggle nav menu
        function toggleNavMenu() {
            const menu = document.getElementById('nav-menu');
            if (menu) {
                menu.classList.toggle('hidden');
            }
        }
        
        // Close nav menu when clicking outside
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('nav-menu');
            if (!menu) return;
            
            const button = event.target.closest('button');
            const isMenuClick = menu.contains(event.target);
            
            if (!isMenuClick && (!button || !button.onclick || button.onclick.toString().indexOf('toggleNavMenu') === -1)) {
                menu.classList.add('hidden');
            }
        });
        
        // Auto-hide alerts (APENAS mensagens com classe .alert-message)
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert-message');
            alerts.forEach(function(alert) {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 500);
            });
        }, 5000);
    </script>
    
    <!-- Custom Calendar Styles -->
    <style>
        .custom-calendar-wrapper input[readonly] {
            cursor: pointer;
            background-color: white;
        }
        
        .custom-calendar {
            animation: fadeIn 0.2s ease-in;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .custom-calendar-day:not(:disabled):hover {
            background-color: #f3f4f6 !important;
        }
        
        .custom-calendar-day:disabled {
            opacity: 0.5;
        }
        
        /* Mobile optimizations */
        @media (max-width: 640px) {
            .custom-calendar {
                width: calc(100vw - 20px) !important;
                max-width: 320px !important;
            }
        }
    </style>
    
    <!-- Custom Calendar Script -->
    <script src="<?= asset('js/custom-calendar.js') ?>"></script>
</body>
</html>
