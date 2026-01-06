<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= $app['name'] ?></title>
    
    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-white min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full space-y-8">
        <div class="text-center">
            <div class="mx-auto flex items-center justify-center mb-6">
                <?php 
                // Caminho relativo ao diretório raiz do projeto
                $rootPath = dirname(__DIR__, 3); // Volta 3 níveis: app/Views/auth -> app/Views -> app -> raiz
                $logoPath = $rootPath . '/Public/assets/images/kss/logo.png';
                $logoUrl = url('Public/assets/images/kss/logo.png');
                $logoExists = file_exists($logoPath);
                ?>
                <?php if ($logoExists): ?>
                    <img src="<?= $logoUrl ?>" 
                         alt="KSS Seguros" 
                         class="h-16 w-auto max-w-full object-contain"
                         style="display: block;">
                <?php else: ?>
                    <div class="h-16 w-16 bg-blue-600 rounded-full flex items-center justify-center">
                        <i class="fas fa-shield-alt text-white text-xl"></i>
                    </div>
                <?php endif; ?>
            </div>
            <p class="mt-2 text-sm text-gray-600">
                Faça login para acessar o painel administrativo
            </p>
        </div>
        
        <div class="bg-white py-8 px-6 shadow-lg rounded-lg">
            <?php if (isset($error) && $error): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                <div class="flex">
                    <i class="fas fa-exclamation-circle mt-1 mr-3"></i>
                    <div><?= $error ?></div>
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
            
            <form class="space-y-6" method="POST" action="<?= url('login') ?>">
                <?= \App\Core\View::csrfField() ?>
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">
                        Email
                    </label>
                    <div class="mt-1 relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-400"></i>
                        </div>
                        <input id="email" name="email" type="email" required 
                               value="<?= isset($email) ? htmlspecialchars($email) : '' ?>"
                               class="appearance-none block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               placeholder="seu@email.com">
                    </div>
                </div>
                
                <div>
                    <label for="senha" class="block text-sm font-medium text-gray-700">
                        Senha
                    </label>
                    <div class="mt-1 relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input id="senha" name="senha" type="password" required
                               class="appearance-none block w-full pl-10 pr-10 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               placeholder="Sua senha">
                        <button type="button" 
                                onclick="togglePassword()" 
                                class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <i id="password-icon" class="fas fa-eye text-gray-400 hover:text-gray-600"></i>
                        </button>
                    </div>
                </div>
                
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember-me" name="remember_me" type="checkbox" value="1"
                               class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                        <label for="remember-me" class="ml-2 block text-sm text-gray-900">
                            Lembrar de mim
                        </label>
                    </div>
                    
                    <div class="text-sm">
                        <a href="<?= url('forgot-password') ?>" class="font-medium text-blue-600 hover:text-blue-500">
                            Esqueceu sua senha?
                        </a>
                    </div>
                </div>
                
                <div>
                    <button type="submit" 
                            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-150 ease-in-out">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <i class="fas fa-sign-in-alt text-green-300 group-hover:text-green-200"></i>
                        </span>
                        Entrar
                    </button>
                </div>
            </form>
        </div>
        
        <div class="text-center">
            <p class="text-xs text-gray-500">
                © <?= date('Y') ?> <?= $app['name'] ?>. Todos os direitos reservados.
            </p>
        </div>
    </div>
    
    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('senha');
            const passwordIcon = document.getElementById('password-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.classList.remove('fa-eye');
                passwordIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                passwordIcon.classList.remove('fa-eye-slash');
                passwordIcon.classList.add('fa-eye');
            }
        }
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.bg-red-50');
            alerts.forEach(function(alert) {
                alert.style.transition = 'opacity 0.5s ease-out';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 500);
            });
        }, 5000);
    </script>
</body>
</html>
