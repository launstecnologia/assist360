<?php
/**
 * EducaTudo - Sistema Educacional Single-Tenant
 * Front Controller - Ponto de entrada principal
 */

// Iniciar sessão
session_start();

// Configuração da pasta base dinâmica
define('FOLDER', '/educatudo');
define('URL', 'http://localhost' . FOLDER);

// Configuração de ambiente
define('ENVIRONMENT', 'development'); // development, production
define('DEBUG', ENVIRONMENT === 'development');

// Configuração de segurança
if (ENVIRONMENT === 'production') {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/storage/logs/error.log');
} else {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

// Autoloader simples
spl_autoload_register(function ($class) {
    // Remove namespace se existir
    $class = str_replace('\\', '/', $class);
    
    // Tenta diferentes caminhos
    $paths = [
        __DIR__ . '/app/' . $class . '.php',
        __DIR__ . '/app/Core/' . $class . '.php',
        __DIR__ . '/app/Controllers/' . $class . '.php',
        __DIR__ . '/app/Models/' . $class . '.php',
        __DIR__ . '/app/Middleware/' . $class . '.php'
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Inicializar aplicação
try {
    require_once __DIR__ . '/app/Core/App.php';
    $app = new App();
    $app->run();
} catch (Exception $e) {
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