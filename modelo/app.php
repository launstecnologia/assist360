<?php
/**
 * EducaTudo - Configurações da Aplicação
 * Carrega configurações do arquivo .env
 */

// Função para carregar variáveis do .env (apenas se não existir)
if (!function_exists('loadEnv')) {
    function loadEnv($path) {
        if (!file_exists($path)) {
            return [];
        }
        
        $content = file_get_contents($path);
        
        // Remover BOM se presente
        if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
            $content = substr($content, 3);
        }
        
        $lines = explode("\n", $content);
        $env = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }
            
            if (strpos($line, '=') === false) {
                continue;
            }
            
            list($name, $value) = explode('=', $line, 2);
            $env[trim($name)] = trim($value);
        }
        
        return $env;
    }
}

// Carregar variáveis do .env
$env = loadEnv(__DIR__ . '/../.env');

// Função helper para obter variável do .env (apenas se não existir)
if (!function_exists('env')) {
    function env($key, $default = null) {
        global $env;
        return isset($env[$key]) ? $env[$key] : $default;
    }
}

return [
    'app' => [
        'name' => 'EducaTudo',
        'version' => '1.0.0',
        'environment' => ENVIRONMENT,
        'debug' => DEBUG,
        'url' => URL,
        'folder' => FOLDER,
    ],
    
    'database' => [
        'host' => '186.209.113.149',
        'port' => '3306',
        'name' => 'educatudo_bd_educatudo',
        'user' => 'educatudo_bd_educatudo',
        'pass' => '117910Campi!25',
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    ],
    
    'session' => [
        'name' => 'EDUCATUDO_SESSION',
        'lifetime' => 3600, // 1 hora
        'secure' => ENVIRONMENT === 'production',
        'httponly' => true,
        'samesite' => 'Strict',
    ],
    
    'security' => [
        'csrf_token_name' => '_token',
        'password_min_length' => 8,
        'max_login_attempts' => 5,
        'lockout_duration' => 900, // 15 minutos
    ],
    
    'upload' => [
        'max_size' => 10 * 1024 * 1024, // 10MB
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'],
        'path' => __DIR__ . '/../storage/uploads/',
        'chat_path' => __DIR__ . '/../storage/chat/',
    ],
    
    'ai' => [
        'openai_api_key' => env('OPENAI_API_KEY', ''),
        'max_tokens' => 2000,
        'temperature' => 0.7,
    ],
    
    'perfiles' => [
        'admin_escola' => ['diretor', 'coordenador', 'dev'],
        'professor' => [],
        'aluno' => [],
        'pai' => [],
    ],
    
    'routes' => [
        'aluno' => '/',
        'professor' => '/professor',
        'admin' => '/admin',
        'pais' => '/pais',
    ]
];
