<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Country data provider
    |--------------------------------------------------------------------------
    |
    | One of the keys of "drivers" below. Without a REST Countries API key the
    | bundled static dataset is used so the application works out of the box.
    |
    */

    'driver' => env('COUNTRIES_DRIVER', env('COUNTRIES_RESTCOUNTRIES_API_KEY') ? 'restcountries' : 'static'),

    'drivers' => [

        // https://restcountries.com/docs/countries (v5 requires an API key, free plan: 1000 requests/month)
        'restcountries' => [
            'base_url' => env('COUNTRIES_RESTCOUNTRIES_URL', 'https://api.restcountries.com'),
            'api_key' => env('COUNTRIES_RESTCOUNTRIES_API_KEY'),
            'page_size' => 100, // the free plan caps "limit" at 100
            'timeout' => 5,
            'retries' => 2,
            'retry_sleep_ms' => 200,
        ],

        'static' => [
            'path' => resource_path('data/countries.json'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Stale-while-revalidate: data is served as-is for "fresh_ttl" seconds, then
    | served stale (and refreshed after the response) for up to "stale_ttl"
    | seconds in total. Bump the version in "key_prefix" when the payload shape
    | changes.
    |
    */

    'cache' => [
        'enabled' => (bool) env('COUNTRIES_CACHE_ENABLED', true),
        'store' => env('COUNTRIES_CACHE_STORE'), // null = default cache store
        'key_prefix' => 'countries:v1',
        'fresh_ttl' => 86400,
        'stale_ttl' => 604800,
    ],

];
