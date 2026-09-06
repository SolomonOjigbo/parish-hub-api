<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | The API is consumed by the ParishHub SPA (bearer-token auth, so no
    | credentialed cookies). Only the configured frontend origin may call it.
    |
    */

    'paths' => ['api/*', 'up'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter([
        env('FRONTEND_URL'),
        env('APP_ENV') === 'local' ? 'http://localhost:8080' : null,
        env('APP_ENV') === 'local' ? 'http://127.0.0.1:8080' : null,
    ]),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
