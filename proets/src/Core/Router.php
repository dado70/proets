<?php
declare(strict_types=1);

namespace ProETS\Core;

class Router
{
    private static array $routes = [];

    public static function get(string $path, array $handler): void
    {
        self::$routes[] = ['GET', $path, $handler];
    }

    public static function post(string $path, array $handler): void
    {
        self::$routes[] = ['POST', $path, $handler];
    }

    public static function any(string $path, array $handler): void
    {
        self::$routes[] = ['GET|POST', $path, $handler];
    }

    public static function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $uri    = rtrim($uri, '/') ?: '/';

        foreach (self::$routes as [$routeMethod, $routePath, $handler]) {
            // Controlla metodo
            if (!in_array($method, explode('|', $routeMethod))) continue;

            // Converti path in regex (es: /prima-nota/:id → /prima-nota/(\w+))
            $pattern = preg_replace('/\/:([a-zA-Z_]+)/', '/(?P<$1>[^/]+)', $routePath);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $uri, $matches)) {
                // Parametri named
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                [$class, $action] = $handler;
                if (!class_exists($class)) {
                    self::abort(500, "Controller {$class} non trovato");
                }
                $controller = new $class();
                if (!method_exists($controller, $action)) {
                    self::abort(500, "Metodo {$action} non trovato in {$class}");
                }
                $controller->$action($params);
                return;
            }
        }

        self::abort(404);
    }

    public static function abort(int $code, string $message = ''): void
    {
        http_response_code($code);
        $file = PROETS_ROOT . "/templates/errors/{$code}.php";
        if (file_exists($file)) {
            include $file;
        } else {
            echo "<h1>Errore {$code}</h1><p>{$message}</p>";
        }
        exit;
    }

    public static function redirect(string $url, int $code = 302): never
    {
        header("Location: {$url}", true, $code);
        exit;
    }
}
