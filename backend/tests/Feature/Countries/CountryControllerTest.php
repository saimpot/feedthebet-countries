<?php

declare(strict_types=1);

use App\Countries\Contracts\CountryProvider;
use App\Countries\Data\Country;
use App\Countries\Exceptions\CountryProviderException;
use App\Http\Controllers\Api\CountryController;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::get('/_test/countries', CountryController::class);
});

it('returns the countries in the API contract shape', function () {
    $this->app->instance(CountryProvider::class, new class implements CountryProvider
    {
        public function all(): Collection
        {
            return collect([
                new Country('DE', 'Germany', 'https://flags.test/de.svg', 'Flag of Germany'),
                new Country('JP', 'Japan', 'https://flags.test/jp.svg'),
            ]);
        }
    });

    $this->getJson('/_test/countries')
        ->assertOk()
        ->assertExactJson(['data' => [
            ['code' => 'DE', 'name' => 'Germany', 'flagUrl' => 'https://flags.test/de.svg', 'flagAlt' => 'Flag of Germany'],
            ['code' => 'JP', 'name' => 'Japan', 'flagUrl' => 'https://flags.test/jp.svg', 'flagAlt' => null],
        ]]);
});

it('lets provider failures propagate to the exception handler', function () {
    $this->app->instance(CountryProvider::class, new class implements CountryProvider
    {
        public function all(): Collection
        {
            throw CountryProviderException::invalidPayload('fake');
        }
    });

    $this->withoutExceptionHandling()
        ->getJson('/_test/countries');
})->throws(CountryProviderException::class);
