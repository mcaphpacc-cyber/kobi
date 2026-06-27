<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

class View
{
    public static function render(
        string $view,
        array $data = [],
        string $layout = 'app'
    ): void {

        $viewsPath = dirname(__DIR__) . '/Views/';

        $viewFile = $viewsPath . $view . '.php';

        if (!file_exists($viewFile)) {
            throw new RuntimeException("View not found: {$view}");
        }

        $layoutFile = $viewsPath . 'layouts/' . $layout . '.php';

        if (!file_exists($layoutFile)) {
            throw new RuntimeException("Layout not found: {$layout}");
        }

        extract($data, EXTR_SKIP);

        ob_start();

        require $viewFile;

        $content = ob_get_clean();

        require $layoutFile;
    }
}