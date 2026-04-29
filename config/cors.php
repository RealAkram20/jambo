<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    // Narrow to the production origin (and localhost dev hosts). A
    // wildcard origin combined with credentials is fatal; even
    // credentialless wildcard leaks API metadata to any origin. Add
    // additional origins here as the frontend deploys to staging /
    // preview hosts. The CORS_ALLOWED_ORIGINS env var lets ops widen
    // this without a code deploy.
    'allowed_origins' => array_filter(array_merge(
        [
            env('APP_URL', 'http://localhost'),
            'https://jambofilms.com',
            'http://localhost',
            'http://127.0.0.1',
        ],
        array_map('trim', explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))),
    )),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
