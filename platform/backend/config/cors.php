<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which cross-origin requests are allowed. The storefront at
    | honeybee.net.in must be in CORS_ALLOWED_ORIGINS or all requests from
    | it will be blocked by the browser.
    |
    | Env vars to set in production:
    |   CORS_ALLOWED_ORIGINS=https://honeybee.net.in,https://www.honeybee.net.in
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_filter(
        explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000,http://localhost:3001'))
    ),

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Content-Type',
        'Accept',
        'Authorization',
        'X-Store-ID',
        'X-Requested-With',
    ],

    'exposed_headers' => [],

    'max_age' => 3600,

    // Required for Sanctum cookie-based auth (if ever used)
    'supports_credentials' => false,

];
