<?php

declare(strict_types=1);

// Same-origin SPA + API behind nginx: no path is CORS-enabled, so no Access-Control-* headers are sent.
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
