<?php

declare(strict_types=1);

namespace App\Core;

class App
{
    private Router $router;

    private Request $request;

    private Container $container;

    public function __construct()
    {
        $this->router = new Router();

        $this->request = new Request();

        $this->container = new Container();

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

    public function container(): Container
    {
        return $this->container;
    }

    public function run(): void
    {
        $this->router->dispatch(
            $this->request->method(),
            $this->request->uri(),
            $this->container
        );
    }
}