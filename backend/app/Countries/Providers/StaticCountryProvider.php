<?php

declare(strict_types=1);

namespace App\Countries\Providers;

use App\Countries\Contracts\CountryProvider;
use App\Countries\Data\Country;
use App\Countries\Exceptions\CountryProviderException;
use App\Countries\Providers\Concerns\ReportsFetchOutcome;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Collection;
use JsonException;

/**
 * Serves countries from a local JSON file: works offline and shows that the
 * provider is swappable. Like the HTTP adapter it skips malformed rows.
 */
final readonly class StaticCountryProvider implements CountryProvider
{
    use ReportsFetchOutcome;

    private const NAME = 'static';

    public function __construct(
        private string $path,
        private Dispatcher $events,
    ) {}

    public function all(): Collection
    {
        return $this->reportingOutcome($this->events, self::NAME, $this->load(...));
    }

    /**
     * @return Collection<int, Country>
     */
    private function load(): Collection
    {
        return $this->read()
            ->map(fn (mixed $row): ?Country => is_array($row)
                ? Country::tryCreate($row['code'] ?? null, $row['name'] ?? null, $row['flagUrl'] ?? null, $row['flagAlt'] ?? null)
                : null)
            ->filter()
            ->values();
    }

    /**
     * @return Collection<int, mixed>
     */
    private function read(): Collection
    {
        $contents = is_readable($this->path) ? file_get_contents($this->path) : false;

        if ($contents === false) {
            throw CountryProviderException::unreadableSource(self::NAME, $this->path);
        }

        try {
            $rows = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw CountryProviderException::invalidPayload(self::NAME);
        }

        return is_array($rows) ? new Collection(array_values($rows)) : throw CountryProviderException::invalidPayload(self::NAME);
    }
}
