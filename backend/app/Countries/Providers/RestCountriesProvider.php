<?php

declare(strict_types=1);

namespace App\Countries\Providers;

use App\Countries\Data\Country;
use App\Countries\Exceptions\CountryProviderException;
use Illuminate\Support\Collection;

/**
 * Adapter for the REST Countries v5 API (https://restcountries.com/docs/countries).
 *
 * The v5 list endpoint is paginated (max 100 rows on the free plan), needs a
 * bearer API key and wraps rows in {"data": {"objects": [...], "meta": {"total": N}}}.
 */
final class RestCountriesProvider extends HttpCountryProvider
{
    private const DEFAULT_PAGE_SIZE = 100;

    public function name(): string
    {
        return 'restcountries';
    }

    protected function endpoint(): string
    {
        return 'countries/v5';
    }

    protected function query(): array
    {
        return [
            'response_fields' => 'names.common,codes.alpha_2,flag.url_svg,flag.url_png,flag.description',
            'limit' => $this->config['page_size'] ?? self::DEFAULT_PAGE_SIZE,
            'offset' => 0,
        ];
    }

    protected function headers(): array
    {
        $key = $this->config['api_key'] ?? null;

        return is_string($key) && $key !== '' ? ['Authorization' => "Bearer {$key}"] : [];
    }

    protected function nextPageQuery(array $payload, array $query): ?array
    {
        $received = count($this->objects($payload));
        $offset = (int) $query['offset'] + $received;
        $total = $payload['data']['meta']['total'] ?? 0;

        return $received > 0 && is_int($total) && $offset < $total
            ? [...$query, 'offset' => $offset]
            : null;
    }

    protected function map(array $payload): Collection
    {
        return collect($this->objects($payload))
            ->map(fn (mixed $row): ?Country => is_array($row) ? $this->toCountry($row) : null)
            ->filter()
            ->values();
    }

    /**
     * @param  array<array-key, mixed>  $payload
     * @return array<array-key, mixed>
     */
    private function objects(array $payload): array
    {
        $objects = $payload['data']['objects'] ?? null;

        return is_array($objects) ? $objects : throw CountryProviderException::invalidPayload($this->name());
    }

    /**
     * @param  array<array-key, mixed>  $row
     */
    private function toCountry(array $row): ?Country
    {
        $code = $row['codes']['alpha_2'] ?? null;
        $name = $row['names']['common'] ?? null;
        $flagUrl = $row['flag']['url_svg'] ?? $row['flag']['url_png'] ?? null;
        $flagAlt = $row['flag']['description'] ?? null;

        if (! is_string($code) || ! is_string($name) || ! is_string($flagUrl) || $code === '' || $name === '' || $flagUrl === '') {
            return null;
        }

        return new Country($code, $name, $flagUrl, is_string($flagAlt) && $flagAlt !== '' ? $flagAlt : null);
    }
}
