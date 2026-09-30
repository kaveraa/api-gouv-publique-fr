<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use Kaveraa\ApiGouv\Adresse\AdresseApi;
use Kaveraa\ApiGouv\ApiGouvClient;
use Kaveraa\ApiGouv\Entreprises\EntreprisesApi;
use Kaveraa\ApiGouv\Geo\GeoApi;
use Kaveraa\ApiGouv\Http\Transport;
use Kaveraa\ApiGouv\Laravel\ApiGouv;
use Kaveraa\ApiGouv\Laravel\ApiGouvServiceProvider;
use Kaveraa\ApiGouv\Laravel\LaravelHttpTransport;

it('merges the default config', function () {
    expect(config('api-gouv.adresse.base_url'))->toBe('https://data.geopf.fr/geocodage')
        ->and(config('api-gouv.entreprises.base_url'))->toBe('https://recherche-entreprises.api.gouv.fr')
        ->and(config('api-gouv.cache.enabled'))->toBeFalse();
});

it('binds the transport and the clients', function () {
    expect(app(Transport::class))->toBeInstanceOf(LaravelHttpTransport::class)
        ->and(app(ApiGouvClient::class))->toBeInstanceOf(ApiGouvClient::class)
        ->and(app(EntreprisesApi::class))->toBe(app(ApiGouvClient::class)->entreprises())
        ->and(app(AdresseApi::class))->toBe(app(ApiGouvClient::class)->adresse());
});

it('works through the facade with Http::fake', function () {
    Http::fake(['recherche-entreprises.api.gouv.fr/*' => Http::response(loadFixture('entreprises_siren.json'))]);

    $entreprise = ApiGouv::entreprises()->parSiren('812487973');

    expect($entreprise->nomComplet)->toBe('OCTO');
});

it('reads the address api base url from the config', function () {
    config(['api-gouv.adresse.base_url' => 'https://custom.test/geo']);
    app()->forgetInstance(ApiGouvClient::class);
    Http::fake(['custom.test/*' => Http::response(loadFixture('adresse_empty.json'))]);

    app(ApiGouvClient::class)->adresse()->rechercher('x');

    Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://custom.test/geo/search'));
});

it('falls back to a default cache ttl when none is configured', function () {
    config([
        'api-gouv.cache.enabled' => true,
        'api-gouv.cache.store' => 'array',
        'api-gouv.adresse.cache_ttl' => null,
        'api-gouv.entreprises.cache_ttl' => null,
    ]);
    app()->forgetInstance(ApiGouvClient::class);
    Http::fake([
        'data.geopf.fr/*' => Http::response(loadFixture('adresse_search.json')),
        'recherche-entreprises.api.gouv.fr/*' => Http::response(loadFixture('entreprises_siren.json')),
    ]);

    ApiGouv::adresse()->rechercher('8 bd du port amiens');
    ApiGouv::adresse()->rechercher('8 bd du port amiens');
    ApiGouv::entreprises()->parSiren('812487973');
    ApiGouv::entreprises()->parSiren('812487973');

    Http::assertSentCount(2);
});

it('caches responses when the cache is enabled', function () {
    config(['api-gouv.cache.enabled' => true, 'api-gouv.cache.store' => 'array']);
    app()->forgetInstance(ApiGouvClient::class);
    Http::fake(['data.geopf.fr/*' => Http::response(loadFixture('adresse_search.json'))]);

    ApiGouv::adresse()->rechercher('8 bd du port amiens');
    ApiGouv::adresse()->rechercher('8 bd du port amiens');

    Http::assertSentCount(1);
});

it('publishes the config file and the translations under their tags', function () {
    $config = ServiceProvider::pathsToPublish(ApiGouvServiceProvider::class, 'api-gouv-config');
    $lang = ServiceProvider::pathsToPublish(ApiGouvServiceProvider::class, 'api-gouv-lang');

    expect(array_values($config))->toBe([config_path('api-gouv.php')])
        ->and(basename((string) array_key_first($config)))->toBe('api-gouv.php')
        ->and(array_values($lang))->toBe([app()->langPath('vendor/api-gouv')])
        ->and(basename((string) array_key_first($lang)))->toBe('lang');
});

it('merges the geo config and binds the geo client', function () {
    expect(config('api-gouv.geo.base_url'))->toBe('https://geo.api.gouv.fr')
        ->and(config('api-gouv.geo.cache_ttl'))->toBe(86400)
        ->and(app(GeoApi::class))->toBe(app(ApiGouvClient::class)->geo());
});

it('finds a commune through the facade with Http::fake', function () {
    Http::fake(['geo.api.gouv.fr/*' => Http::response(loadFixture('geo_commune.json'))]);

    expect(ApiGouv::geo()->commune('80021')->nom)->toBe('Amiens');

    Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://geo.api.gouv.fr/communes/80021?fields='));
});

it('caches geo responses and falls back to the geo ttl when the key is missing', function () {
    config(['api-gouv.cache.enabled' => true, 'api-gouv.cache.store' => 'array', 'api-gouv.geo.cache_ttl' => null]);
    app()->forgetInstance(ApiGouvClient::class);
    Http::fake(['geo.api.gouv.fr/*' => Http::response(loadFixture('geo_regions.json'))]);

    ApiGouv::geo()->regions();
    ApiGouv::geo()->regions();

    Http::assertSentCount(1);
});

it('still works when a published config has no geo block', function () {
    config(['api-gouv.geo' => null]);
    app()->forgetInstance(ApiGouvClient::class);
    Http::fake(['geo.api.gouv.fr/*' => Http::response(loadFixture('geo_region.json'))]);

    expect(ApiGouv::geo()->region('32')->nom)->toBe('Hauts-de-France');
});
