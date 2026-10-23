<?php

declare(strict_types=1);

namespace App\Countries;

use App\Countries\Data\Country;
use Collator;
use Illuminate\Support\Collection;

/**
 * Orders countries the way a person expects: a primary-strength collator ignores
 * case and accents, so "Åland Islands" follows "Afghanistan" instead of landing
 * after "Zimbabwe" as a byte-wise comparison would put it.
 */
final class CountryNameSorter
{
    /**
     * @param  Collection<int, Country>  $countries
     * @return Collection<int, Country>
     */
    public static function sort(Collection $countries): Collection
    {
        $collator = new Collator('en');
        $collator->setStrength(Collator::PRIMARY);

        return $countries
            ->sort(fn (Country $a, Country $b): int => (int) $collator->compare($a->name, $b->name))
            ->values();
    }
}
