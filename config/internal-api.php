<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Internal API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the internal service-to-service API used by internal
    | tools such as Discord bots, internal scripts, etc.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Allowed Service Accounts
    |--------------------------------------------------------------------------
    |
    | Comma-separated list of user IDs that are allowed to access the internal API.
    | These should be service accounts like RABot, NOT regular user accounts.
    |
    */
    'allowed_user_ids' => env('INTERNAL_API_ALLOWED_USER_IDS', ''),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limit configuration for internal API endpoints.
    |
    */
    'rate_limit' => [
        'requests' => env('INTERNAL_API_RATE_LIMIT_REQUESTS', 60),
        'minutes' => env('INTERNAL_API_RATE_LIMIT_MINUTES', 1),
    ],
];
