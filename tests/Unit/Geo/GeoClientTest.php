<?php

declare(strict_types=1);

use Kaveraa\ApiGouv\Exceptions\NotFoundException;
use Kaveraa\ApiGouv\Geo\Commune;
use Kaveraa\ApiGouv\Geo\GeoClient;
use Kaveraa\ApiGouv\Http\Requester;
use Kaveraa\ApiGouv\Tests\Support\FakeTransport;

const GEO_FIELDS = 'code,nom,codesPostaux,population,codeDepartement,codeRegion,siren,codeEpci,centre';

function geoClient(FakeTransport $transport): GeoClient
{
    return new GeoClient(new Requester($transport, 'https://geo.api.gouv.fr'));
}

it('finds a commune by INSEE code', function () {
    $transport = new FakeTransport(FakeTransport::json(loadFixture('geo_commune.json')));

    $commune = geoClient($transport)->commune('80021');

    expect($commune->nom)->toBe('Amiens')
        ->and($transport->calls[0])->toBe(['https://geo.api.gouv.fr/communes/80021', ['fields' => GEO_FIELDS]]);
});

it('accepts Corsica and overseas codes and sends them upper-cased', function (string $input, string $sent) {
    $transport = new FakeTransport(FakeTransport::json(loadFixture('geo_commune.json')));

    geoClient($transport)->commune($input);

    expect($transport->calls[0][0])->toBe('https://geo.api.gouv.fr/communes/'.$sent);
})->with([['2A004', '2A004'], [' 2a004 ', '2A004'], ['97101', '97101'], ["80\u{A0}021", '80021']]);

it('throws NotFoundException on a 404 with a plain text body', function () {
    geoClient(new FakeTransport(FakeTransport::json('Not Found', 404)))->commune('99999');
})->throws(NotFoundException::class);

it('rejects an invalid INSEE code before calling the API', function (string $code) {
    $transport = new FakeTransport;

    expect(fn () => geoClient($transport)->commune($code))->toThrow(InvalidArgumentException::class)
        ->and($transport->calls)->toBe([]);
})->with(['', '8002', 'ABCDE', '2C004']);

it('lists the communes of a postal code, all of them and in order', function () {
    $raw = json_decode(loadFixture('geo_communes_cp.json'), true);
    $transport = new FakeTransport(FakeTransport::json(loadFixture('geo_communes_cp.json')));

    $communes = geoClient($transport)->communesParCodePostal('80300');

    expect($communes)->toHaveCount(35)
        ->and(array_map(fn (Commune $c) => $c->code, $communes))->toBe(array_column($raw, 'code'))
        ->and($communes[0]->nom)->toBe('Albert')
        ->and($transport->calls[0][1])->toBe(['codePostal' => '80300', 'fields' => GEO_FIELDS]);
});

it('returns an empty list for a postal code without communes', function () {
    expect(geoClient(new FakeTransport(FakeTransport::json(loadFixture('geo_empty.json'))))->communesParCodePostal('99999'))->toBe([]);
});

it('rejects an invalid postal code before calling the API', function () {
    $transport = new FakeTransport;

    expect(fn () => geoClient($transport)->communesParCodePostal('8000'))->toThrow(InvalidArgumentException::class)
        ->and($transport->calls)->toBe([]);
});

it('searches communes by name with a population boost', function () {
    $transport = new FakeTransport(FakeTransport::json(loadFixture('geo_communes_nom.json')));

    $communes = geoClient($transport)->rechercherCommunes('amiens', 2);

    expect($communes)->toHaveCount(2)
        ->and($communes[0]->code)->toBe('80021')
        ->and($communes[0]->score)->toBeGreaterThan($communes[1]->score ?? 0.0)
        ->and($transport->calls[0][1])->toBe(['nom' => 'amiens', 'boost' => 'population', 'limit' => 2, 'fields' => GEO_FIELDS]);
});

it('uses a default limit of 10 for a name search', function () {
    $transport = new FakeTransport(FakeTransport::json(loadFixture('geo_empty.json')));

    geoClient($transport)->rechercherCommunes('nulle part');

    expect($transport->calls[0][1]['limit'])->toBe(10);
});

it('rejects a blank name and a limit outside 1..50', function (string $nom, int $limit) {
    $transport = new FakeTransport;

    expect(fn () => geoClient($transport)->rechercherCommunes($nom, $limit))->toThrow(InvalidArgumentException::class)
        ->and($transport->calls)->toBe([]);
})->with([['  ', 10], ['amiens', 0], ['amiens', 51]]);

it('finds the commune of a point', function () {
    $transport = new FakeTransport(FakeTransport::json(loadFixture('geo_communes_latlon.json')));

    $commune = geoClient($transport)->communeParCoordonnees(49.897442, 2.290084);

    expect($commune?->nom)->toBe('Amiens')
        ->and($transport->calls[0][1])->toBe(['lat' => 49.897442, 'lon' => 2.290084, 'fields' => GEO_FIELDS]);
});

it('returns null for a point at sea', function () {
    expect(geoClient(new FakeTransport(FakeTransport::json(loadFixture('geo_empty.json'))))->communeParCoordonnees(0.0, 0.0))->toBeNull();
});

it('rejects NAN, INF and out of range coordinates before calling the API', function (float $lat, float $lon) {
    $transport = new FakeTransport;

    expect(fn () => geoClient($transport)->communeParCoordonnees($lat, $lon))->toThrow(InvalidArgumentException::class)
        ->and($transport->calls)->toBe([]);
})->with([[NAN, 2.0], [49.0, INF], [-91.0, 2.0], [49.0, 181.0]]);

it('lists all departements without query parameters', function () {
    $transport = new FakeTransport(FakeTransport::json(loadFixture('geo_departements.json')));

    $departements = geoClient($transport)->departements();

    expect($departements)->toHaveCount(101)
        ->and($departements[0]->code)->toBe('01')
        ->and($departements[0]->nom)->toBe('Ain')
        ->and($transport->calls[0])->toBe(['https://geo.api.gouv.fr/departements', []]);
});

it('finds a departement and upper-cases a Corsica code', function () {
    $transport = new FakeTransport(FakeTransport::json(loadFixture('geo_departement.json')), FakeTransport::json(loadFixture('geo_departement.json')));
    $client = geoClient($transport);

    $departement = $client->departement('80');
    $client->departement('2a');

    expect($departement->nom)->toBe('Somme')
        ->and($departement->codeRegion)->toBe('32')
        ->and($transport->calls[0][0])->toBe('https://geo.api.gouv.fr/departements/80')
        ->and($transport->calls[1][0])->toBe('https://geo.api.gouv.fr/departements/2A');
});

it('rejects an invalid departement code before calling the API', function (string $code) {
    $transport = new FakeTransport;

    expect(fn () => geoClient($transport)->departement($code))->toThrow(InvalidArgumentException::class)
        ->and($transport->calls)->toBe([]);
})->with(['1', '980', '2C']);

it('lists the communes of a departement without loss and in order', function () {
    $raw = json_decode(loadFixture('geo_dep_communes.json'), true);
    $transport = new FakeTransport(FakeTransport::json(loadFixture('geo_dep_communes.json')));

    $communes = geoClient($transport)->communesDuDepartement('80');

    expect($communes)->toHaveCount(771)
        ->and(array_map(fn (Commune $c) => $c->code, $communes))->toBe(array_column($raw, 'code'))
        ->and(array_unique(array_map(fn (Commune $c) => $c->codeDepartement, $communes)))->toBe(['80'])
        ->and($transport->calls[0])->toBe(['https://geo.api.gouv.fr/departements/80/communes', ['fields' => GEO_FIELDS]]);
});

it('throws NotFoundException for the communes of an unknown departement', function () {
    geoClient(new FakeTransport(FakeTransport::json('Not Found', 404)))->communesDuDepartement('99');
})->throws(NotFoundException::class);

it('lists regions and finds one', function () {
    $transport = new FakeTransport(FakeTransport::json(loadFixture('geo_regions.json')), FakeTransport::json(loadFixture('geo_region.json')));
    $client = geoClient($transport);

    $regions = $client->regions();
    $region = $client->region('32');

    expect($regions)->toHaveCount(18)
        ->and($region->nom)->toBe('Hauts-de-France')
        ->and($transport->calls[0])->toBe(['https://geo.api.gouv.fr/regions', []])
        ->and($transport->calls[1][0])->toBe('https://geo.api.gouv.fr/regions/32');
});

it('rejects a region code that is not 2 digits before calling the API', function (string $code) {
    $transport = new FakeTransport;

    expect(fn () => geoClient($transport)->region($code))->toThrow(InvalidArgumentException::class)
        ->and($transport->calls)->toBe([]);
})->with(['1', '032', 'AB']);

it('lists the departements of a region', function () {
    $transport = new FakeTransport(FakeTransport::json(loadFixture('geo_region_departements.json')));

    $departements = geoClient($transport)->departementsDeLaRegion('32');

    expect($departements)->toHaveCount(5)
        ->and(array_unique(array_map(fn ($d) => $d->codeRegion, $departements)))->toBe(['32'])
        ->and($transport->calls[0])->toBe(['https://geo.api.gouv.fr/regions/32/departements', []]);
});

it('throws NotFoundException for the departements of an unknown region', function () {
    geoClient(new FakeTransport(FakeTransport::json('Not Found', 404)))->departementsDeLaRegion('99');
})->throws(NotFoundException::class);

it('finds an EPCI and lists the EPCI of a departement', function () {
    $transport = new FakeTransport(FakeTransport::json(loadFixture('geo_epci.json')), FakeTransport::json(loadFixture('geo_epcis_dep.json')));
    $client = geoClient($transport);

    $epci = $client->epci('248000531');
    $epcis = $client->epcisDuDepartement('80');

    expect($epci->nom)->toBe('CA Amiens Métropole')
        ->and($epci->population)->toBe(182854)
        ->and($epcis)->toHaveCount(17)
        ->and($transport->calls[0][0])->toBe('https://geo.api.gouv.fr/epcis/248000531')
        ->and($transport->calls[1])->toBe(['https://geo.api.gouv.fr/epcis', ['codeDepartement' => '80']]);
});

it('returns an empty list for the EPCI of an unknown departement', function () {
    expect(geoClient(new FakeTransport(FakeTransport::json(loadFixture('geo_empty.json'))))->epcisDuDepartement('99'))->toBe([]);
});

it('rejects an invalid EPCI code before calling the API', function (string $code) {
    $transport = new FakeTransport;

    expect(fn () => geoClient($transport)->epci($code))->toThrow(InvalidArgumentException::class)
        ->and($transport->calls)->toBe([]);
})->with(['12345678', 'A48000531']);
