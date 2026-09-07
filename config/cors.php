<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | The API is consumed by the ParishHub SPA (bearer-token auth, so no
    | credentialed cookies). FRONTEND_URL may hold a comma-separated list of
    | allowed origins, e.g. the Vercel production URL plus a custom domain:
    |
    |   FRONTEND_URL=https://parishhub.vercel.app,https://chms.stferdinandboystown.com
    |
    | Set FRONTEND_URL_PATTERN to additionally allow Vercel preview builds,
    | e.g. FRONTEND_URL_PATTERN="#^https://parish-hub-connect-.*\.vercel\.app$#"
    |
    */

    'paths' => ['api/*', 'up'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter(array_merge(
        array_map('trim', explode(',', (string) env('FRONTEND_URL', ''))),
        env('APP_ENV') === 'local' ? ['http://localhost:8080', 'http://127.0.0.1:8080'] : []
    ))),

    'allowed_origins_patterns' => array_values(array_filter([
        env('FRONTEND_URL_PATTERN'),
    ])),

    'allowed_headers' => ['*'],

    'exposed_headers' => ['Content-Disposition'],

    'max_age' => 0,

    'supports_credentials' => false,

];
