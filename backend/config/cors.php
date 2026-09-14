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

    // CORS lo maneja exclusivamente nginx (docker/nginx/default.conf) para este
    // servicio. Dejar 'paths' vacio para que Laravel nunca agregue sus propios
    // headers Access-Control-*: si ambos los agregan, el navegador recibe
    // "Access-Control-Allow-Origin: *, *" (duplicado) y bloquea TODA peticion
    // real, no solo el preflight.
    'paths' => [],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
