<?php

declare(strict_types=1);

use Kaveraa\ApiGouv\Adresse\AdresseApi;
use Kaveraa\ApiGouv\ApiGouvClient;
use Kaveraa\ApiGouv\Entreprises\EntreprisesApi;
use Kaveraa\ApiGouv\Geo\GeoApi;
use Kaveraa\ApiGouv\Testing\Factories;
use Kaveraa\ApiGouv\Testing\FakeAdresse;
use Kaveraa\ApiGouv\Testing\FakeApiGouv;
use Kaveraa\ApiGouv\Testing\FakeEntreprises;
use Kaveraa\ApiGouv\Testing\FakeGeo;
use Kaveraa\ApiGouv\Tests\Symfony\MockResponses;

it('replaces the clients with the fakes', function () {
    $container = $this->boot(['fake' => true], mockHttp: false);

    expect($container->get(EntreprisesApi::class))->toBeInstanceOf(FakeEntreprises::class)
        ->and($container->get(AdresseApi::class))->toBeInstanceOf(FakeAdresse::class)
        ->and($container->get(GeoApi::class))->toBeInstanceOf(FakeGeo::class)
        ->and($container->get(ApiGouvClient::class))->toBeInstanceOf(FakeApiGouv::class)
        ->and($container->get(FakeApiGouv::class))->toBe($container->get(ApiGouvClient::class))
        ->and($container->get(FakeApiGouv::class)->geo())->toBe($container->get(GeoApi::class))
        ->and($container->getParameter('api_gouv.fake'))->toBeTrue()
        ->and($container->has('api_gouv.transport'))->toBeFalse()
        ->and($container->has('api_gouv.psr18_client'))->toBeFalse()
        ->and($container->has('api_gouv.requester.geo'))->toBeFalse();
});

it('is public from the real container', function () {
    $this->boot(['fake' => true], mockHttp: false, exposeServices: false);
    $container = $this->kernel->getContainer();

    $fake = $container->get(FakeApiGouv::class);

    expect($fake)->toBeInstanceOf(FakeApiGouv::class)
        ->and($container->get(ApiGouvClient::class))->toBe($fake);
});

it('serves the prepared objects and records the calls', function () {
    $container = $this->boot(['fake' => true]);
    $fake = $container->get(FakeApiGouv::class);
    $fake->entreprises()->with(Factories::entreprise(['siren' => '123456782', 'nomComplet' => 'ACME']));
    $fake->geo()->with(Factories::commune());

    expect($container->get(EntreprisesApi::class)->parSiren('123456782')->nomComplet)->toBe('ACME')
        ->and($container->get(GeoApi::class)->commune('80021')->nom)->toBe('Amiens')
        ->and($fake->entreprises()->calls)->toBe([['parSiren', '123456782']])
        ->and($fake->geo()->calls)->toBe([['commune', '80021']])
        ->and(MockResponses::$urls)->toBe([]);
});

it('ignores the cache setting in fake mode', function () {
    $container = $this->boot(['fake' => true, 'cache' => ['enabled' => true]], mockHttp: false);

    expect($container->has('api_gouv.cache'))->toBeFalse()
        ->and($container->get(GeoApi::class))->toBeInstanceOf(FakeGeo::class);
});
