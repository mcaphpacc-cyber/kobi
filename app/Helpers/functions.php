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