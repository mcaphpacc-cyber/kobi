<?php

declare(strict_types=1);

namespace App\Core;

class App
{
    private Router $router;
    private Request $request;

    public function __construct()
    {
        $this->router = new Router();
        $this->request = new Request();

        require dirname(__DIR__, 2) . '/routes/web.php';
    }

    public function router(): Router
    {
        return $this->router;
    }

    public function request(): Request
    {
        return $this->request;
    }

    public function run(): void
    {
        $this->router->dispatch(
            $this->request->method(),
            $this->request->uri()
        );
    }
}