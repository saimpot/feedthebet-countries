<?php

declare(strict_types=1);

namespace App\Countries\Providers;

use App\Countries\Contracts\CountryProvider;
use App\Countries\Data\Country;
use App\Countries\Exceptions\CountryProviderException;
use Illuminate\Support\Collection;
use JsonException;

/**
 * Serves countries from a local JSON file: works offline and shows that the
 * provider is swappable.
 */
final readonly class StaticCountryProvider implements CountryProvider
{
    public function __construct(private string $path) {}

    public function all(): Collection
    {
        return $this->read()
            ->map(Country::fromArray(...))
            ->sortBy(fn (Country $country): string => $country->name, SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /**
     * @return Collection<int, array{code: string, name: string, flagUrl: string, flagAlt?: string|null}>
     */
    private function read(): Collection
    {
        $contents = is_readable($this->path) ? file_get_contents($this->path) : false;

        if ($contents === false) {
            throw CountryProviderException::unreadableSource('static', $this->path);
        }

        try {
            $rows = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw CountryProviderException::invalidPayload('static');
        }

        return is_array($rows) ? new Collection($rows) : throw CountryProviderException::invalidPayload('static');
    }
}
