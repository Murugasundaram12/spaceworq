<?php

$configuredOrigins = array_values(array_filter(array_map(
    static fn (string $origin): string => trim($origin),
    explode(',', (string) env('SPACEWORQ_CORS_ALLOWED_ORIGINS', 'http://localhost,http://127.0.0.1,http://localhost:8000,http://127.0.0.1:8000'))
)));

return [
    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => $configuredOrigins,

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'Idempotency-Key',
        'Origin',
        'X-CSRF-TOKEN',
        'X-Provider-Id',
        'X-Requested-With',
    ],

    'exposed_headers' => ['Content-Disposition'],

    'max_age' => 3600,

    'supports_credentials' => true,
];
