<?php

declare(strict_types=1);

use Kaveraa\ApiGouv\Adresse\AdresseClient;
use Kaveraa\ApiGouv\ApiGouvClient;
use Kaveraa\ApiGouv\Entreprises\EntreprisesClient;
use Kaveraa\ApiGouv\Geo\GeoClient;
use Kaveraa\ApiGouv\Http\Requester;
use Kaveraa\ApiGouv\Tests\Support\FakeTransport;

it('gives access to the three clients', function () {
    $requester = new Requester(new FakeTransport, 'https://example.test');
    $entreprises = new EntreprisesClient($requester);
    $adresse = new AdresseClient($requester);
    $geo = new GeoClient($requester);

    $client = new ApiGouvClient($entreprises, $adresse, $geo);

    expect($client->entreprises())->toBe($entreprises)
        ->and($client->adresse())->toBe($adresse)
        ->and($client->geo())->toBe($geo);
});

it('requires the geo client', function () {
    $requester = new Requester(new FakeTransport, 'https://example.test');

    expect((new ReflectionClass(ApiGouvClient::class))->getConstructor()?->getNumberOfRequiredParameters())->toBe(3);
    expect(fn () => new ApiGouvClient(new EntreprisesClient($requester), new AdresseClient($requester)))->toThrow(ArgumentCountError::class);
});
