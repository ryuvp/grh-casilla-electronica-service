<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    'auth' => [
        'url' => env('AUTH_SERVICE_URL'),
        // Caché de sesión de RemoteAuth. El TTL "fresco" no debe pasar de 15s:
        // cada servicio tiene su propio Redis y no hay invalidación cruzada desde
        // auth-service, así que este TTL es el único control sobre roles/permisos
        // desactualizados en una sesión activa.
        'cache_ttl' => (int) env('REMOTE_AUTH_CACHE_TTL', 15),
        // Ventana de degradación elegante. Si auth-service no responde, se sirve
        // la última identidad conocida hasta este límite en vez de romper con 503.
        // Es un intercambio deliberado: durante una caída de Auth, un token
        // revocado puede seguir siendo aceptado hasta 'stale_ttl' segundos.
        // Poner 0 restaura el comportamiento anterior (fallar con 503).
        'stale_ttl' => (int) env('REMOTE_AUTH_STALE_TTL', 60),
        'timeout' => (int) env('REMOTE_AUTH_TIMEOUT', 5),
        'connect_timeout' => (int) env('REMOTE_AUTH_CONNECT_TIMEOUT', 2),
        // Espera máxima por el lock anti-estampida antes de resolver por cuenta propia.
        'lock_wait' => (int) env('REMOTE_AUTH_LOCK_WAIT', 3),
    ],
];
