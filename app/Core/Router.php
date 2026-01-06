<?php

namespace App\Core;

class Router
{
    private array $routes = [];
    private array $middlewares = [];

    public function get(string $path, callable|string $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post(string $path, callable|string $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    public function put(string $path, callable|string $handler, array $middleware = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middleware);
    }

    public function delete(string $path, callable|string $handler, array $middleware = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    private function addRoute(string $method, string $path, callable|string $handler, array $middleware = []): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middleware' => $middleware
        ];
    }

    public function middleware(string $name, callable $middleware): void
    {
        $this->middlewares[$name] = $middleware;
    }
    
    public function getRoutes(): array
    {
        return $this->routes;
    }
    
    public function getRoutesCount(): int
    {
        return count($this->routes);
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remove a base path se existir (ex: /kss/confirmacao-horario -> /confirmacao-horario)
        $basePath = \App\Core\Url::path();
        
        // Só remover base path se ele realmente existir no path
        if ($basePath && $basePath !== '/' && $basePath !== '') {
            // Verificar se o path começa com o base path
            if (strpos($path, '/' . $basePath . '/') === 0) {
                // Path: /kss/cron/lembretes-peca -> /cron/lembretes-peca
                $path = substr($path, strlen('/' . $basePath));
            } elseif ($path === '/' . $basePath) {
                // Path: /kss -> /
                $path = '/';
            } elseif (strpos($path, '/' . $basePath) === 0 && strlen($path) > strlen('/' . $basePath)) {
                // Path: /kss/cron -> /cron (sem barra final)
                $path = substr($path, strlen('/' . $basePath));
            }
            // Se o path não começa com base path, não fazer nada (pode estar rodando sem base path)
        }
        
        // Garantir que o path começa com /
        if ($path === '' || $path[0] !== '/') {
            $path = '/' . $path;
        }
        
        $path = rtrim($path, '/') ?: '/';

        foreach ($this->routes as $index => $route) {
            // Verificar se o método corresponde
            if ($route['method'] !== $method) {
                continue;
            }
            
            // Verificar match do path
            $matched = $this->matchPath($route['path'], $path);
            
            if ($matched) {
                
                // Executar middlewares
                foreach ($route['middleware'] as $middlewareName) {
                    if (isset($this->middlewares[$middlewareName])) {
                        $result = call_user_func($this->middlewares[$middlewareName]);
                        if ($result === false) {
                            return; // Middleware bloqueou a execução
                        }
                    }
                }

                // Executar handler - capturar RouteContinueException para continuar para próxima rota
                try {
                    $this->executeHandler($route['handler'], $this->extractParams($route['path'], $path));
                    return;
                } catch (\App\Core\RouteContinueException $e) {
                    // Continuar para próxima rota sem retornar erro
                    continue;
                }
            }
        }

        // Rota não encontrada
        http_response_code(404);
        View::render('errors.404');
    }

    private function matchPath(string $routePath, string $requestPath): bool
    {
        // Tratar rota raiz
        if ($routePath === '/' && $requestPath === '/') {
            return true;
        }
        
        // Tratar outras rotas exatas
        if ($routePath === $requestPath) {
            return true;
        }
        
        // Tratar rotas com parâmetros
        $routePattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $routePath);
        $routePattern = '#^' . $routePattern . '$#';
        
        return (bool) preg_match($routePattern, $requestPath);
    }

    private function extractParams(string $routePath, string $requestPath): array
    {
        $params = [];
        $routeSegments = explode('/', trim($routePath, '/'));
        $requestSegments = explode('/', trim($requestPath, '/'));

        foreach ($routeSegments as $index => $segment) {
            if (preg_match('/\{([^}]+)\}/', $segment, $matches)) {
                $paramName = $matches[1];
                $params[$paramName] = $requestSegments[$index] ?? null;
            }
        }

        return $params;
    }

    private function executeHandler(callable|string $handler, array $params): void
    {
        try {
            if (is_callable($handler)) {
                call_user_func_array($handler, $params);
            } elseif (is_string($handler)) {
                // Formato: Controller@method
                if (strpos($handler, '@') !== false) {
                    [$controllerName, $methodName] = explode('@', $handler);
                    $controllerClass = "App\\Controllers\\{$controllerName}";
                    
                    if (class_exists($controllerClass)) {
                        $controller = new $controllerClass();
                        if (method_exists($controller, $methodName)) {
                            // Usar Reflection para mapear parâmetros corretamente
                            $reflection = new \ReflectionMethod($controllerClass, $methodName);
                            $methodParams = $reflection->getParameters();
                            $orderedParams = [];
                            
                            foreach ($methodParams as $param) {
                                $paramName = $param->getName();
                                $paramValue = null;
                                
                                // Tentar encontrar pelo nome do parâmetro
                                if (isset($params[$paramName])) {
                                    $paramValue = $params[$paramName];
                                } elseif (!empty($params)) {
                                    // Se não encontrar pelo nome, usar valores na ordem
                                    $paramValue = array_shift($params);
                                } elseif ($param->isDefaultValueAvailable()) {
                                    $paramValue = $param->getDefaultValue();
                                } else {
                                    $paramValue = null;
                                }
                                
                                // Converter para o tipo esperado se o parâmetro tiver type hint
                                if ($paramValue !== null && $param->hasType()) {
                                    $paramType = $param->getType();
                                    if ($paramType instanceof \ReflectionNamedType && !$paramType->isBuiltin()) {
                                        // Tipo de classe, não converter
                                    } elseif ($paramType instanceof \ReflectionNamedType) {
                                        $typeName = $paramType->getName();
                                        if ($typeName === 'int' && is_numeric($paramValue)) {
                                            $paramValue = (int)$paramValue;
                                        } elseif ($typeName === 'float' && is_numeric($paramValue)) {
                                            $paramValue = (float)$paramValue;
                                        } elseif ($typeName === 'bool') {
                                            $paramValue = filter_var($paramValue, FILTER_VALIDATE_BOOLEAN);
                                        }
                                    }
                                }
                                
                                $orderedParams[] = $paramValue;
                            }
                            
                            call_user_func_array([$controller, $methodName], $orderedParams);
                        } else {
                            throw new \Exception("Método $methodName não encontrado no controller $controllerName");
                        }
                    } else {
                        throw new \Exception("Controller $controllerName não encontrado");
                    }
                }
            }
        } catch (\Throwable $e) {
            // ✅ Se for uma requisição JSON (API), retornar JSON em vez de HTML
            $isJsonRequest = (
                isset($_SERVER['HTTP_ACCEPT']) && 
                strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false
            ) || (
                isset($_SERVER['CONTENT_TYPE']) && 
                strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false
            );
            
            if ($isJsonRequest) {
                // Limpar buffers
                while (ob_get_level() > 0) {
                    ob_end_clean();
                }
                
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'error' => 'Erro interno: ' . $e->getMessage()
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Se não for JSON, re-lançar a exceção para o handler padrão
            throw $e;
        }
    }

    public function url(string $name, array $params = []): string
    {
        // Implementar geração de URLs por nome (opcional)
        return '';
    }
}
