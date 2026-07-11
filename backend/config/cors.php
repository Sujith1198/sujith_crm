<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CORS (Cross-Origin Resource Sharing) Configuration
    |--------------------------------------------------------------------------
    |
    | The Angular frontend (localhost:4200) needs to call the Laravel API.
    | In production, replace localhost:4200 with your actual frontend domain.
    |
    */

    'paths'                    => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods'          => ['*'],
    'allowed_origins'          => [
        env('FRONTEND_URL', 'http://localhost:4200'),
        'http://crm.toptentopic.com',
        'https://crm.toptentopic.com',
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers'          => ['*'],
    'exposed_headers'          => ['Content-Disposition', 'X-Total-Count'],
    'max_age'                  => 86400,   // 24 hours preflight cache
    'supports_credentials'     => true,
];
