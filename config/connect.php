<?php

declare(strict_types=1);

return [
    'rate_limits' => [
        'publish_per_minute' => (int) env('CONNECT_RATE_LIMIT_PUBLISH_PER_MINUTE', 600),
        'default_per_minute' => (int) env('CONNECT_RATE_LIMIT_DEFAULT_PER_MINUTE', 180),
        'delegated_per_minute' => (int) env('CONNECT_RATE_LIMIT_DELEGATED_PER_MINUTE', 6000),
        'login_per_ip' => (int) env('CONNECT_RATE_LIMIT_LOGIN_PER_IP', 300),
        'login_per_user_ip' => (int) env('CONNECT_RATE_LIMIT_LOGIN_PER_USER_IP', 30),
    ],
];
