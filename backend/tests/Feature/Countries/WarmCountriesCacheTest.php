<?php

declare(strict_types=1);

use App\Countries\Contracts\CountryProvider;
use App\Countries\Providers\StaticCountryProvider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    config()->set('countries.driver', 'static');
    config()->set('countries.cache.store', 'array');
    Cache::store('array')->flush();
});

it('warms the cache and reports the number of countries', function () {
    $this->artisan('countries:warm')
        ->expectsOutputToContain('Warmed the countries cache with')
        ->assertSuccessful();

    expect(Cache::store('array')->get('countries:v1:static'))->toBeArray()->not->toBeEmpty();
});

it('supports forgetting the cached data first', function () {
    $this->artisan('countries:warm --fresh')->assertSuccessful();

    expect(Cache::store('array')->get('countries:v1:static'))->toBeArray()->not->toBeEmpty();
});

it('works when caching is disabled', function () {
    config()->set('countries.cache.enabled', false);

    expect(app(CountryProvider::class))->toBeInstanceOf(StaticCountryProvider::class);

    $this->artisan('countries:warm')->assertSuccessful();
});

it('fails with a message when the provider is unavailable', function () {
    config()->set('countries.drivers.static.path', '/nonexistent/countries.json');

    $this->artisan('countries:warm')
        ->expectsOutputToContain('Could not warm the countries cache')
        ->assertFailed();
});

it('is scheduled daily', function () {
    $events = collect(app(Schedule::class)->events())
        ->filter(fn ($event) => str_contains($event->command, 'countries:warm'));

    expect($events)->toHaveCount(1)
        ->and($events->first()->expression)->toBe('0 0 * * *');
});
