<?php

declare(strict_types=1);

namespace App\Providers;

use App\Countries\Contracts\CountryProvider;
use App\Countries\CountryProviderManager;
use App\Countries\Events\CountryProviderFailed;
use App\Countries\Listeners\LogCountryProviderFailure;
use App\Countries\Providers\CachedCountryProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class CountryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CountryProviderManager::class);

        $this->app->singleton(CountryProvider::class, function (Application $app): CountryProvider {
            $provider = $app->make(CountryProviderManager::class)->driver();
            $cache = $app['config']['countries.cache'];

            if (! $cache['enabled']) {
                return $provider;
            }

            return new CachedCountryProvider(
                $provider,
                $app->make('cache')->store($cache['store']),
                "{$cache['key_prefix']}:{$app['config']['countries.driver']}",
                $cache['fresh_ttl'],
                $cache['stale_ttl'],
            );
        });
    }

    public function boot(): void
    {
        Event::listen(CountryProviderFailed::class, LogCountryProviderFailure::class);
    }
}
