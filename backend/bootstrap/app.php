<?php

declare(strict_types=1);

use App\Countries\Exceptions\CountryProviderException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // nginx is the single proxy hop in front of php-fpm (which must stay unpublished: only nginx reaches it).
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // The API never redirects: guests get a JSON 401 ({"message":"Unauthenticated."}).
        $exceptions->shouldRenderJsonWhen(
            static fn (Request $request): bool => $request->is('api/*') || $request->expectsJson(),
        );

        // Reported (logged) as usual, but rendered as a clean 503 for the client.
        $exceptions->render(
            static fn (CountryProviderException $e, Request $request) => response()->json(
                ['message' => 'Country data is temporarily unavailable.'],
                503,
            ),
        );
    })->create();
