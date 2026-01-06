<?php
/**
 * KSS Seguros - Sistema de Assistências Residenciais
 * Front Controller - Ponto de entrada principal
 */

// Inicializar sessão
session_start();

// Carregar configurações
$config = require __DIR__ . '/app/Config/config.php';

// Configuração de ambiente
define('ENVIRONMENT', env('APP_ENVIRONMENT', 'development'));
define('DEBUG', env('APP_DEBUG', 'true') === 'true');
define('FOLDER', env('APP_BASE_PATH', '/kss'));
define('URL', env('APP_URL', 'http://localhost' . FOLDER));

// Configurar diretório e arquivo de log do PHP
$logDir = __DIR__ . '/storage/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}
$phpErrorLog = $logDir . '/app.log';
if (!file_exists($phpErrorLog)) {
    touch($phpErrorLog);
}
ini_set('log_errors', '1');
ini_set('error_log', $phpErrorLog);

// Configuração de segurança
if (ENVIRONMENT === 'production') {
    ini_set('display_errors', 0);
} else {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

// Configurar timezone
$timezone = env('APP_TIMEZONE', 'America/Sao_Paulo');
$validTimezones = [
    'America/Sao_Paulo', 'America/Campo_Grande', 'America/Cuiaba',
    'America/Fortaleza', 'America/Maceio', 'America/Manaus',
    'America/Recife', 'America/Rio_Branco', 'America/Santarem',
    'America/Bahia', 'America/Noronha'
];

if (in_array($timezone, $validTimezones)) {
    date_default_timezone_set($timezone);
} else {
    date_default_timezone_set('America/Sao_Paulo');
}

// Carregar autoloader do Composer
require_once __DIR__ . '/vendor/autoload.php';

// Carregar helpers globais
require_once __DIR__ . '/app/helpers.php';

// Inicializar sistema de URLs
\App\Core\Url::init();

// Configurar banco de dados
\App\Core\Database::setConfig($config['database']);

// Configurar dados globais da view
\App\Core\View::setGlobalData([
    'app' => $config['app'],
    'user' => $_SESSION['user'] ?? null,
    'user_level' => $_SESSION['user_level'] ?? null,
    'csrf_token' => \App\Core\View::csrfToken(),
]);

// Inicializar aplicação
try {
    $router = new \App\Core\Router();
    
    // Carregar rotas
    require __DIR__ . '/app/Config/routes.php';
    
    // Processar requisição
    $router->dispatch();
    
} catch (Exception $e) {
    // ✅ Limpar buffers
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    // ✅ Verificar se é requisição JSON/API
    $isJsonRequest = (
        isset($_SERVER['HTTP_ACCEPT']) && 
        strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false
    ) || (
        isset($_SERVER['CONTENT_TYPE']) && 
        strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false
    );
    
    if ($isJsonRequest) {
        // Retornar JSON para requisições API
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => 'Erro interno: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Para requisições HTML, mostrar erro normalmente
    if (DEBUG) {
        echo '<h1>Erro na Aplicação</h1>';
        echo '<p><strong>Mensagem:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<p><strong>Arquivo:</strong> ' . htmlspecialchars($e->getFile()) . '</p>';
        echo '<p><strong>Linha:</strong> ' . $e->getLine() . '</p>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        http_response_code(500);
        echo '<h1>Erro Interno do Servidor</h1>';
        echo '<p>Ocorreu um erro inesperado. Tente novamente mais tarde.</p>';
    }
    
    // Log do erro
    error_log("Erro na aplicação: " . $e->getMessage() . " em " . $e->getFile() . " linha " . $e->getLine());
}