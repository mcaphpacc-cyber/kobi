<?php

declare(strict_types=1);

return [

    'name'       => env('APP_NAME', 'KOBI'),
    'version'    => env('APP_VERSION', '0.1.0'),
    'url'        => env('APP_URL', ''),
    'base_path'  => env('APP_BASE_PATH', ''),
    'timezone'   => env('APP_TIMEZONE', 'UTC'),
    'locale'     => env('APP_LOCALE', 'en'),
    'debug'      => filter_var(
        env('APP_DEBUG', false),
        FILTER_VALIDATE_BOOLEAN
    ),
    
    'storage_path' => env(
        'APP_STORAGE_PATH',
        dirname(__DIR__, 2) . '/storage'
    ),

    'mail' => [
        'driver' => env('MAIL_DRIVER', 'log'),

        'host' => env('MAIL_HOST', ''),
        'port' => (int) env('MAIL_PORT', 587),
        'username' => env('MAIL_USERNAME', ''),
        'password' => env('MAIL_PASSWORD', ''),
        'encryption' => env('MAIL_ENCRYPTION', 'tls'),

        'from_address' => env('MAIL_FROM_ADDRESS', ''),
        'from_name' => env('MAIL_FROM_NAME', 'KOBI'),
    ],

];