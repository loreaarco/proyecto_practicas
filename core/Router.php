<?php

/**
 * Router - Sistema de rutas simple y limpio
 *
 * Mapea URIs a controladores/métodos.
 * Soporta parámetros dinámicos tipo {id}.
 */
class Router
{
    private array $routes = [];

    public function get(string $uri, string $controllerAction): void
    {
        $this->addRoute('GET', $uri, $controllerAction);
    }

    public function post(string $uri, string $controllerAction): void
    {
        $this->addRoute('POST', $uri, $controllerAction);
    }

    private function addRoute(string $method, string $uri, string $controllerAction): void
    {
        $this->routes[] = [
            'method'  => $method,
            'pattern' => $this->uriToPattern($uri),
            'action'  => $controllerAction,
        ];
    }

    private function uriToPattern(string $uri): string
    {
        // Convierte {id} → (\d+), {slug} → ([a-z0-9\-]+), {any} → (.+)
        $pattern = preg_replace('/\{id\}/',   '(\d+)',           $uri);
        $pattern = preg_replace('/\{slug\}/', '([a-z0-9\-]+)', $pattern);
        $pattern = preg_replace('/\{any\}/',  '(.+)',           $pattern);
        return '@^' . $pattern . '$@';
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Eliminar el prefijo de instalación (ej. /oscisa/public)
        $base = parse_url(BASE_URL, PHP_URL_PATH);
        if ($base && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }
        $uri = '/' . ltrim($uri, '/');

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;

            if (preg_match($route['pattern'], $uri, $matches)) {
                array_shift($matches); // quitar match completo
                $this->callAction($route['action'], $matches);
                return;
            }
        }

        // Ruta no encontrada
        http_response_code(404);
        include BASE_PATH . '/views/errors/404.php';
    }

    private function callAction(string $action, array $params): void
    {
        [$controllerName, $method] = explode('@', $action);

        $file = BASE_PATH . '/app/Controllers/' . $controllerName . '.php';
        if (!file_exists($file)) {
            throw new RuntimeException("Controlador no encontrado: {$controllerName}");
        }

        require_once $file;
        $controller = new $controllerName();

        if (!method_exists($controller, $method)) {
            throw new RuntimeException("Método no encontrado: {$controllerName}@{$method}");
        }

        call_user_func_array([$controller, $method], $params);
    }
}
