<?php

declare(strict_types=1);

use App\Core\App;
use App\Core\Logger;

/*
|--------------------------------------------------------------------------
| Bootstrap KOBI
|--------------------------------------------------------------------------
*/

$config = require dirname(__DIR__) . '/app/Config/Config.php';

date_default_timezone_set($config['timezone']);

/*
|--------------------------------------------------------------------------
| PHP Error Reporting
|--------------------------------------------------------------------------
*/

error_reporting(E_ALL);

if ($config['debug']) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
}

/*
|--------------------------------------------------------------------------
| Session Security
|--------------------------------------------------------------------------
*/

$isHttps =
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (
        isset($_SERVER['SERVER_PORT'])
        && (int) $_SERVER['SERVER_PORT'] === 443
    );

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();

/*
|--------------------------------------------------------------------------
| Response Headers
|--------------------------------------------------------------------------
*/

header('Content-Type: text/html; charset=UTF-8');

header(
    "Content-Security-Policy: "
    . "default-src 'self'; "
    . "script-src 'self' 'sha256-HXnvbjhHtEQlezvoFANKV23eYJYHqnX9EjrIQe1jYDw='; "
    . "style-src 'self'; "
    . "img-src 'self' data:; "
    . "font-src 'self'; "
    . "connect-src 'self'; "
    . "object-src 'none'; "
    . "base-uri 'self'; "
    . "frame-ancestors 'none'; "
    . "form-action 'self';"
);

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

header(
    'Permissions-Policy: '
    . 'camera=(), '
    . 'microphone=(), '
    . 'geolocation=(), '
    . 'payment=()'
);

/*
|--------------------------------------------------------------------------
| Uncaught Exception Handler
|--------------------------------------------------------------------------
*/

set_exception_handler(
    static function (Throwable $exception) use ($config): void {
        Logger::exception($exception);

        http_response_code(500);

        if ($config['debug']) {
            echo '<pre>';
            echo htmlspecialchars(
                (string) $exception,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            );
            echo '</pre>';
            return;
        }

        echo 'Something went wrong. Please try again later.';
    }
);

/*
|--------------------------------------------------------------------------
| PHP Error Handler
|--------------------------------------------------------------------------
*/

set_error_handler(
    static function (
        int $severity,
        string $message,
        string $file,
        int $line
    ) use ($config): bool {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        $errorMessage = sprintf(
            '%s in %s:%d',
            $message,
            $file,
            $line
        );

        if ($config['debug']) {
            return false;
        }

        Logger::error($errorMessage);

        return true;
    }
);

/*
|--------------------------------------------------------------------------
| Fatal Error Handler
|--------------------------------------------------------------------------
*/

register_shutdown_function(
    static function () use ($config): void {
        $error = error_get_last();

        if ($error === null) {
            return;
        }

        $fatalTypes = [
            E_ERROR,
            E_PARSE,
            E_CORE_ERROR,
            E_COMPILE_ERROR,
        ];

        if (!in_array($error['type'], $fatalTypes, true)) {
            return;
        }

        $message = sprintf(
            '%s in %s:%d',
            $error['message'],
            $error['file'],
            $error['line']
        );

        Logger::error(
            'Fatal PHP error: ' . $message
        );

        if (headers_sent()) {
            return;
        }

        http_response_code(500);

        if ($config['debug']) {
            echo '<pre>';
            echo htmlspecialchars(
                $message,
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            );
            echo '</pre>';
            return;
        }

        echo 'Something went wrong. Please try again later.';
    }
);

/*
|--------------------------------------------------------------------------
| Start Application
|--------------------------------------------------------------------------
*/

$app = new App();

$app->run();