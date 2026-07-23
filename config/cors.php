<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter(array_unique([
        env('FRONTEND_URL', 'http://localhost:3000'),
        // Always allow local dev origins
        'http://localhost:3000',
        'http://localhost:3001',
        'http://127.0.0.1:3000',
        // Allow both www and non-www of the production frontend
    ])),

    'allowed_origins_patterns' => [], // Usually not needed if `allowed_origins` covers everything

    'allowed_headers' => ['*'],

    'exposed_headers' => ['*'],

    'max_age' => 0,

    'supports_credentials' => true,

];

