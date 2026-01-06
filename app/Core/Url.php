<?php

namespace App\Core;

class Url
{
    private static ?string $baseUrl = null;
    private static ?string $basePath = null;
    
    public static function init(): void
    {
        $config = require __DIR__ . '/../Config/config.php';
        // Limpar barras invertidas e normalizar
        self::$baseUrl = str_replace('\\', '', $config['app']['url']);
        self::$basePath = str_replace('\\', '/', trim($config['app']['base_path'], '/\\'));
    }
    
    public static function base(): string
    {
        if (self::$baseUrl === null) {
            self::init();
        }
        
        return rtrim(self::$baseUrl, '/');
    }
    
    public static function path(): string
    {
        if (self::$basePath === null) {
            self::init();
        }
        
        // Limpar e normalizar base path
        $path = str_replace('\\', '/', self::$basePath);
        $path = trim($path, '/');
        
        // Retornar o base path sem a barra inicial
        return $path === '/' || $path === '' ? '' : $path;
    }
    
    public static function to(string $path = ''): string
    {
        // Detectar URL base automaticamente
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        // Se estiver na porta 8000 (desenvolvimento), usar porta 8000
        if (strpos($host, ':8000') !== false || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 8000)) {
            $host = 'localhost:8000';
        }
        
        $base = $protocol . '://' . $host;
        $pathPrefix = self::path();
        
        // Se estiver em produção (sem pasta), usar apenas a URL base
        if ($pathPrefix === '' || $pathPrefix === '/') {
            return $base . '/' . ltrim($path, '/');
        }
        
        // Se estiver em desenvolvimento (com pasta), usar base_path
        return $base . '/' . ltrim($path, '/');
    }
    
    public static function asset(string $path): string
    {
        return self::to('Public/assets/' . ltrim($path, '/'));
    }
    
    public static function current(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        return $protocol . '://' . $host . $uri;
    }
    
    public static function previous(): string
    {
        return $_SERVER['HTTP_REFERER'] ?? self::to();
    }
    
    public static function route(string $name, array $params = []): string
    {
        // Implementar sistema de rotas nomeadas se necessário
        return self::to($name);
    }
    
    /**
     * Retorna a URL da logo da KSS
     */
    public static function kssLogo(): string
    {
        $logoPath = 'Public/assets/images/kss/logo.png';
        $fullPath = __DIR__ . '/../../' . $logoPath;
        
        if (file_exists($fullPath)) {
            return self::to($logoPath);
        }
        
        // Se não existir, retorna null para usar fallback de texto
        return '';
    }
}
