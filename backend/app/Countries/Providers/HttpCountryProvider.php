<?php

declare(strict_types=1);

namespace App\Countries\Providers;

use App\Countries\Contracts\CountryProvider;
use App\Countries\Data\Country;
use App\Countries\Events\CountriesFetched;
use App\Countries\Events\CountryProviderFailed;
use App\Countries\Exceptions\CountryProviderException;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;

/**
 * Template Method: {@see self::all()} fixes the algorithm (request, error
 * translation, mapping, sorting, events); subclasses supply the provider
 * specific steps.
 */
abstract class HttpCountryProvider implements CountryProvider
{
    /**
     * @param  array<string, mixed>  $config  base_url plus optional timeout, retries, retry_sleep_ms
     */
    public function __construct(
        private readonly Factory $http,
        private readonly Dispatcher $events,
        protected readonly array $config,
    ) {}

    /**
     * Short provider identifier used in events and exceptions.
     */
    abstract public function name(): string;

    /**
     * Path of the endpoint listing the countries, relative to the base URL.
     */
    abstract protected function endpoint(): string;

    /**
     * Turn one response payload into countries, skipping malformed entries.
     *
     * @param  array<array-key, mixed>  $payload
     * @return Collection<int, Country>
     *
     * @throws CountryProviderException when the payload has an unexpected structure
     */
    abstract protected function map(array $payload): Collection;

    /**
     * Query string of the first request.
     *
     * @return array<string, scalar>
     */
    protected function query(): array
    {
        return [];
    }

    /**
     * Extra request headers (e.g. authentication).
     *
     * @return array<string, string>
     */
    protected function headers(): array
    {
        return [];
    }

    /**
     * Query string of the next page, or null when the payload was the last one.
     *
     * @param  array<array-key, mixed>  $payload
     * @param  array<string, scalar>  $query  the query used to fetch $payload
     * @return array<string, scalar>|null
     */
    protected function nextPageQuery(array $payload, array $query): ?array
    {
        return null;
    }

    final public function all(): Collection
    {
        try {
            $countries = $this->fetchAllPages();
        } catch (CountryProviderException $exception) {
            $this->events->dispatch(new CountryProviderFailed($this->name(), $exception));

            throw $exception;
        }

        $countries = $countries
            ->sortBy(fn (Country $country): string => $country->name, SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $this->events->dispatch(new CountriesFetched($this->name(), $countries->count()));

        return $countries;
    }

    /**
     * @return Collection<int, Country>
     */
    private function fetchAllPages(): Collection
    {
        $countries = new Collection;
        $query = $this->query();

        while ($query !== null) {
            $payload = $this->fetch($query);
            $countries = $countries->concat($this->map($payload));
            $query = $this->nextPageQuery($payload, $query);
        }

        return $countries;
    }

    /**
     * @param  array<string, scalar>  $query
     * @return array<array-key, mixed>
     */
    private function fetch(array $query): array
    {
        try {
            $payload = $this->request()->get($this->endpoint(), $query)->throw()->json();
        } catch (ConnectionException|RequestException $exception) {
            throw CountryProviderException::requestFailed($this->name(), $exception);
        }

        return is_array($payload) ? $payload : throw CountryProviderException::invalidPayload($this->name());
    }

    private function request(): PendingRequest
    {
        return $this->http
            ->baseUrl($this->config['base_url'])
            ->timeout($this->config['timeout'] ?? 5)
            ->retry($this->config['retries'] ?? 0, $this->config['retry_sleep_ms'] ?? 0)
            ->acceptJson()
            ->withHeaders($this->headers());
    }
}
