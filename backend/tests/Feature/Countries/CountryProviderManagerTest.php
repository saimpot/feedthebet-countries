<?php

declare(strict_types=1);

use App\Countries\Contracts\CountryProvider;
use App\Countries\CountryProviderManager;
use App\Countries\Events\CountriesFetched;
use App\Countries\Events\CountryProviderFailed;
use App\Countries\Exceptions\CountryProviderException;
use App\Countries\Providers\CachedCountryProvider;
use App\Countries\Providers\RestCountriesProvider;
use App\Countries\Providers\StaticCountryProvider;
use Illuminate\Support\Facades\Event;
use Psr\Log\LoggerInterface;

it('resolves the drivers configured in config/countries.php', function (string $driver, string $class) {
    config()->set('countries.driver', $driver);

    expect(app(CountryProviderManager::class)->driver())->toBeInstanceOf($class);
})->with([
    'restcountries' => ['restcountries', RestCountriesProvider::class],
    'static' => ['static', StaticCountryProvider::class],
]);

it('can build a specific driver regardless of the default', function () {
    config()->set('countries.driver', 'static');

    expect(app(CountryProviderManager::class)->driver('restcountries'))->toBeInstanceOf(RestCountriesProvider::class);
});

it('binds the contract to the cached decorator when caching is enabled', function () {
    config()->set('countries.driver', 'static');
    config()->set('countries.cache.enabled', true);

    expect(app(CountryProvider::class))->toBeInstanceOf(CachedCountryProvider::class);
});

it('binds the contract to the raw driver when caching is disabled', function () {
    config()->set('countries.driver', 'static');
    config()->set('countries.cache.enabled', false);

    expect(app(CountryProvider::class))->toBeInstanceOf(StaticCountryProvider::class);
});

it('serves the bundled countries through the container', function () {
    config()->set('countries.driver', 'static');

    expect(app(CountryProvider::class)->all()->pluck('code'))->toContain('DE');
});

it('logs a warning when the provider fails', function () {
    $this->mock(LoggerInterface::class)
        ->shouldReceive('warning')
        ->once()
        ->with('Country provider failed.', Mockery::on(fn (array $context) => $context['provider'] === 'restcountries'));

    Event::dispatch(new CountryProviderFailed('restcountries', CountryProviderException::invalidPayload('restcountries')));
});

it('registers the failure listener exactly once', function () {
    expect(Event::getListeners(CountryProviderFailed::class))->toHaveCount(1);
});

it('logs an info entry when countries are fetched', function () {
    $this->mock(LoggerInterface::class)
        ->shouldReceive('info')
        ->once()
        ->with('Countries fetched.', ['provider' => 'static', 'count' => 250]);

    Event::dispatch(new CountriesFetched('static', 250));
});

it('registers the fetched listener exactly once', function () {
    expect(Event::getListeners(CountriesFetched::class))->toHaveCount(1);
});

it('dispatches CountriesFetched for every provider, including the static one', function () {
    config()->set('countries.driver', 'static');
    config()->set('countries.cache.enabled', false);
    Event::fake([CountriesFetched::class]);

    app(CountryProvider::class)->all();

    Event::assertDispatched(CountriesFetched::class, fn (CountriesFetched $event) => $event->provider === 'static' && $event->count > 240);
});
