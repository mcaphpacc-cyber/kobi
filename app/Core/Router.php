<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    /**
     * @var array<string, array<string, array{controller:string, method:string}>>
     */
    private array $routes = [];

    public function get(string $uri, string $controller, string $method): void
    {
        $this->routes['GET'][$this->normalize($uri)] = [
            'controller' => $controller,
            'method'     => $method,
        ];
    }

    public function dispatch(string $httpMethod, string $uri): void
    {
        $uri = $this->normalize($uri);

        if (!isset($this->routes[$httpMethod][$uri])) {
            http_response_code(404);
            echo '<h1>404 - Page Not Found</h1>';
            return;
        }

        $route = $this->routes[$httpMethod][$uri];

        $controller = new $route['controller']();

        $method = $route['method'];

        $controller->$method();
    }

    private function normalize(string $uri): string
    {
        $uri = parse_url($uri, PHP_URL_PATH) ?? '/';

        $basePath = config('base_path');

        if (
            $basePath !== '/'
            && str_starts_with($uri, $basePath)
        ) {
            $uri = substr($uri, strlen($basePath));
        }

        $uri = '/' . trim($uri, '/');

        return $uri === '//' ? '/' : $uri;
    }
}