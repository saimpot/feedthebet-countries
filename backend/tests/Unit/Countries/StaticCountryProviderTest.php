<?php

declare(strict_types=1);

use App\Countries\Events\CountriesFetched;
use App\Countries\Events\CountryProviderFailed;
use App\Countries\Exceptions\CountryProviderException;
use App\Countries\Providers\StaticCountryProvider;
use Illuminate\Events\Dispatcher;

/**
 * @param  array<mixed>|string  $contents  rows to JSON-encode, or raw file contents
 * @return array{0: StaticCountryProvider, 1: ArrayObject<int, object>, 2: string} the provider, its dispatched events and the file path
 */
function staticProviderFor(array|string $contents): array
{
    $path = tempnam(sys_get_temp_dir(), 'countries');
    file_put_contents($path, is_string($contents) ? $contents : json_encode($contents));

    $events = new Dispatcher;
    $dispatched = new ArrayObject;
    $events->listen('*', fn (string $name, array $payload) => $dispatched->append($payload[0]));

    return [new StaticCountryProvider($path, $events), $dispatched, $path];
}

it('reads countries from the JSON file sorted by name', function () {
    [$provider, , $path] = staticProviderFor([
        ['code' => 'FR', 'name' => 'France', 'flagUrl' => 'https://flagcdn.com/fr.svg'],
        ['code' => 'DE', 'name' => 'Germany', 'flagUrl' => 'https://flagcdn.com/de.svg', 'flagAlt' => 'Flag of Germany'],
        ['code' => 'AT', 'name' => 'austria', 'flagUrl' => 'https://flagcdn.com/at.svg'],
    ]);

    $countries = $provider->all();

    expect($countries->pluck('name')->all())->toBe(['austria', 'France', 'Germany'])
        ->and($countries[2]->flagAlt)->toBe('Flag of Germany')
        ->and($countries[1]->flagAlt)->toBeNull();

    unlink($path);
});

it('sorts accented names next to their base letter', function () {
    [$provider, , $path] = staticProviderFor([
        ['code' => 'ZW', 'name' => 'Zimbabwe', 'flagUrl' => 'https://flagcdn.com/zw.svg'],
        ['code' => 'AX', 'name' => 'Åland Islands', 'flagUrl' => 'https://flagcdn.com/ax.svg'],
        ['code' => 'AF', 'name' => 'Afghanistan', 'flagUrl' => 'https://flagcdn.com/af.svg'],
        ['code' => 'CU', 'name' => 'Cuba', 'flagUrl' => 'https://flagcdn.com/cu.svg'],
        ['code' => 'CI', 'name' => 'Côte d\'Ivoire', 'flagUrl' => 'https://flagcdn.com/ci.svg'],
        ['code' => 'CA', 'name' => 'Canada', 'flagUrl' => 'https://flagcdn.com/ca.svg'],
    ]);

    expect($provider->all()->pluck('name')->all())
        ->toBe(['Afghanistan', 'Åland Islands', 'Canada', 'Côte d\'Ivoire', 'Cuba', 'Zimbabwe']);

    unlink($path);
});

it('skips malformed rows instead of failing', function () {
    [$provider, , $path] = staticProviderFor([
        ['code' => 'FR', 'name' => 'France', 'flagUrl' => 'https://flagcdn.com/fr.svg'],
        ['name' => 'No code', 'flagUrl' => 'https://flagcdn.com/xx.svg'],
        ['code' => 'XX', 'name' => '', 'flagUrl' => 'https://flagcdn.com/xx.svg'],
        ['code' => 'YY', 'name' => 'No flag'],
        ['code' => 42, 'name' => 'Numeric code', 'flagUrl' => 'https://flagcdn.com/xx.svg'],
        'not a row',
    ]);

    expect($provider->all()->pluck('code')->all())->toBe(['FR']);

    unlink($path);
});

it('ships a complete bundled dataset with Åland near the top', function () {
    $provider = new StaticCountryProvider(dirname(__DIR__, 3).'/resources/data/countries.json', new Dispatcher);

    $countries = $provider->all();

    expect($countries->count())->toBeGreaterThan(240)
        ->and($countries->pluck('code'))->toContain('DE', 'GR', 'US')
        ->and($countries->take(3)->pluck('name')->all())->toBe(['Afghanistan', 'Åland Islands', 'Albania']);
});

it('dispatches CountriesFetched on success', function () {
    [$provider, $dispatched, $path] = staticProviderFor([
        ['code' => 'FR', 'name' => 'France', 'flagUrl' => 'https://flagcdn.com/fr.svg'],
    ]);

    $provider->all();

    expect($dispatched)->toHaveCount(1)
        ->and($dispatched[0])->toBeInstanceOf(CountriesFetched::class)
        ->and($dispatched[0]->provider)->toBe('static')
        ->and($dispatched[0]->count)->toBe(1);

    unlink($path);
});

it('fails with a domain exception and dispatches CountryProviderFailed', function (string $contents) {
    [$provider, $dispatched, $path] = staticProviderFor($contents);

    expect(fn () => $provider->all())->toThrow(CountryProviderException::class);
    expect($dispatched)->toHaveCount(1)
        ->and($dispatched[0])->toBeInstanceOf(CountryProviderFailed::class)
        ->and($dispatched[0]->provider)->toBe('static');

    unlink($path);
})->with([
    'invalid json' => ['{nope'],
    'not a list' => ['"text"'],
]);

it('fails when the file is missing', function () {
    $provider = new StaticCountryProvider(sys_get_temp_dir().'/missing-countries-'.uniqid().'.json', new Dispatcher);

    expect(fn () => $provider->all())->toThrow(CountryProviderException::class);
});
