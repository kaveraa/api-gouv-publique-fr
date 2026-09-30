<?php

declare(strict_types=1);

use Kaveraa\ApiGouv\Entreprises\EntreprisesClient;
use Kaveraa\ApiGouv\Entreprises\SearchQuery;
use Kaveraa\ApiGouv\Exceptions\NotFoundException;
use Kaveraa\ApiGouv\Http\Requester;
use Kaveraa\ApiGouv\Tests\Support\FakeTransport;

function entreprisesClient(FakeTransport $transport): EntreprisesClient
{
    return new EntreprisesClient(new Requester($transport, 'https://recherche-entreprises.api.gouv.fr'));
}

it('finds a company by SIREN', function () {
    $transport = new FakeTransport(FakeTransport::json(loadFixture('entreprises_siren.json')));

    $entreprise = entreprisesClient($transport)->parSiren('812 487 973');

    expect($entreprise->nomComplet)->toBe('OCTO')
        ->and($transport->calls[0][0])->toBe('https://recherche-entreprises.api.gouv.fr/search')
        ->and($transport->calls[0][1])->toBe(['q' => '812487973', 'page' => 1, 'per_page' => 1]);
});

it('accepts non-breaking spaces in a SIREN', function () {
    $transport = new FakeTransport(FakeTransport::json(loadFixture('entreprises_siren.json')));

    $entreprise = entreprisesClient($transport)->parSiren("812\u{A0}487\u{A0}973");

    expect($entreprise->nomComplet)->toBe('OCTO')
        ->and($transport->calls[0][1]['q'])->toBe('812487973');
});

it('throws NotFoundException when the API returns 200 with no result', function () {
    entreprisesClient(new FakeTransport(FakeTransport::json(loadFixture('entreprises_empty.json'))))->parSiren('000000000');
})->throws(NotFoundException::class);

it('throws NotFoundException when the first result is another company', function () {
    // The API can return a fuzzy match. Only an exact SIREN counts.
    entreprisesClient(new FakeTransport(FakeTransport::json(loadFixture('entreprises_search.json'))))->parSiren('999999999');
})->throws(NotFoundException::class);

it('rejects a SIREN that is not 9 digits before calling the API', function (string $siren) {
    $transport = new FakeTransport;

    try {
        entreprisesClient($transport)->parSiren($siren);
        $this->fail('Expected InvalidArgumentException');
    } catch (InvalidArgumentException) {
        expect($transport->calls)->toBe([]);
    }
})->with(['', '12345', 'abcdefghi', '1234567890']);

it('finds an establishment by SIRET', function () {
    $transport = new FakeTransport(FakeTransport::json(loadFixture('entreprises_siret.json')));

    $etablissement = entreprisesClient($transport)->parSiret('41816609600010');

    expect($etablissement->siret)->toBe('41816609600010')
        ->and($etablissement->siren)->toBe('418166096')
        ->and($transport->calls[0][1]['q'])->toBe('41816609600010');
});

it('throws NotFoundException for an unknown SIRET', function () {
    entreprisesClient(new FakeTransport(FakeTransport::json(loadFixture('entreprises_empty.json'))))->parSiret('00000000000000');
})->throws(NotFoundException::class);

it('searches with a string or a SearchQuery and returns pagination', function () {
    $transport = new FakeTransport(
        FakeTransport::json(loadFixture('entreprises_search.json')),
        FakeTransport::json(loadFixture('entreprises_search.json')),
    );
    $client = entreprisesClient($transport);

    $result = $client->rechercher('octo');
    $client->rechercher(new SearchQuery('octo', perPage: 2, codePostal: '75001'));

    expect($result->results)->toHaveCount(2)
        ->and($result->page)->toBe(1)
        ->and($result->perPage)->toBe(2)
        ->and($result->total)->toBeGreaterThan(2)
        ->and($result->totalPages)->toBeGreaterThan(1)
        ->and($transport->calls[0][1])->toBe(['q' => 'octo', 'page' => 1, 'per_page' => 10])
        ->and($transport->calls[1][1]['code_postal'])->toBe('75001');
});

it('returns an empty result for a search with no match', function () {
    $result = entreprisesClient(new FakeTransport(FakeTransport::json(loadFixture('entreprises_empty.json'))))->rechercher('zzzz');

    expect($result->results)->toBe([])->and($result->total)->toBe(0);
});
