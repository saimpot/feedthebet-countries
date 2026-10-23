<?php

declare(strict_types=1);

namespace App\Countries;

use App\Countries\Contracts\CountryProvider;
use App\Countries\Providers\RestCountriesProvider;
use App\Countries\Providers\StaticCountryProvider;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Manager;

/**
 * Factory Method: each createXDriver() method builds one provider. Adding a
 * provider means adding a method here and an entry in config/countries.php.
 *
 * @method CountryProvider driver(?string $driver = null)
 */
final class CountryProviderManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return (string) $this->config->get('countries.driver');
    }

    protected function createRestcountriesDriver(): CountryProvider
    {
        return new RestCountriesProvider(
            $this->container->make(Factory::class),
            $this->container->make(Dispatcher::class),
            $this->config->get('countries.drivers.restcountries'),
        );
    }

    protected function createStaticDriver(): CountryProvider
    {
        return new StaticCountryProvider(
            (string) $this->config->get('countries.drivers.static.path'),
            $this->container->make(Dispatcher::class),
        );
    }
}
