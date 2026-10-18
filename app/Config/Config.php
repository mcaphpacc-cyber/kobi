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

    'api_auth' => [
        // Unset key fails closed. Provision separately; never reuse another secret.
        'rate_key' => env('API_AUTH_RATE_KEY', ''),
    ],

    'treatment_governance' => [
        'publication_mode' => env('TREATMENT_PUBLICATION_MODE', 'hold'),
        // Deployment-controlled positive user IDs; no grants are inferred.
        'permission_admin_user_ids' => [],
    ],

    'medicine_governance' => [
        // Owner-approved deployment settings; no real personnel or sources seeded.
        'owner_user_ids' => [],
        // Explicit owner decision; inert until authenticated one-time bootstrap is committed.
        'owner_bootstrap' => ['owner_user_id'=>1,'administrator_user_id'=>1,'decision_reference'=>'owner-account-1-choice-a-2026-10-10','policy_version'=>'initial-owner-v1'],
        'registry_operator_user_ids' => [],
        'deployment_operator_user_ids' => [],
        'pilot_enabled' => false,
        // Intake permission is separate from internal availability after approval.
        'pilot_internal_enabled' => false,
        'pilot_activation' => [],
        'pilot_code' => '',
        'pilot_limit' => 10,
        'pilot_substances' => [],
        'pilot_actions' => ['register_reference', 'create_identity'],
        'authoritative_tiers' => [1, 2],
        'evidence_sources' => [],
        // Empty until genuine professional verification is separately documented and authorized.
        'reviewer_verifications' => [],
    ],

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
