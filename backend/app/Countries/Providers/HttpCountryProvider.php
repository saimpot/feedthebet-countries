<?php

declare(strict_types=1);

namespace App\Countries\Providers;

use App\Countries\Contracts\CountryProvider;
use App\Countries\Data\Country;
use App\Countries\Exceptions\CountryProviderException;
use App\Countries\Providers\Concerns\ReportsFetchOutcome;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Template Method: {@see self::all()} fixes the algorithm (paged requests, error
 * translation, mapping, sorting, events); subclasses supply the provider
 * specific steps.
 */
abstract class HttpCountryProvider implements CountryProvider
{
    use ReportsFetchOutcome;

    private const DEFAULT_MAX_PAGES = 10;

    /**
     * @param  array<string, mixed>  $config  base_url plus optional timeout, retries, retry_sleep_ms, max_pages
     */
    public function __construct(
        private readonly Factory $http,
        private readonly Dispatcher $events,
        protected readonly array $config,
    ) {}

    abstract public function name(): string;

    /**
     * Path relative to the base URL.
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
     * @return array<string, scalar>
     */
    protected function query(): array
    {
        return [];
    }

    /**
     * @return array<string, string>
     */
    protected function headers(): array
    {
        return [];
    }

    /**
     * Query of the next page, or null when the payload was the last one.
     *
     * @param  array<array-key, mixed>  $payload
     * @param  array<string, scalar>  $query  the query that fetched $payload
     * @return array<string, scalar>|null
     */
    protected function nextPageQuery(array $payload, array $query): ?array
    {
        return null;
    }

    final public function all(): Collection
    {
        return $this->reportingOutcome($this->events, $this->name(), $this->fetchAllPages(...));
    }

    /**
     * @return Collection<int, Country>
     */
    private function fetchAllPages(): Collection
    {
        $maxPages = $this->config['max_pages'] ?? self::DEFAULT_MAX_PAGES;
        $countries = new Collection;
        $query = $this->query();

        for ($page = 1; $query !== null; $page++) {
            // A misbehaving API (e.g. an inflated total) must not keep us looping against a metered quota.
            if ($page > $maxPages) {
                throw CountryProviderException::tooManyPages($this->name(), $maxPages);
            }

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
            ->retry($this->config['retries'] ?? 0, $this->config['retry_sleep_ms'] ?? 0, $this->isTransient(...))
            ->acceptJson()
            ->withHeaders($this->headers());
    }

    /**
     * Client errors such as 401/403/429 will not fix themselves and would only burn the API quota.
     */
    private function isTransient(Throwable $exception): bool
    {
        return $exception instanceof ConnectionException
            || ($exception instanceof RequestException && $exception->response->serverError());
    }
}
