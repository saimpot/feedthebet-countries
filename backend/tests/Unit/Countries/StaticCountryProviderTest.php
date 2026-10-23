<?php

declare(strict_types=1);

use App\Countries\Exceptions\CountryProviderException;
use App\Countries\Providers\StaticCountryProvider;

it('reads countries from the JSON file sorted by name', function () {
    $path = tempnam(sys_get_temp_dir(), 'countries');
    file_put_contents($path, json_encode([
        ['code' => 'FR', 'name' => 'France', 'flagUrl' => 'https://flagcdn.com/fr.svg'],
        ['code' => 'DE', 'name' => 'Germany', 'flagUrl' => 'https://flagcdn.com/de.svg', 'flagAlt' => 'Flag of Germany'],
        ['code' => 'AT', 'name' => 'austria', 'flagUrl' => 'https://flagcdn.com/at.svg'],
    ]));

    $countries = (new StaticCountryProvider($path))->all();

    expect($countries->pluck('name')->all())->toBe(['austria', 'France', 'Germany'])
        ->and($countries[2]->flagAlt)->toBe('Flag of Germany')
        ->and($countries[1]->flagAlt)->toBeNull();

    unlink($path);
});

it('ships a complete bundled dataset', function () {
    $countries = (new StaticCountryProvider(dirname(__DIR__, 3).'/resources/data/countries.json'))->all();

    expect($countries->count())->toBeGreaterThan(240)
        ->and($countries->pluck('code'))->toContain('DE', 'GR', 'US');
});

it('fails when the file is missing', function () {
    $provider = new StaticCountryProvider(sys_get_temp_dir().'/missing-countries-'.uniqid().'.json');

    expect(fn () => $provider->all())->toThrow(CountryProviderException::class);
});

it('fails when the file content is invalid', function (string $contents) {
    $path = tempnam(sys_get_temp_dir(), 'countries');
    file_put_contents($path, $contents);

    expect(fn () => (new StaticCountryProvider($path))->all())->toThrow(CountryProviderException::class);

    unlink($path);
})->with([
    'invalid json' => ['{nope'],
    'not a list' => ['"text"'],
]);
