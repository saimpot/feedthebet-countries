<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Countries\Contracts\CountryProvider;
use App\Countries\Contracts\RefreshableCountryProvider;
use App\Countries\Data\Country;
use App\Countries\Exceptions\CountryProviderException;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

final class WarmCountriesCache extends Command
{
    protected $signature = 'countries:warm {--fresh : Forget cached data before fetching}';

    protected $description = 'Fetch the countries from the configured provider and store them in the cache';

    public function handle(CountryProvider $provider): int
    {
        try {
            $countries = $this->warm($provider);
        } catch (CountryProviderException $exception) {
            $this->error("Could not warm the countries cache: {$exception->getMessage()}");

            return self::FAILURE;
        }

        $this->info("Warmed the countries cache with {$countries->count()} countries.");

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, Country>
     */
    private function warm(CountryProvider $provider): Collection
    {
        if (! $provider instanceof RefreshableCountryProvider) {
            return $provider->all();
        }

        if ($this->option('fresh')) {
            $provider->forget();

            return $provider->all();
        }

        return $provider->refresh();
    }
}
