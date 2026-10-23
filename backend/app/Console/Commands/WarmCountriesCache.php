<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Countries\Contracts\CountryProvider;
use App\Countries\Contracts\RefreshableCountryProvider;
use App\Countries\Exceptions\CountryProviderException;
use Illuminate\Console\Command;

final class WarmCountriesCache extends Command
{
    protected $signature = 'countries:warm';

    protected $description = 'Fetch the countries from the configured provider and replace the cached copy';

    public function handle(CountryProvider $provider): int
    {
        if (! $provider instanceof RefreshableCountryProvider) {
            $this->components->info('Caching is disabled; nothing to warm.');

            return self::SUCCESS;
        }

        try {
            // Fetches before replacing, so a failing provider keeps the previous copy.
            $countries = $provider->refresh();
        } catch (CountryProviderException $exception) {
            $this->components->error("Could not warm the countries cache: {$exception->getMessage()}");

            return self::FAILURE;
        }

        $this->components->info("Warmed the countries cache with {$countries->count()} countries.");

        return self::SUCCESS;
    }
}
