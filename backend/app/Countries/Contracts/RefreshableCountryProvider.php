<?php

declare(strict_types=1);

namespace App\Countries\Contracts;

use App\Countries\Data\Country;
use App\Countries\Exceptions\CountryProviderException;
use Illuminate\Support\Collection;

interface RefreshableCountryProvider extends CountryProvider
{
    /**
     * Drop any stored copy of the data.
     */
    public function forget(): void;

    /**
     * Fetch from the source and replace the stored copy.
     *
     * @return Collection<int, Country>
     *
     * @throws CountryProviderException
     */
    public function refresh(): Collection;
}
