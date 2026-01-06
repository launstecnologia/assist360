<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - <?= $app['name'] ?></title>
    
    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full space-y-8">
        <div class="text-center">
            <div class="mx-auto flex items-center justify-center mb-4">
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
                         class="h-24 w-auto max-w-full object-contain"
                         style="display: block;">
                <?php else: ?>
                    <div class="h-20 w-20 bg-blue-600 rounded-full flex items-center justify-center">
                        <i class="fas fa-shield-alt text-white text-2xl"></i>
                    </div>
                <?php endif; ?>
            </div>
            <h2 class="mt-2 text-3xl font-extrabold text-gray-900">
                Recuperar Senha
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                Digite seu email para receber um link de recuperação
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
            
            <?php if (!isset($success)): ?>
            <form class="space-y-6" method="POST" action="<?= url('forgot-password') ?>">
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
                    <button type="submit" 
                            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition duration-150 ease-in-out">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <i class="fas fa-paper-plane text-green-300 group-hover:text-green-200"></i>
                        </span>
                        Enviar Link de Recuperação
                    </button>
                </div>
            </form>
            <?php endif; ?>
            
            <div class="mt-6 text-center">
                <a href="<?= url('login') ?>" class="text-sm font-medium text-blue-600 hover:text-blue-500">
                    <i class="fas fa-arrow-left mr-1"></i>
                    Voltar para o login
                </a>
            </div>
        </div>
        
        <div class="text-center">
            <p class="text-xs text-gray-500">
                © <?= date('Y') ?> <?= $app['name'] ?>. Todos os direitos reservados.
            </p>
        </div>
    </div>
</body>
</html>

