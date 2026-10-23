<?php

declare(strict_types=1);

use App\Countries\Contracts\CountryProvider;
use App\Countries\Providers\StaticCountryProvider;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

const WARM_CACHE_KEY = 'countries:v1:static';

beforeEach(function () {
    config()->set('countries.driver', 'static');
    config()->set('countries.cache.store', 'array');
    Cache::store('array')->flush();
});

/**
 * @return Collection<int, Event>
 */
function scheduledWarmEvents(): Collection
{
    return collect(app(Schedule::class)->events())
        ->filter(fn (Event $event) => str_contains((string) $event->command, 'countries:warm'));
}

it('warms the cache and reports the number of countries', function () {
    $this->artisan('countries:warm')
        ->expectsOutputToContain('Warmed the countries cache with')
        ->assertSuccessful();

    expect(Cache::store('array')->get(WARM_CACHE_KEY))->toBeArray()->not->toBeEmpty();
});

it('replaces whatever is cached with fresh data', function () {
    Cache::store('array')->put(WARM_CACHE_KEY, [['code' => 'XX', 'name' => 'Old', 'flagUrl' => 'x', 'flagAlt' => null]], 3600);

    $this->artisan('countries:warm')->assertSuccessful();

    expect(collect(Cache::store('array')->get(WARM_CACHE_KEY))->pluck('code'))->toContain('DE')->not->toContain('XX');
});

it('does nothing when caching is disabled', function () {
    config()->set('countries.cache.enabled', false);

    expect(app(CountryProvider::class))->toBeInstanceOf(StaticCountryProvider::class);

    $this->artisan('countries:warm')
        ->expectsOutputToContain('Caching is disabled; nothing to warm.')
        ->assertSuccessful();

    expect(Cache::store('array')->get(WARM_CACHE_KEY))->toBeNull();
});

it('fails with a message and keeps the previous copy when the provider is unavailable', function () {
    $previous = [['code' => 'XX', 'name' => 'Old', 'flagUrl' => 'x', 'flagAlt' => null]];
    Cache::store('array')->put(WARM_CACHE_KEY, $previous, 3600);
    config()->set('countries.drivers.static.path', '/nonexistent/countries.json');

    $this->artisan('countries:warm')
        ->expectsOutputToContain('Could not warm the countries cache')
        ->assertFailed();

    expect(Cache::store('array')->get(WARM_CACHE_KEY))->toBe($previous);
});

it('is scheduled daily when caching is enabled', function () {
    expect(scheduledWarmEvents())->toHaveCount(1)
        ->and(scheduledWarmEvents()->first()->expression)->toBe('0 0 * * *');
});

it('is not scheduled when caching is disabled', function () {
    putenv('COUNTRIES_CACHE_ENABLED=false');

    try {
        $this->refreshApplication();

        expect(scheduledWarmEvents())->toBeEmpty();
    } finally {
        putenv('COUNTRIES_CACHE_ENABLED');
    }
});
