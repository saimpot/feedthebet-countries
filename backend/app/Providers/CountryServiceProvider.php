<?php

declare(strict_types=1);

namespace App\Providers;

use App\Countries\Contracts\CountryProvider;
use App\Countries\CountryProviderManager;
use App\Countries\Events\CountryProviderFailed;
use App\Countries\Listeners\LogCountryProviderFailure;
use App\Countries\Providers\CachedCountryProvider;
use Illuminate\Cache\CacheManager;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class CountryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CountryProviderManager::class);

        $this->app->singleton(CountryProvider::class, function (Application $app): CountryProvider {
            $provider = $app->make(CountryProviderManager::class)->driver();

            if (! Config::boolean('countries.cache.enabled')) {
                return $provider;
            }

            $store = Config::get('countries.cache.store');

            /** @var CacheRepository $cache CacheManager::store() returns the concrete repository, which provides flexible(). */
            $cache = $app->make(CacheManager::class)->store(is_string($store) ? $store : null);

            return new CachedCountryProvider(
                $provider,
                $cache,
                Config::string('countries.cache.key_prefix').':'.Config::string('countries.driver'),
                Config::integer('countries.cache.fresh_ttl'),
                Config::integer('countries.cache.stale_ttl'),
            );
        });
    }

    public function boot(): void
    {
        Event::listen(CountryProviderFailed::class, LogCountryProviderFailure::class);
    }
}
