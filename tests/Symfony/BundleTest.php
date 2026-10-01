<?php

declare(strict_types=1);

use Kaveraa\ApiGouv\Adresse\AdresseApi;
use Kaveraa\ApiGouv\Adresse\AdresseClient;
use Kaveraa\ApiGouv\ApiGouvClient;
use Kaveraa\ApiGouv\Entreprises\EntreprisesApi;
use Kaveraa\ApiGouv\Entreprises\EntreprisesClient;
use Kaveraa\ApiGouv\Geo\GeoApi;
use Kaveraa\ApiGouv\Geo\GeoClient;
use Kaveraa\ApiGouv\Http\Psr18Transport;
use Kaveraa\ApiGouv\Http\Transport;
use Kaveraa\ApiGouv\Tests\Symfony\AutowiredConsumer;
use Kaveraa\ApiGouv\Tests\Symfony\MockResponses;
use Kaveraa\ApiGouv\Tests\Symfony\NoHttpClientBundle;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\HttpClient\Response\MockResponse;

it('wires the clients with the defaults', function () {
    $container = $this->boot();

    expect($container->get(EntreprisesApi::class))->toBeInstanceOf(EntreprisesClient::class)
        ->and($container->get(AdresseApi::class))->toBeInstanceOf(AdresseClient::class)
        ->and($container->get(GeoApi::class))->toBeInstanceOf(GeoClient::class)
        ->and($container->get(ApiGouvClient::class)->geo())->toBe($container->get(GeoApi::class))
        ->and($container->get(Transport::class))->toBeInstanceOf(Psr18Transport::class)
        ->and($container->getParameter('api_gouv.entreprises.base_url'))->toBe('https://recherche-entreprises.api.gouv.fr')
        ->and($container->getParameter('api_gouv.adresse.base_url'))->toBe('https://data.geopf.fr/geocodage')
        ->and($container->getParameter('api_gouv.geo.base_url'))->toBe('https://geo.api.gouv.fr')
        ->and($container->getParameter('api_gouv.entreprises.cache_ttl'))->toBe(3600)
        ->and($container->getParameter('api_gouv.geo.cache_ttl'))->toBe(86400)
        ->and($container->getParameter('api_gouv.cache.enabled'))->toBeFalse()
        ->and($container->getParameter('api_gouv.fake'))->toBeFalse()
        ->and($container->has('api_gouv.cache'))->toBeFalse();
});

it('autowires the clients into an application service', function () {
    $this->boot(exposeServices: false, autowiredConsumer: true);

    $consumer = $this->kernel->getContainer()->get(AutowiredConsumer::class);
    assert($consumer instanceof AutowiredConsumer);

    expect($consumer->entreprises)->toBeInstanceOf(EntreprisesClient::class)
        ->and($consumer->adresse)->toBeInstanceOf(AdresseClient::class)
        ->and($consumer->geo)->toBeInstanceOf(GeoClient::class)
        ->and($consumer->client)->toBeInstanceOf(ApiGouvClient::class)
        ->and($consumer->client->geo())->toBe($consumer->geo);
});

it('calls the API through the application http client', function () {
    MockResponses::$queue[] = new MockResponse(loadFixture('entreprises_siren.json'));
    $container = $this->boot();

    $entreprise = $container->get(EntreprisesApi::class)->parSiren('812487973');

    expect($entreprise->nomComplet)->toBe('OCTO')
        ->and(MockResponses::$urls)->toHaveCount(1)
        ->and(MockResponses::$urls[0])->toStartWith('https://recherche-entreprises.api.gouv.fr/search?');
});

it('uses the configured base url', function () {
    MockResponses::$queue[] = new MockResponse(loadFixture('geo_region.json'));
    $container = $this->boot(['geo' => ['base_url' => 'https://custom.test/geo']]);

    $container->get(GeoApi::class)->region('32');

    expect(MockResponses::$urls[0])->toStartWith('https://custom.test/geo/regions/32');
});

it('rejects an empty base url', function () {
    $this->boot(['adresse' => ['base_url' => '']]);
})->throws(InvalidConfigurationException::class);

it('caches successful answers when the cache is enabled', function () {
    MockResponses::$queue[] = new MockResponse(loadFixture('geo_regions.json'));
    $container = $this->boot(['cache' => ['enabled' => true]]);

    $container->get(GeoApi::class)->regions();
    $container->get(GeoApi::class)->regions();

    expect(MockResponses::$urls)->toHaveCount(1)
        ->and($container->get('api_gouv.psr16_cache'))->toBeInstanceOf(Psr16Cache::class)
        ->and($container->getParameter('api_gouv.cache.enabled'))->toBeTrue();
});

it('wraps the configured cache pool', function () {
    $container = $this->boot(['cache' => ['enabled' => true, 'pool' => 'cache.system']]);

    $cache = $container->get('api_gouv.psr16_cache');
    $pool = (new ReflectionProperty(Psr16Cache::class, 'pool'))->getValue($cache);

    expect($pool)->toBe($container->get('cache.system'));
});

it('explains what to install when the http client is missing', function () {
    $this->boot(bundleClass: NoHttpClientBundle::class);
})->throws(LogicException::class, 'symfony/http-client');
