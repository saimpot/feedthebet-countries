<?php

declare(strict_types=1);

use App\Countries\Data\Country;
use App\Countries\Events\CountriesFetched;
use App\Countries\Events\CountryProviderFailed;
use App\Countries\Exceptions\CountryProviderException;
use App\Countries\Providers\RestCountriesProvider;
use Illuminate\Events\Dispatcher;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;

/**
 * @return array<string, mixed>
 */
function restCountriesFixture(): array
{
    return json_decode(file_get_contents(__DIR__.'/../../Fixtures/restcountries_v5_list.json'), true, flags: JSON_THROW_ON_ERROR);
}

/**
 * @param  array<string, mixed>  $config
 * @return array{0: RestCountriesProvider, 1: ArrayObject<int, object>} the provider and the events it dispatched
 */
function makeRestCountriesProvider(Factory $http, array $config = []): array
{
    $events = new Dispatcher;
    $dispatched = new ArrayObject;
    $events->listen('*', fn (string $name, array $payload) => $dispatched->append($payload[0]));

    $provider = new RestCountriesProvider($http, $events, [
        'base_url' => 'https://api.test',
        'retries' => 0,
        ...$config,
    ]);

    return [$provider, $dispatched];
}

function fakedHttp(mixed $fake): Factory
{
    $http = new Factory;
    $http->preventStrayRequests();
    $http->fake($fake);

    return $http;
}

it('maps the REST Countries payload, skipping malformed rows and sorting by name', function () {
    $http = fakedHttp(['api.test/*' => Factory::response(restCountriesFixture())]);
    [$provider] = makeRestCountriesProvider($http);

    $countries = $provider->all();

    expect($countries->map->toArray()->all())->toBe([
        ['code' => 'BR', 'name' => 'Brazil', 'flagUrl' => 'https://flags.restcountries.com/v5/w640/br.png', 'flagAlt' => null],
        ['code' => 'CA', 'name' => 'Canada', 'flagUrl' => 'https://flags.restcountries.com/v5/svg/ca.svg', 'flagAlt' => 'The flag of Canada is composed of a red vertical band on the hoist and fly sides and a central white square that is twice the width of the vertical bands.'],
        ['code' => 'DE', 'name' => 'Germany', 'flagUrl' => 'https://flags.restcountries.com/v5/svg/de.svg', 'flagAlt' => 'The flag of Germany is composed of three equal horizontal bands of black, red and gold.'],
        ['code' => 'JP', 'name' => 'Japan', 'flagUrl' => 'https://flags.restcountries.com/v5/svg/jp.svg', 'flagAlt' => null],
    ])->and($countries->first())->toBeInstanceOf(Country::class);
});

it('requests the slim field list with the api key', function () {
    $http = fakedHttp(['api.test/*' => Factory::response(restCountriesFixture())]);
    [$provider] = makeRestCountriesProvider($http, ['api_key' => 'secret', 'page_size' => 50]);

    $provider->all();

    $http->assertSentCount(1);
    $http->assertSent(fn (Request $request) => str_starts_with($request->url(), 'https://api.test/countries/v5?')
        && $request['response_fields'] === 'names.common,codes.alpha_2,flag.url_svg,flag.url_png,flag.description'
        && (int) $request['limit'] === 50
        && (int) $request['offset'] === 0
        && $request->hasHeader('Authorization', 'Bearer secret')
        && $request->hasHeader('Accept', 'application/json'));
});

it('omits the authorization header without an api key', function () {
    $http = fakedHttp(['api.test/*' => Factory::response(restCountriesFixture())]);
    [$provider] = makeRestCountriesProvider($http);

    $provider->all();

    $http->assertSent(fn (Request $request) => ! $request->hasHeader('Authorization'));
});

it('follows pagination until every country is fetched', function () {
    $objects = restCountriesFixture()['data']['objects'];
    $page = fn (int $offset) => ['data' => [
        'objects' => array_slice($objects, $offset, 2),
        'meta' => ['total' => count($objects)],
    ]];
    $http = new Factory;
    $http->preventStrayRequests();
    $http->fake(['api.test/*' => $http->sequence()->push($page(0))->push($page(2))->push($page(4))]);
    [$provider] = makeRestCountriesProvider($http, ['page_size' => 2]);

    $countries = $provider->all();

    expect($countries->pluck('code')->all())->toBe(['BR', 'CA', 'DE', 'JP']);
    $http->assertSentCount(3);
    $http->assertSent(fn (Request $request) => (int) $request['offset'] === 4);
});

it('retries transient failures', function () {
    $http = new Factory;
    $http->preventStrayRequests();
    $http->fake(['api.test/*' => $http->sequence()
        ->push('', 500)
        ->push(restCountriesFixture())]);
    [$provider] = makeRestCountriesProvider($http, ['retries' => 2]);

    expect($provider->all())->toHaveCount(4);
    $http->assertSentCount(2);
});

it('dispatches CountriesFetched on success', function () {
    $http = fakedHttp(['api.test/*' => Factory::response(restCountriesFixture())]);
    [$provider, $dispatched] = makeRestCountriesProvider($http);

    $provider->all();

    expect($dispatched)->toHaveCount(1)
        ->and($dispatched[0])->toBeInstanceOf(CountriesFetched::class)
        ->and($dispatched[0]->provider)->toBe('restcountries')
        ->and($dispatched[0]->count)->toBe(4);
});

it('throws a domain exception and dispatches CountryProviderFailed on failure', function (Closure $fake) {
    [$provider, $dispatched] = makeRestCountriesProvider(fakedHttp(['api.test/*' => $fake()]));

    expect(fn () => $provider->all())->toThrow(CountryProviderException::class);

    expect($dispatched)->toHaveCount(1)
        ->and($dispatched[0])->toBeInstanceOf(CountryProviderFailed::class)
        ->and($dispatched[0]->provider)->toBe('restcountries')
        ->and($dispatched[0]->exception)->toBeInstanceOf(CountryProviderException::class);
})->with([
    'server error' => fn () => fn () => Factory::response(['errors' => []], 500),
    'unauthorized' => fn () => fn () => Factory::response(['errors' => []], 401),
    'connection error' => fn () => fn () => throw new ConnectionException('timeout'),
    'not json' => fn () => fn () => Factory::response('<html>', 200),
    'unexpected structure' => fn () => fn () => Factory::response(['data' => ['objects' => 'nope']]),
]);
