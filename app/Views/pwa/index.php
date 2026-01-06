<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $app['name'] ?> - Locatário</title>
    
    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#2563eb">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="<?= $app['name'] ?>">
</head>
<body class="bg-white min-h-screen flex items-center justify-center">
    <!-- Logo KSS Centralizada -->
    <div class="flex justify-center items-center">
        <?= kss_logo('', 'KSS ASSISTÊNCIA 360°', 60) ?>
    </div>
</body>
</html>
