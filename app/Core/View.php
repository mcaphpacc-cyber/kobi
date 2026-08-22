<?php

declare(strict_types=1);

namespace App\Core;

use App\Services\AuthService;
use RuntimeException;

class View
{
    private static ?Container $container = null;

    /**
     * Set the application container used by the view layer.
     */
    public static function setContainer(
        Container $container
    ): void {
        self::$container = $container;
    }

    public static function render(
        string $view,
        array $data = [],
        string $layout = 'app'
    ): void {

        $viewsPath = dirname(__DIR__) . '/Views/';

        $viewFile = $viewsPath . $view . '.php';

        if (!file_exists($viewFile)) {
            throw new RuntimeException(
                "View not found: {$view}"
            );
        }

        $layoutFile =
            $viewsPath .
            'layouts/' .
            $layout .
            '.php';

        if (!file_exists($layoutFile)) {
            throw new RuntimeException(
                "Layout not found: {$layout}"
            );
        }

        /*
         * Make authentication available to all views.
         */
        $auth = null;

        if (self::$container !== null) {
            $auth = self::$container->get(
                AuthService::class
            );
        }

        extract(
            array_merge(
                [
                    'auth' => $auth
                ],
                $data
            ),
            EXTR_SKIP
        );

        ob_start();

        require $viewFile;

        $content = ob_get_clean();

        require $layoutFile;
    }

    /**
     * Render a reusable view component.
     */
    public static function component(
        string $component,
        array $data = []
    ): void {
        $componentsPath =
            dirname(__DIR__) . '/Views/';

        $componentFile =
            $componentsPath .
            $component .
            '.php';

        if (!file_exists($componentFile)) {
            throw new RuntimeException(
                "Component not found: {$component}"
            );
        }

        extract(
            $data,
            EXTR_SKIP
        );

        require $componentFile;
    }
}