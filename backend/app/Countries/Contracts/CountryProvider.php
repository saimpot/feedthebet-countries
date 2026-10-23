<?php

declare(strict_types=1);

namespace App\Countries\Contracts;

use App\Countries\Data\Country;
use App\Countries\Exceptions\CountryProviderException;
use Illuminate\Support\Collection;

interface CountryProvider
{
    /**
     * All countries, sorted by name.
     *
     * @return Collection<int, Country>
     *
     * @throws CountryProviderException
     */
    public function all(): Collection;
}
