<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? $title . ' - ' : '' ?><?= $app['name'] ?></title>
    
    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <style>
        .sidebar-transition {
            transition: all 0.3s ease-in-out;
        }
        
        .kanban-column {
            min-height: 500px;
        }
        
        .drag-item {
            transition: all 0.2s ease-in-out;
        }
        
        .drag-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .drag-item.dragging {
            opacity: 0.5;
            transform: rotate(5deg);
        }
        
        /* Status Badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-nova-solicitacao {
            background-color: #DBEAFE;
            color: #1E40AF;
        }
        
        .status-buscando-prestador {
            background-color: #FEF3C7;
            color: #92400E;
        }
        
        .status-servico-agendado {
            background-color: #D1FAE5;
            color: #065F46;
        }
        
        .status-em-andamento {
            background-color: #E0E7FF;
            color: #3730A3;
        }
        
        .status-concluido {
            background-color: #D1FAE5;
            color: #065F46;
        }
        
        .status-cancelado {
            background-color: #FEE2E2;
            color: #991B1B;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Sidebar -->
    <div id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-white shadow-lg sidebar-transition transform -translate-x-full lg:translate-x-0 flex flex-col">
        <div class="flex items-center justify-center h-16 px-4 flex-shrink-0">
            <?php 
            $logoUrl = \App\Core\Url::kssLogo();
            if (!empty($logoUrl)): ?>
                <img src="<?= htmlspecialchars($logoUrl) ?>" alt="KSS ASSISTÊNCIA 360°" class="h-10 w-auto" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="flex items-center space-x-2" style="display: none;">
                    <span class="text-green-600 font-bold text-xl">KSS</span>
                    <span class="text-gray-600 text-sm">ASSISTÊNCIA 360°</span>
                </div>
            <?php else: ?>
                <div class="flex items-center space-x-2">
                    <span class="text-green-600 font-bold text-xl">KSS</span>
                    <span class="text-gray-600 text-sm">ASSISTÊNCIA 360°</span>
                </div>
            <?php endif; ?>
        </div>
        
        <nav class="mt-8 flex-1 overflow-y-auto">
            <div class="px-4 space-y-2 pb-4">
                <!-- GERAL -->
                <div class="pt-2">
                    <h3 class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Geral</h3>
                </div>
                
                <?php if (temAcessoPagina('dashboard')): ?>
                <a href="<?= url('admin/dashboard') ?>" class="flex items-center px-4 py-2 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors <?= $currentPage === 'dashboard' ? 'bg-blue-50 text-blue-700 font-medium' : '' ?>">
                    <i class="fas fa-tachometer-alt mr-3 w-5 text-center"></i>
                    Dashboard
                </a>
                <?php endif; ?>

                <?php if (temAcessoPagina('relatorios')): ?>
                <a href="<?= url('admin/relatorios') ?>" class="flex items-center px-4 py-2 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors <?= $currentPage === 'relatorios' ? 'bg-blue-50 text-blue-700 font-medium' : '' ?>">
                    <i class="fas fa-chart-line mr-3 w-5 text-center"></i>
                    Relatórios
                </a>
                <?php endif; ?>
                
                <?php if (temAcessoPagina('kanban')): ?>
                <a href="<?= url('admin/kanban') ?>" class="flex items-center px-4 py-2 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors <?= $currentPage === 'kanban' ? 'bg-blue-50 text-blue-700 font-medium' : '' ?>">
                    <i class="fas fa-columns mr-3 w-5 text-center"></i>
                    <span class="flex-1">Kanban</span>
                    <span id="kanbanBadge" class="hidden ml-2 px-2 py-0.5 text-xs font-bold text-white bg-red-500 rounded-full">0</span>
                </a>
                <?php endif; ?>
                
                <?php if (temAcessoPagina('upload')): ?>
                <a href="<?= url('admin/upload') ?>" class="flex items-center px-4 py-2 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors <?= $currentPage === 'upload' ? 'bg-blue-50 text-blue-700 font-medium' : '' ?>">
                    <i class="fas fa-upload mr-3 w-5 text-center"></i>
                    Upload
                </a>
                <?php endif; ?>
                
                <!-- OPERAÇÕES -->
                <div class="pt-4">
                    <h3 class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Operações</h3>
                </div>
                
                <?php if (temAcessoPagina('solicitacoes-manuais')): ?>
                <a href="<?= url('admin/solicitacoes-manuais') ?>" class="flex items-center px-4 py-2 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors <?= $currentPage === 'solicitacoes-manuais' ? 'bg-blue-50 text-blue-700 font-medium' : '' ?>">
                    <i class="fas fa-file-alt mr-3 w-5 text-center"></i>
                    <span class="flex-1">Solicitações Manuais</span>
                    <?php
                    // Contador de solicitações manuais não migradas
                    try {
                        $solicitacaoManualModel = new \App\Models\SolicitacaoManual();
                        $naoMigradas = count($solicitacaoManualModel->getNaoMigradas(999));
                    ?>
                        <span id="badge-solicitacoes-manuais" class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-yellow-800 bg-yellow-200 rounded-full <?= $naoMigradas > 0 ? '' : 'hidden' ?>">
                            <?= $naoMigradas ?>
                        </span>
                    <?php 
                    } catch (\Exception $e) {
                        // Silencioso se der erro
                    ?>
                        <span id="badge-solicitacoes-manuais" class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-yellow-800 bg-yellow-200 rounded-full hidden">0</span>
                    <?php
                    }
                    ?>
                </a>
                <?php endif; ?>
                
                <?php if (temAcessoPagina('templates-whatsapp')): ?>
                <a href="<?= url('admin/templates-whatsapp') ?>" class="flex items-center px-4 py-2 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors <?= $currentPage === 'templates-whatsapp' ? 'bg-blue-50 text-blue-700 font-medium' : '' ?>">
                    <i class="fas fa-file-code mr-3 w-5 text-center"></i>
                    Templates WhatsApp
                </a>
                <?php endif; ?>
                
                <?php if (temAcessoPagina('whatsapp-instances')): ?>
                <a href="<?= url('admin/whatsapp-instances') ?>" class="flex items-center px-4 py-2 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors <?= $currentPage === 'whatsapp-instances' ? 'bg-blue-50 text-blue-700 font-medium' : '' ?>">
                    <i class="fas fa-mobile-alt mr-3 w-5 text-center"></i>
                    Instâncias WhatsApp
                </a>
                <?php endif; ?>
                
                <?php if (temAcessoPagina('imobiliarias') || temAcessoPagina('usuarios') || temAcessoPagina('categorias') || temAcessoPagina('status') || temAcessoPagina('condicoes')): ?>
                <!-- ADMINISTRAÇÃO -->
                <div class="pt-4">
                    <h3 class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Administração</h3>
                </div>
                
                <?php if (temAcessoPagina('imobiliarias')): ?>
                <a href="<?= url('admin/imobiliarias') ?>" class="flex items-center px-4 py-2 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors <?= $currentPage === 'imobiliarias' ? 'bg-blue-50 text-blue-700 font-medium' : '' ?>">
                    <i class="fas fa-building mr-3 w-5 text-center"></i>
                    Imobiliárias
                </a>
                <?php endif; ?>
                
                <?php if (temAcessoPagina('usuarios')): ?>
                <a href="<?= url('admin/usuarios') ?>" class="flex items-center px-4 py-2 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors <?= $currentPage === 'usuarios' ? 'bg-blue-50 text-blue-700 font-medium' : '' ?>">
                    <i class="fas fa-users mr-3 w-5 text-center"></i>
                    Usuários
                </a>
                <?php endif; ?>
                
                <?php if (temAcessoPagina('categorias')): ?>
                <a href="<?= url('admin/categorias') ?>" class="flex items-center px-4 py-2 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors <?= $currentPage === 'categorias' ? 'bg-blue-50 text-blue-700 font-medium' : '' ?>">
                    <i class="fas fa-tags mr-3 w-5 text-center"></i>
                    Categorias
                </a>
                <?php endif; ?>
                
                <?php if (temAcessoPagina('status')): ?>
                <a href="<?= url('admin/status') ?>" class="flex items-center px-4 py-2 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors <?= $currentPage === 'status' ? 'bg-blue-50 text-blue-700 font-medium' : '' ?>">
                    <i class="fas fa-list mr-3 w-5 text-center"></i>
                    Status
                </a>
                <?php endif; ?>
                
                <?php if (temAcessoPagina('condicoes')): ?>
                <a href="<?= url('admin/condicoes') ?>" class="flex items-center px-4 py-2 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors <?= $currentPage === 'condicoes' ? 'bg-blue-50 text-blue-700 font-medium' : '' ?>">
                    <i class="fas fa-tag mr-3 w-5 text-center"></i>
                    Condições/Pendências
                </a>
                <?php endif; ?>
                <?php endif; ?>
                
                <?php if (temAcessoPagina('configuracoes') || temAcessoPagina('cron-jobs') || temAcessoPagina('logs') || temAcessoPagina('migracoes')): ?>
                <!-- SISTEMA -->
                <div class="pt-4">
                    <h3 class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Sistema</h3>
                </div>
                
                <?php if (temAcessoPagina('configuracoes')): ?>
                <a href="<?= url('admin/configuracoes') ?>" class="flex items-center px-4 py-2 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors <?= $currentPage === 'configuracoes' ? 'bg-blue-50 text-blue-700 font-medium' : '' ?>">
                    <i class="fas fa-cog mr-3 w-5 text-center"></i>
                    Configurações
                </a>
                <?php endif; ?>
                
                <?php if (temAcessoPagina('cron-jobs')): ?>
                <a href="<?= url('admin/cron-jobs') ?>" class="flex items-center px-4 py-2 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors <?= $currentPage === 'cron-jobs' ? 'bg-blue-50 text-blue-700 font-medium' : '' ?>">
                    <i class="fas fa-clock mr-3 w-5 text-center"></i>
                    Cron Jobs
                </a>
                <?php endif; ?>
                
                <?php if (temAcessoPagina('logs')): ?>
                <a href="<?= url('admin/logs') ?>" class="flex items-center px-4 py-2 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors <?= $currentPage === 'logs' ? 'bg-blue-50 text-blue-700 font-medium' : '' ?>">
                    <i class="fas fa-file-alt mr-3 w-5 text-center"></i>
                    Visualizador de Logs
                </a>
                <?php endif; ?>
                
                <?php if (temAcessoPagina('migracoes')): ?>
                <a href="<?= url('admin/migracoes') ?>" class="flex items-center px-4 py-2 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors <?= $currentPage === 'migracoes' ? 'bg-blue-50 text-blue-700 font-medium' : '' ?>">
                    <i class="fas fa-database mr-3 w-5 text-center"></i>
                    Migrações
                </a>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </nav>
        
        <div class="w-full p-4 flex-shrink-0 border-t border-gray-200">
            <div class="flex items-center px-4 py-2 bg-gray-50 rounded-lg">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-white text-sm"></i>
                    </div>
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium text-gray-900"><?= $user['nome'] ?? 'Usuário' ?></p>
                    <p class="text-xs text-gray-500"><?= $user['nivel_permissao'] ?? 'OPERADOR' ?></p>
                </div>
                <!-- Botão de sair -->
                <a href="<?= url('logout') ?>" class="ml-2 text-gray-400 hover:text-gray-600">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="lg:ml-64">
        <!-- Top Bar -->
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="flex items-center justify-between px-6 py-4">
                <div class="flex items-center">
                    <button id="sidebar-toggle" class="lg:hidden text-gray-500 hover:text-gray-700">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h2 class="ml-4 text-2xl font-semibold text-gray-900"><?= $pageTitle ?? 'Dashboard' ?></h2>
                </div>
            </div>
        </header>
        
        <!-- Page Content -->
        <main class="px-6 pb-6 pt-6">
            <?php 
            // Mostrar mensagens flash
            if (isset($_SESSION['flash_message'])): 
                $flashType = $_SESSION['flash_type'] ?? 'info';
                $bgColor = match($flashType) {
                    'success' => 'bg-green-50 border-green-200 text-green-700',
                    'error' => 'bg-red-50 border-red-200 text-red-700',
                    'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-700',
                    default => 'bg-blue-50 border-blue-200 text-blue-700'
                };
                $icon = match($flashType) {
                    'success' => 'fa-check-circle',
                    'error' => 'fa-exclamation-circle',
                    'warning' => 'fa-exclamation-triangle',
                    default => 'fa-info-circle'
                };
            ?>
            <div class="mb-6 <?= $bgColor ?> border px-4 py-3 rounded-lg alert-message">
                <div class="flex">
                    <i class="fas <?= $icon ?> mt-1 mr-3"></i>
                    <div><?= htmlspecialchars($_SESSION['flash_message']) ?></div>
                </div>
            </div>
            <?php 
                unset($_SESSION['flash_message'], $_SESSION['flash_type']);
            endif; 
            ?>
            
            <?php if (isset($error) && $error): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                <div class="flex">
                    <i class="fas fa-exclamation-circle mt-1 mr-3"></i>
                    <div><?= $error ?></div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (isset($success) && $success): ?>
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                <div class="flex">
                    <i class="fas fa-check-circle mt-1 mr-3"></i>
                    <div><?= $success ?></div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (isset($errors) && !empty($errors)): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                <div class="flex">
                    <i class="fas fa-exclamation-circle mt-1 mr-3"></i>
                    <div>
                        <ul class="list-disc list-inside">
                            <?php foreach ($errors as $error): ?>
                            <li><?= $error ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?= $content ?? '' ?>
        </main>
    </div>
    
    <!-- Mobile Sidebar Overlay -->
    <div id="sidebar-overlay" class="fixed inset-0 z-40 bg-black bg-opacity-50 hidden lg:hidden"></div>
    
    <!-- Scripts -->
    <script>
        // Sidebar toggle for mobile
        document.getElementById('sidebar-toggle').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        });
        
        // Close sidebar when clicking overlay
        document.getElementById('sidebar-overlay').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        });
        
        // Auto-hide alerts (APENAS mensagens com classe .alert-message)
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert-message');
            alerts.forEach(function(alert) {
                alert.style.transition = 'opacity 0.5s ease-out';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 500);
            });
        }, 5000);
        
        // Atualizar badge de novas solicitações no Kanban
        function atualizarBadgeKanban() {
            fetch('<?= url("admin/kanban/novas-solicitacoes") ?>')
                .then(response => response.json())
                .then(data => {
                    const badge = document.getElementById('kanbanBadge');
                    if (badge) {
                        const count = data.count || 0;
                        if (count > 0) {
                            badge.textContent = count;
                            badge.classList.remove('hidden');
                        } else {
                            badge.classList.add('hidden');
                        }
                    }
                })
                .catch(error => {
                    console.error('Erro ao atualizar badge do Kanban:', error);
                });
        }
        
        // Atualizar badge de solicitações manuais não migradas
        function atualizarBadgeSolicitacoesManuais() {
            fetch('<?= url("admin/solicitacoes-manuais/contagem/api") ?>')
                .then(response => response.json())
                .then(data => {
                    const badge = document.getElementById('badge-solicitacoes-manuais');
                    if (badge && data.success) {
                        const count = data.contagem || 0;
                        if (count > 0) {
                            badge.textContent = count;
                            badge.classList.remove('hidden');
                        } else {
                            badge.classList.add('hidden');
                        }
                    }
                    
                    // Disparar evento customizado para que a página de manuais possa atualizar
                    window.dispatchEvent(new CustomEvent('solicitacoesManuaisAtualizado', { detail: data }));
                })
                .catch(error => {
                    console.error('Erro ao atualizar badge de Solicitações Manuais:', error);
                });
        }
        
        // Atualizar badges ao carregar a página
        atualizarBadgeKanban();
        atualizarBadgeSolicitacoesManuais();
        
        // Atualizar badges a cada 10 segundos
        setInterval(atualizarBadgeKanban, 10000);
        setInterval(atualizarBadgeSolicitacoesManuais, 10000);
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
