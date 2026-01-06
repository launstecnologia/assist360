<?php
/**
 * Router script para servidor PHP built-in (php -S)
 * Este arquivo é usado quando o servidor não processa .htaccess
 */

// Habilitar logs de erro
ini_set('log_errors', '1');
$logFile = __DIR__ . '/storage/logs/app.log';
if (!is_dir(dirname($logFile))) {
    mkdir(dirname($logFile), 0755, true);
}
ini_set('error_log', $logFile);

// Se o arquivo solicitado existe e não é este router, servir diretamente
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$parsedUrl = parse_url($requestUri);
$path = $parsedUrl['path'] ?? '/';

// Log para debug
error_log("Router.php - Request URI: $requestUri, Path: $path");

// Remover query string para verificação
$pathWithoutQuery = strtok($path, '?');

// Se for um arquivo real (CSS, JS, imagens, etc.), servir diretamente
if ($pathWithoutQuery !== '/' && file_exists(__DIR__ . $pathWithoutQuery) && !is_dir(__DIR__ . $pathWithoutQuery)) {
    error_log("Router.php - Serving file directly: $pathWithoutQuery");
    return false; // Servir arquivo diretamente
}

// Se for um diretório e existir index.php dentro, servir
if (is_dir(__DIR__ . $pathWithoutQuery) && file_exists(__DIR__ . $pathWithoutQuery . '/index.php')) {
    error_log("Router.php - Serving directory index: $pathWithoutQuery");
    return false; // Servir index.php do diretório
}

// Caso contrário, redirecionar tudo para index.php
error_log("Router.php - Routing to index.php for: $pathWithoutQuery");
require __DIR__ . '/index.php';

