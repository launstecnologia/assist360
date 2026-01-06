<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página não encontrada - <?= $app['name'] ?></title>
    
    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full text-center">
            <div class="mb-8">
                <div class="mx-auto h-24 w-24 bg-red-100 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-4xl"></i>
                </div>
                <h1 class="text-6xl font-bold text-gray-900 mb-2">404</h1>
                <h2 class="text-2xl font-semibold text-gray-700 mb-4">Página não encontrada</h2>
                <p class="text-gray-600 mb-8">
                    A página que você está procurando não existe ou foi movida.
                </p>
            </div>
            
            <div class="space-y-4">
                <a href="<?= url() ?>" 
                   class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition duration-150 ease-in-out">
                    <i class="fas fa-home mr-2"></i>
                    Voltar ao Início
                </a>
                
                <div class="text-sm text-gray-500">
                    <p>Ou acesse:</p>
                    <div class="mt-2 space-x-4">
                        <a href="<?= url('pwa') ?>" class="text-blue-600 hover:text-blue-800">
                            PWA Locatário
                        </a>
                        <a href="<?= url('operador') ?>" class="text-blue-600 hover:text-blue-800">
                            Operador
                        </a>
                        <a href="<?= url('admin') ?>" class="text-blue-600 hover:text-blue-800">
                            Admin
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
