<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    /**
     * @var array<string, array<int, array>>
     */
    private array $routes = [];

    public function get(string $uri, string $controller, string $method): void
    {
        $this->routes['GET'][] = [
            'uri'        => $this->normalize($uri),
            'controller' => $controller,
            'method'     => $method,
        ];
    }

    public function dispatch(
        string $httpMethod,
        string $uri,
        Container $container
    ): void {

        $uri = $this->normalize($uri);

        foreach ($this->routes[$httpMethod] ?? [] as $route) {

            $pattern = preg_replace(
                '/\{([a-zA-Z0-9_]+)\}/',
                '([^/]+)',
                $route['uri']
            );

            $pattern = '#^' . $pattern . '$#';

            if (!preg_match($pattern, $uri, $matches)) {
                continue;
            }

            array_shift($matches);

            $controller = $container->get($route['controller']);

            call_user_func_array(
                [$controller, $route['method']],
                $matches
            );

            return;
        }

        http_response_code(404);

        echo "<h1>404 - Page Not Found</h1>";
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

    public function post(
        string $uri,
        string $controller,
        string $method
    ): void {

        $this->routes['POST'][] = [
            'uri'        => $this->normalize($uri),
            'controller' => $controller,
            'method'     => $method,
        ];
    }
}