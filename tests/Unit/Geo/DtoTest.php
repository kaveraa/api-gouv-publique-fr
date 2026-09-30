<?php

declare(strict_types=1);

use Kaveraa\ApiGouv\Exceptions\InvalidResponseException;
use Kaveraa\ApiGouv\Geo\Commune;
use Kaveraa\ApiGouv\Geo\Departement;
use Kaveraa\ApiGouv\Geo\Epci;
use Kaveraa\ApiGouv\Geo\Region;

it('builds a Commune from the recorded response', function () {
    $commune = Commune::fromArray(json_decode(loadFixture('geo_commune.json'), true));

    expect($commune->code)->toBe('80021')
        ->and($commune->nom)->toBe('Amiens')
        ->and($commune->codesPostaux)->toBe(['80000', '80080', '80090'])
        ->and($commune->population)->toBe(136449)
        ->and($commune->codeDepartement)->toBe('80')
        ->and($commune->codeRegion)->toBe('32')
        ->and($commune->siren)->toBe('218000198')
        ->and($commune->codeEpci)->toBe('248000531')
        ->and($commune->centre?->latitude)->toBe(49.8987)
        ->and($commune->centre?->longitude)->toBe(2.2847)
        ->and($commune->score)->toBeNull();
});

it('keeps the score of a search by name', function () {
    $first = Commune::fromArray(json_decode(loadFixture('geo_communes_nom.json'), true)[0]);

    expect($first->code)->toBe('80021')->and($first->score)->toBeGreaterThan(1.0);
});

it('builds a Commune from minimal data', function () {
    $commune = Commune::fromArray(['code' => '2A004', 'nom' => 'Ajaccio']);

    expect($commune->codesPostaux)->toBe([])
        ->and($commune->population)->toBeNull()
        ->and($commune->codeDepartement)->toBeNull()
        ->and($commune->centre)->toBeNull()
        ->and($commune->score)->toBeNull();
});

it('gives a null centre when it is missing or malformed', function (mixed $centre) {
    expect(Commune::fromArray(['code' => '80021', 'nom' => 'Amiens', 'centre' => $centre])->centre)->toBeNull();
})->with([null, 'x', [[]], [['coordinates' => 'x']], [['coordinates' => [2.28]]], [['coordinates' => ['a', 'b']]]]);

it('raises InvalidResponseException when code or nom is missing', function (string $class, array $data) {
    $class::fromArray($data);
})->with([
    'commune without code' => [Commune::class, ['nom' => 'Amiens']],
    'commune without nom' => [Commune::class, ['code' => '80021']],
    'departement without code' => [Departement::class, ['nom' => 'Somme']],
    'region without nom' => [Region::class, ['code' => '32']],
    'epci without code' => [Epci::class, ['nom' => 'x']],
])->throws(InvalidResponseException::class);

it('builds a Departement, a Region and an Epci from the recorded responses', function () {
    $departement = Departement::fromArray(json_decode(loadFixture('geo_departement.json'), true));
    $region = Region::fromArray(json_decode(loadFixture('geo_region.json'), true));
    $epci = Epci::fromArray(json_decode(loadFixture('geo_epci.json'), true));

    expect($departement->code)->toBe('80')
        ->and($departement->nom)->toBe('Somme')
        ->and($departement->codeRegion)->toBe('32')
        ->and($region->code)->toBe('32')
        ->and($region->nom)->toBe('Hauts-de-France')
        ->and($epci->code)->toBe('248000531')
        ->and($epci->nom)->toBe('CA Amiens Métropole')
        ->and($epci->population)->toBe(182854)
        ->and($epci->codesDepartements)->toBe(['80'])
        ->and($epci->codesRegions)->toBe(['32']);
});

it('builds an Epci and a Departement from minimal data', function () {
    $epci = Epci::fromArray(['code' => '200070951', 'nom' => 'CC Nord Picardie']);
    $departement = Departement::fromArray(['code' => '2A', 'nom' => 'Corse-du-Sud']);

    expect($epci->population)->toBeNull()
        ->and($epci->codesDepartements)->toBe([])
        ->and($epci->codesRegions)->toBe([])
        ->and($departement->codeRegion)->toBeNull();
});
