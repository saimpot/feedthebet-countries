<?php

declare(strict_types=1);

// Identities come from Auth0 (no local user storage); the "auth0-session" guard and its provider
// are registered by the Auth0 package, see config/auth0.php.

return [

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'auth0-session'),
        'passwords' => null,
    ],

    'guards' => [],

    'providers' => [],

    'passwords' => [],

];
