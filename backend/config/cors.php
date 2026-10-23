<?php

declare(strict_types=1);

/*
 | The SPA and the API are served from the same origin (nginx on :8080), so
 | cross-origin requests are never needed. No paths are CORS-enabled, which
 | means no Access-Control-* headers are emitted at all.
 */
return [
    'paths' => [],
    'allowed_methods' => ['*'],
    'allowed_origins' => [],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
