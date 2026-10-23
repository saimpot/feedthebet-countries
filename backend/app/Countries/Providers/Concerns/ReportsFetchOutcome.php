<?php

declare(strict_types=1);

namespace App\Countries\Providers\Concerns;

use App\Countries\CountryNameSorter;
use App\Countries\Data\Country;
use App\Countries\Events\CountriesFetched;
use App\Countries\Events\CountryProviderFailed;
use App\Countries\Exceptions\CountryProviderException;
use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Collection;

/**
 * Gives every concrete provider the same contract: sorted results and an
 * observable outcome (CountriesFetched or CountryProviderFailed).
 */
trait ReportsFetchOutcome
{
    /**
     * @param  Closure(): Collection<int, Country>  $fetch
     * @return Collection<int, Country>
     *
     * @throws CountryProviderException
     */
    private function reportingOutcome(Dispatcher $events, string $provider, Closure $fetch): Collection
    {
        try {
            $countries = CountryNameSorter::sort($fetch());
        } catch (CountryProviderException $exception) {
            $events->dispatch(new CountryProviderFailed($provider, $exception));

            throw $exception;
        }

        $events->dispatch(new CountriesFetched($provider, $countries->count()));

        return $countries;
    }
}
