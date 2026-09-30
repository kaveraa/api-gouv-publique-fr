<?php

declare(strict_types=1);

use Kaveraa\ApiGouv\Adresse\Adresse;
use Kaveraa\ApiGouv\Adresse\AdresseClient;
use Kaveraa\ApiGouv\Exceptions\InvalidResponseException;
use Kaveraa\ApiGouv\Http\Requester;
use Kaveraa\ApiGouv\Tests\Support\FakeTransport;

function adresseClient(FakeTransport $transport): AdresseClient
{
    return new AdresseClient(new Requester($transport, 'https://data.geopf.fr/geocodage'));
}

it('searches addresses and maps the first feature', function () {
    $transport = new FakeTransport(FakeTransport::json(loadFixture('adresse_search.json')));

    $adresses = adresseClient($transport)->rechercher('8 bd du port amiens', 1);

    expect($adresses)->toHaveCount(1)
        ->and($adresses[0])->toBeInstanceOf(Adresse::class)
        ->and($adresses[0]->label)->toBe('8 Boulevard du Port 80000 Amiens')
        ->and($adresses[0]->numero)->toBe('8')
        ->and($adresses[0]->rue)->toBe('Boulevard du Port')
        ->and($adresses[0]->codePostal)->toBe('80000')
        ->and($adresses[0]->codeCommune)->toBe('80021')
        ->and($adresses[0]->commune)->toBe('Amiens')
        ->and($adresses[0]->type)->toBe('housenumber')
        ->and($adresses[0]->coordonnees->longitude)->toBe(2.290084)
        ->and($adresses[0]->coordonnees->latitude)->toBe(49.897442)
        ->and($transport->calls[0][0])->toBe('https://data.geopf.fr/geocodage/search')
        ->and($transport->calls[0][1])->toBe(['q' => '8 bd du port amiens', 'limit' => 1, 'autocomplete' => 0]);
});

it('uses the autocomplete mode for autocompleter', function () {
    $transport = new FakeTransport(FakeTransport::json(loadFixture('adresse_search.json')));

    adresseClient($transport)->autocompleter('8 bd du po');

    expect($transport->calls[0][1])->toBe(['q' => '8 bd du po', 'limit' => 5, 'autocomplete' => 1]);
});

it('returns an empty list when nothing matches', function () {
    $adresses = adresseClient(new FakeTransport(FakeTransport::json(loadFixture('adresse_empty.json'))))->rechercher('zzzzzzqqqq');

    expect($adresses)->toBe([]);
});

it('passes text with accents and symbols to the transport untouched', function () {
    $transport = new FakeTransport(FakeTransport::json(loadFixture('adresse_empty.json')));

    adresseClient($transport)->rechercher("rue de l'Eglise & co, Ecully");

    expect($transport->calls[0][1]['q'])->toBe("rue de l'Eglise & co, Ecully");
});

it('reverse geocodes a point', function () {
    $transport = new FakeTransport(FakeTransport::json(loadFixture('adresse_reverse.json')));

    $adresse = adresseClient($transport)->geocoderInverse(49.897442, 2.290084);

    expect($adresse?->commune)->toBe('Amiens')
        ->and($transport->calls[0][0])->toBe('https://data.geopf.fr/geocodage/reverse')
        ->and($transport->calls[0][1])->toBe(['lat' => 49.897442, 'lon' => 2.290084, 'limit' => 1]);
});

it('returns null when reverse geocoding finds nothing', function () {
    $empty = '{"type":"FeatureCollection","features":[]}';

    expect(adresseClient(new FakeTransport(FakeTransport::json($empty)))->geocoderInverse(0.0, 0.0))->toBeNull();
});

it('rejects an empty query and a limit outside 1..50', function (string $query, int $limit) {
    adresseClient(new FakeTransport)->rechercher($query, $limit);
})->with([['', 5], ['x', 0], ['x', 51]])->throws(InvalidArgumentException::class);

it('rejects coordinates outside the valid range', function (float $lat, float $lon) {
    adresseClient(new FakeTransport)->geocoderInverse($lat, $lon);
})->with([[91.0, 0.0], [-91.0, 0.0], [0.0, 181.0], [0.0, -181.0]])->throws(InvalidArgumentException::class);

it('raises InvalidResponseException for a feature without a point', function () {
    $broken = '{"type":"FeatureCollection","features":[{"type":"Feature","properties":{"label":"x"}}]}';

    adresseClient(new FakeTransport(FakeTransport::json($broken)))->rechercher('x');
})->throws(InvalidResponseException::class);
