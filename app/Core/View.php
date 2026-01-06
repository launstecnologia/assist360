<?php

namespace App\Core;

class View
{
    private static string $basePath = 'app/Views/';
    private static array $globalData = [];

    public static function setGlobalData(array $data): void
    {
        self::$globalData = array_merge(self::$globalData, $data);
    }

    public static function render(string $view, array $data = []): void
    {
        $data = array_merge(self::$globalData, $data);
        
        // Extrair variáveis para o escopo da view
        extract($data);
        
        $viewPath = self::$basePath . str_replace('.', '/', $view) . '.php';
        
        if (!file_exists($viewPath)) {
            throw new \Exception("View não encontrada: $view");
        }
        
        include $viewPath;
    }

    public static function renderPartial(string $partial, array $data = []): string
    {
        $data = array_merge(self::$globalData, $data);
        extract($data);
        
        $partialPath = self::$basePath . 'partials/' . str_replace('.', '/', $partial) . '.php';
        
        if (!file_exists($partialPath)) {
            throw new \Exception("Partial não encontrado: $partial");
        }
        
        ob_start();
        include $partialPath;
        return ob_get_clean();
    }

    public static function escape(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    public static function url(string $path = ''): string
    {
        return \App\Core\Url::to($path);
    }
    
    public static function asset(string $path): string
    {
        return \App\Core\Url::asset($path);
    }

    public static function csrfToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function csrfField(): string
    {
        return '<input type="hidden" name="_token" value="' . self::csrfToken() . '">';
    }

    public static function verifyCsrfToken(string $token): bool
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
