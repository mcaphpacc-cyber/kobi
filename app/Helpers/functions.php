<?php

declare(strict_types=1);

/**
 * Escape HTML output.
 */
function e(?string $value): string
{
    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

function env(string $key, mixed $default = null): mixed
{
    static $loaded = false;

    if (!$loaded) {

        $file = dirname(__DIR__, 2) . '/.env';

        if (is_file($file)) {

            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($lines as $line) {

                $line = trim($line);

                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }

                [$name, $value] = array_pad(explode('=', $line, 2), 2, '');

                $_ENV[trim($name)] = trim($value);
            }
        }

        $loaded = true;
    }

    return $_ENV[$key] ?? $default;
}

/**
 * Load configuration values.
 */
function config(?string $key = null): mixed
{
    static $config = null;

    if ($config === null) {
        $config = require dirname(__DIR__) . '/Config/Config.php';
    }

    if ($key === null) {
        return $config;
    }

    return $config[$key] ?? null;
}

/**
 * Get application base URL.
 */
function baseUrl(): string
{
    return rtrim(config('url'), '/')
        . rtrim(config('base_path'), '/');
}

/**
 * Generate application URL.
 */
function url(string $path = ''): string
{
    return baseUrl() . '/' . ltrim($path, '/');
}

/**
 * Generate asset URL.
 */
function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

/**
 * Redirect to another page.
 */
function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

/**
 * Abort the current request with an HTTP status code.
 */
function abort(
    int $statusCode = 404,
    string $message = ''
): never {

    http_response_code($statusCode);

    if ($message !== '') {
        exit($message);
    }

    switch ($statusCode) {

        case 404:
            exit('404 - Page Not Found');

        case 403:
            exit('403 - Forbidden');

        case 500:
            exit('500 - Internal Server Error');

        default:
            exit('HTTP Error ' . $statusCode);
    }


}

function buildPreventionTips(string $text): array
    {
        $tips = preg_split(
            '/[.;]\s*/',
            strip_tags($text)
        );

        $tips = array_filter(
            array_map('trim', $tips)
        );

        return array_slice(
            $tips,
            0,
            6
        );
    }