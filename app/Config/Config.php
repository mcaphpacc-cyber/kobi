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

];