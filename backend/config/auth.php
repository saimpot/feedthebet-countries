<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
|
| The application has no local user storage: identities come from Auth0. The
| "auth0-session" guard and its user provider are registered by the Auth0
| package (see config/auth0.php).
|
*/

return [

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'auth0-session'),
        'passwords' => null,
    ],

    'guards' => [],

    'providers' => [],

    'passwords' => [],

];
