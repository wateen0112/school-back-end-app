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

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    // Explicit allow-list of origins (comma-separated in CORS_ALLOWED_ORIGINS).
    // The wildcard "*" is intentionally NOT permitted so browser clients must
    // come from a known frontend origin.
    'allowed_origins' => array_values(array_filter(array_map('trim', explode(
        ',',
        env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000,http://localhost:5173')
    )))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];