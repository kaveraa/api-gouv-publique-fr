<?php

declare(strict_types=1);

use Kaveraa\ApiGouv\Adresse\AdresseClient;
use Kaveraa\ApiGouv\ApiGouvClient;
use Kaveraa\ApiGouv\Entreprises\EntreprisesClient;
use Kaveraa\ApiGouv\Geo\GeoClient;
use Kaveraa\ApiGouv\Http\Requester;
use Kaveraa\ApiGouv\Tests\Support\FakeTransport;

it('gives access to the geo client', function () {
    $requester = new Requester(new FakeTransport, 'https://example.test');
    $geo = new GeoClient($requester);

    $client = new ApiGouvClient(new EntreprisesClient($requester), new AdresseClient($requester), $geo);

    expect($client->geo())->toBe($geo);
});

it('explains when the geo client is missing', function () {
    $requester = new Requester(new FakeTransport, 'https://example.test');

    $client = new ApiGouvClient(new EntreprisesClient($requester), new AdresseClient($requester));

    expect(fn () => $client->geo())->toThrow(LogicException::class, 'The Geo client is not configured.');
});
