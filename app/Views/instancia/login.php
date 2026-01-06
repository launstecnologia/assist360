<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= htmlspecialchars($imobiliaria['nome'] ?? 'KSS') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: <?= $imobiliaria['cor_primaria'] ?? '#3B82F6' ?>;
            --secondary-color: <?= $imobiliaria['cor_secundaria'] ?? '#1E40AF' ?>;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full space-y-8">
        <div class="text-center">
            <?php if (!empty($imobiliaria['logo'])): ?>
                <img class="mx-auto h-20 w-auto" src="<?= url('Public/uploads/logos/' . $imobiliaria['logo']) ?>" alt="<?= htmlspecialchars($imobiliaria['nome']) ?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="mx-auto h-20 w-20 bg-blue-600 rounded-full flex items-center justify-center" style="display: none;">
                    <i class="fas fa-building text-white text-2xl"></i>
                </div>
            <?php else: ?>
                <div class="mx-auto h-20 w-20 bg-blue-600 rounded-full flex items-center justify-center">
                    <i class="fas fa-building text-white text-2xl"></i>
                </div>
            <?php endif; ?>
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                <?= htmlspecialchars($imobiliaria['nome'] ?? 'KSS Seguros') ?>
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                Portal do Locatário
            </p>
        </div>

        <div class="bg-white py-8 px-6 shadow rounded-lg">
            <?php if (isset($error)): ?>
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form class="space-y-6" method="POST" action="/<?= htmlspecialchars($imobiliaria['instancia']) ?>/login">
                <div>
                    <label for="cpf" class="block text-sm font-medium text-gray-700">
                        CPF
                    </label>
                    <div class="mt-1">
                        <input id="cpf" name="cpf" type="text" required 
                               class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               placeholder="000.000.000-00">
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between">
                        <label for="senha" class="block text-sm font-medium text-gray-700">
                            Senha
                        </label>
                        <?php if (!empty($imobiliaria['url_base'])): ?>
                            <?php 
                            $urlBase = rtrim($imobiliaria['url_base'], '/');
                            $urlEsqueciSenha = $urlBase . '/kurole_include/ksi/clientes/acesso/login/#abrirEsqueciSenha';
                            ?>
                            <a href="<?= htmlspecialchars($urlEsqueciSenha) ?>" 
                               target="_blank"
                               class="text-sm text-blue-600 hover:text-blue-800">
                                Esqueci a Senha
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="mt-1">
                        <input id="senha" name="senha" type="password" required 
                               class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               placeholder="Sua senha">
                    </div>
                </div>

                <div>
                    <button type="submit" 
                            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white focus:outline-none focus:ring-2 focus:ring-offset-2"
                            style="background-color: var(--primary-color);">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <i class="fas fa-sign-in-alt text-white"></i>
                        </span>
                        Entrar
                    </button>
                </div>
            </form>

            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Problemas para acessar? Entre em contato com a administração.
                </p>
            </div>
        </div>

        <div class="text-center">
            <a href="/" class="text-sm text-gray-600 hover:text-gray-900">
                <i class="fas fa-arrow-left mr-1"></i>
                Voltar ao início
            </a>
        </div>
    </div>

    <script>
        // Máscara para CPF
        document.getElementById('cpf').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            e.target.value = value;
        });
    </script>
</body>
</html>
















