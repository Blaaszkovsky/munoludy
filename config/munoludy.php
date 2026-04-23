<?php

return [
    'admin_path' => env('ADMIN_PANEL_PATH', 'admin'),

    'user_com' => [
        'base_url' => env('USER_COM_BASE_URL', 'https://kicket.user.com'),
        'api_key' => env('USER_COM_API_KEY'),
        'timeout' => 10,
        'retry_times' => 3,
        'retry_sleep' => 1000,
        'voted_tag_id' => env('USER_COM_VOTED_TAG_ID', 189),
        'marketing_attribute_name' => env('USER_COM_MARKETING_ATTRIBUTE', 'Marketing email'),
    ],

    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
    ],

    'rate_limits' => [
        'registration_per_hour' => 3,
        'code_attempts_per_5min' => 5,
    ],

    'vote_session_ttl_minutes' => 60,
];
