<?php

declare(strict_types=1);

use Kaveraa\ApiGouv\Adresse\AdresseClient;
use Kaveraa\ApiGouv\ApiGouvClient;
use Kaveraa\ApiGouv\Entreprises\EntreprisesClient;
use Kaveraa\ApiGouv\Http\Requester;
use Kaveraa\ApiGouv\Tests\Support\FakeTransport;

it('gives access to both clients', function () {
    $requester = new Requester(new FakeTransport, 'https://example.test');
    $entreprises = new EntreprisesClient($requester);
    $adresse = new AdresseClient($requester);

    $client = new ApiGouvClient($entreprises, $adresse);

    expect($client->entreprises())->toBe($entreprises)->and($client->adresse())->toBe($adresse);
});
