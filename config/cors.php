<?php

return [

    // 'paths' => [
    //     'api/*',
    //     'sanctum/csrf-cookie'
    // ],
    'paths' => ['*'],


    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:3000',
        'http://127.0.0.1:8000/api/',
        'https://app.cryprtorq.com.ng/public/api/',

    ],
 
    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    // 'exposed_headers' => [],
    'exposed_headers' => ['Authorization'],

    'max_age' => 0,

    'supports_credentials' => false,
];
