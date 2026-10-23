<?php

declare(strict_types=1);

use App\Countries\Contracts\CountryProvider;
use App\Countries\Data\Country;
use App\Countries\Exceptions\CountryProviderException;
use App\Countries\Providers\CachedCountryProvider;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Support\Collection;

/**
 * Inner provider that counts calls and can be told to fail.
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

            return collect([new Country('DE', 'Germany', 'https://example.test/de.svg', 'Flag of Germany')]);
        }
    };
}

beforeEach(function () {
    $this->inner = countingProvider();
    $this->cache = new Repository(new ArrayStore);
    $this->provider = new CachedCountryProvider($this->inner, $this->cache, 'countries:test', 60, 600);
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
        ['code' => 'DE', 'name' => 'Germany', 'flagUrl' => 'https://example.test/de.svg', 'flagAlt' => 'Flag of Germany'],
    ]);
});

it('forgets the cached copy', function () {
    $this->provider->all();
    $this->provider->forget();
    $this->provider->all();

    expect($this->inner->calls)->toBe(2);
});

it('refreshes the cached copy from the inner provider', function () {
    $this->provider->all();
    $this->provider->refresh();
    $this->provider->all();

    expect($this->inner->calls)->toBe(2);
});

it('keeps the previous copy when a refresh fails', function () {
    $this->provider->all();
    $this->inner->failing = true;

    expect(fn () => $this->provider->refresh())->toThrow(CountryProviderException::class)
        ->and($this->provider->all())->toHaveCount(1)
        ->and($this->inner->calls)->toBe(2);
});

it('does not cache failures', function () {
    $this->inner->failing = true;

    expect(fn () => $this->provider->all())->toThrow(CountryProviderException::class)
        ->and($this->cache->has('countries:test'))->toBeFalse();
});
