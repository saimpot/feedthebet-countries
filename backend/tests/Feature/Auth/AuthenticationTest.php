<?php

declare(strict_types=1);

use App\Countries\Contracts\CountryProvider;
use App\Countries\Exceptions\CountryProviderException;
use Auth0\Laravel\Guards\GuardContract;
use Auth0\Laravel\Traits\ActingAsAuth0User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

uses(ActingAsAuth0User::class);

function actingAsSessionUser(object $test): void
{
    $test->actingAsAuth0User(
        ['sub' => 'auth0|123', 'name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'picture' => 'https://example.com/ada.png', 'extra' => 'hidden'],
        'auth0-session',
        GuardContract::SOURCE_SESSION,
    );
}

it('keeps the health endpoint public', function (): void {
    $this->getJson('/api/health')->assertOk()->assertExactJson(['status' => 'ok']);
});

it('returns a JSON 401 for guests on /api/me', function (): void {
    $this->getJson('/api/me')->assertUnauthorized()->assertExactJson(['message' => 'Unauthenticated.']);
});

it('returns a JSON 401 instead of a redirect for guests without an Accept header', function (string $uri): void {
    $response = $this->get($uri);

    $response->assertUnauthorized()->assertExactJson(['message' => 'Unauthenticated.']);
    expect($response->headers->has('Location'))->toBeFalse();
})->with(['/api/me', '/api/countries']);

it('returns the authenticated user on /api/me', function (): void {
    actingAsSessionUser($this);

    $this->getJson('/api/me')->assertOk()->assertExactJson([
        'data' => [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'picture' => 'https://example.com/ada.png',
        ],
    ]);
});

it('redirects /login to the Auth0 authorize endpoint', function (): void {
    config()->set('auth0.guards.default.domain', 'tenant.example.auth0.com');
    config()->set('auth0.guards.default.clientId', 'client-id');
    config()->set('auth0.guards.default.clientSecret', 'client-secret');
    config()->set('auth0.guards.web.cookieSecret', 'test-cookie-secret');
    config()->set('auth0.guards.web.redirectUri', 'http://localhost:8080/callback');

    $response = $this->get('/login');

    $response->assertRedirect();
    $location = (string) $response->headers->get('Location');
    expect($location)
        ->toStartWith('https://tenant.example.auth0.com/authorize?')
        ->toContain('client_id=client-id')
        ->toContain(rawurlencode('http://localhost:8080/callback'));
});

it('registers explicit auth routes and disables SDK auto-registration', function (): void {
    expect(config('auth0.registerAuthenticationRoutes'))->toBeFalse()
        ->and(config('auth.defaults.guard'))->toBe('auth0-session');

    foreach (['login' => '/login', 'callback' => '/callback', 'logout' => '/logout'] as $name => $uri) {
        expect(Route::getRoutes()->getByName($name)?->uri())->toBe(ltrim($uri, '/'));
    }
});

it('protects the api routes with auth and throttle middleware', function (string $name): void {
    $middleware = Route::getRoutes()->getByName($name)->gatherMiddleware();

    expect($middleware)->toContain('auth')->toContain('throttle:60,1');
})->with(['api.me', 'api.countries']);

it('maps a country provider failure to a JSON 503', function (): void {
    $this->app->bind(CountryProvider::class, fn () => new class implements CountryProvider
    {
        public function all(): Collection
        {
            throw new CountryProviderException('upstream down');
        }
    });
    actingAsSessionUser($this);

    $this->getJson('/api/countries')
        ->assertStatus(503)
        ->assertExactJson(['message' => 'Country data is temporarily unavailable.']);
});

it('redirects /logout to the Auth0 logout endpoint', function (): void {
    config()->set('auth0.guards.default.domain', 'tenant.example.auth0.com');
    config()->set('auth0.guards.default.clientId', 'client-id');
    config()->set('auth0.guards.default.clientSecret', 'client-secret');
    config()->set('auth0.guards.web.cookieSecret', 'test-cookie-secret');
    config()->set('auth0.guards.web.redirectUri', 'http://localhost:8080/callback');
    actingAsSessionUser($this);

    $response = $this->get('/logout');

    $response->assertRedirect();
    expect((string) $response->headers->get('Location'))->toStartWith('https://tenant.example.auth0.com/v2/logout?');
});
