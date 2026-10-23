<?php

declare(strict_types=1);

namespace App\Countries\Providers;

use App\Countries\Contracts\CountryProvider;
use App\Countries\Contracts\RefreshableCountryProvider;
use App\Countries\Data\Country;
use Illuminate\Cache\Repository;
use Illuminate\Support\Collection;

/**
 * Decorator adding stale-while-revalidate caching to any provider. Plain
 * arrays (not objects) are stored so the cache survives class changes.
 *
 * @phpstan-type Row array{code: string, name: string, flagUrl: string, flagAlt: string|null}
 */
final readonly class CachedCountryProvider implements RefreshableCountryProvider
{
    /** Companion key written by Cache::flexible() holding the creation timestamp. */
    private const FLEXIBLE_CREATED_PREFIX = 'illuminate:cache:flexible:created:';

    public function __construct(
        private CountryProvider $inner,
        private Repository $cache,
        private string $key,
        private int $freshSeconds,
        private int $staleSeconds,
    ) {}

    public function all(): Collection
    {
        return $this->hydrate($this->remember(fn (): array => $this->fetchRows()));
    }

    public function forget(): void
    {
        $this->cache->forget($this->key);
        $this->cache->forget(self::FLEXIBLE_CREATED_PREFIX.$this->key);
    }

    public function refresh(): Collection
    {
        // Fetch first so a failing provider leaves the previous copy untouched.
        $rows = $this->fetchRows();

        $this->forget();

        return $this->hydrate($this->remember(fn (): array => $rows));
    }

    /**
     * @param  callable(): list<Row>  $rows
     * @return list<Row>
     */
    private function remember(callable $rows): array
    {
        return $this->cache->flexible($this->key, [$this->freshSeconds, $this->staleSeconds], $rows);
    }

    /**
     * @return list<Row>
     */
    private function fetchRows(): array
    {
        return $this->inner->all()
            ->map(fn (Country $country): array => $country->toArray())
            ->all();
    }

    /**
     * @param  list<Row>  $rows
     * @return Collection<int, Country>
     */
    private function hydrate(array $rows): Collection
    {
        return (new Collection($rows))->map(Country::fromArray(...));
    }
}
