<?php

declare(strict_types=1);

use App\Countries\CountryNameSorter;
use App\Countries\Data\Country;
use Illuminate\Support\Collection;

function countriesNamed(string ...$names): Collection
{
    return collect($names)->map(fn (string $name) => new Country(strtoupper(substr($name, 0, 2)), $name, 'https://flags.test/x.svg'));
}

it('ignores accents and case', function () {
    $sorted = CountryNameSorter::sort(countriesNamed('Zimbabwe', 'Åland Islands', 'Albania', 'austria', 'Côte d\'Ivoire', 'Cuba', 'Afghanistan', 'Canada'));

    expect($sorted->pluck('name')->all())->toBe([
        'Afghanistan', 'Åland Islands', 'Albania', 'austria', 'Canada', 'Côte d\'Ivoire', 'Cuba', 'Zimbabwe',
    ]);
});

it('returns a re-indexed collection and keeps the input untouched', function () {
    $input = countriesNamed('B', 'A');

    $sorted = CountryNameSorter::sort($input);

    expect($sorted->keys()->all())->toBe([0, 1])
        ->and($input->pluck('name')->all())->toBe(['B', 'A']);
});
