<?php

declare(strict_types=1);

use App\Countries\Contracts\CountryProvider;
use App\Countries\Data\Country;
use App\Countries\Exceptions\CountryProviderException;
use App\Countries\Providers\CachedCountryProvider;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Support\Collection;
use Illuminate\Support\Defer\DeferredCallbackCollection;

const FRESH_SECONDS = 60;
const STALE_SECONDS = 600;

/**
 * Inner provider that counts calls, can be told to fail, and returns a new
 * country name per call so tests can tell which copy they were served.
 */
function countingProvider(): object
{
    return new class implements CountryProvider
    {
        public int $calls = 0;

        public bool $failing = false;

        public function all(): Collection
        {
            $this->calls++;

            if ($this->failing) {
                throw CountryProviderException::invalidPayload('fake');
            }

            return collect([new Country('DE', "Germany v{$this->calls}", 'https://example.test/de.svg', 'Flag of Germany')]);
        }
    };
}

function runDeferredCallbacks(): void
{
    app(DeferredCallbackCollection::class)->invoke();
}

beforeEach(function () {
    $this->inner = countingProvider();
    $this->cache = new Repository(new ArrayStore);
    $this->provider = new CachedCountryProvider($this->inner, $this->cache, 'countries:test', FRESH_SECONDS, STALE_SECONDS);
});

it('serves repeated calls from the cache', function () {
    $first = $this->provider->all();
    $second = $this->provider->all();

    expect($this->inner->calls)->toBe(1)
        ->and($second->first())->toBeInstanceOf(Country::class)
        ->and($second->first())->toEqual($first->first());
});

it('stores plain arrays, not objects', function () {
    $this->provider->all();

    expect($this->cache->get('countries:test'))->toBe([
        ['code' => 'DE', 'name' => 'Germany v1', 'flagUrl' => 'https://example.test/de.svg', 'flagAlt' => 'Flag of Germany'],
    ]);
});

it('forgets the cached copy so the next read refetches', function () {
    $this->provider->all();
    $this->provider->forget();

    expect($this->cache->has('countries:test'))->toBeFalse()
        ->and($this->provider->all()->first()->name)->toBe('Germany v2')
        ->and($this->inner->calls)->toBe(2);
});

it('replaces the cached copy on refresh', function () {
    $this->provider->all();

    expect($this->provider->refresh()->first()->name)->toBe('Germany v2')
        ->and($this->provider->all()->first()->name)->toBe('Germany v2')
        ->and($this->inner->calls)->toBe(2);
});

it('keeps the previous copy when a refresh fails', function () {
    $this->provider->all();
    $this->inner->failing = true;

    expect(fn () => $this->provider->refresh())->toThrow(CountryProviderException::class)
        ->and($this->provider->all()->first()->name)->toBe('Germany v1')
        ->and($this->inner->calls)->toBe(2);
});

it('does not cache failures', function () {
    $this->inner->failing = true;

    expect(fn () => $this->provider->all())->toThrow(CountryProviderException::class)
        ->and($this->cache->has('countries:test'))->toBeFalse();
});

describe('stale-while-revalidate', function () {
    it('serves from the cache without refetching while fresh', function () {
        $this->provider->all();

        $this->travel(FRESH_SECONDS - 1)->seconds();
        $served = $this->provider->all();
        runDeferredCallbacks();

        expect($served->first()->name)->toBe('Germany v1')
            ->and($this->inner->calls)->toBe(1);
    });

    it('serves the stale copy immediately and refreshes it after the response', function () {
        $this->provider->all();

        $this->travel(FRESH_SECONDS + 1)->seconds();
        $served = $this->provider->all();

        expect($served->first()->name)->toBe('Germany v1')
            ->and($this->inner->calls)->toBe(1);

        runDeferredCallbacks();

        expect($this->inner->calls)->toBe(2)
            ->and($this->provider->all()->first()->name)->toBe('Germany v2')
            ->and($this->inner->calls)->toBe(2);
    });

    it('keeps serving the stale copy when the deferred refresh fails', function () {
        $this->provider->all();
        $this->inner->failing = true;

        $this->travel(FRESH_SECONDS + 1)->seconds();
        $this->provider->all();
        runDeferredCallbacks();

        expect($this->inner->calls)->toBe(2)
            ->and($this->provider->all()->first()->name)->toBe('Germany v1');
    });

    it('refetches synchronously once the stale window has passed', function () {
        $this->provider->all();

        $this->travel(STALE_SECONDS + 1)->seconds();
        $served = $this->provider->all();

        expect($served->first()->name)->toBe('Germany v2')
            ->and($this->inner->calls)->toBe(2);
    });
});
