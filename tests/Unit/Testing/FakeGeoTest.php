<?php

declare(strict_types=1);

use Kaveraa\ApiGouv\Coordonnees;
use Kaveraa\ApiGouv\Exceptions\NotFoundException;
use Kaveraa\ApiGouv\Testing\Factories;
use Kaveraa\ApiGouv\Testing\FakeApiGouv;
use Kaveraa\ApiGouv\Testing\FakeGeo;

it('builds geo objects from coherent defaults and overrides', function () {
    $commune = Factories::commune();
    $departement = Factories::departement();
    $region = Factories::region();
    $epci = Factories::epci(['nom' => 'CC Test']);

    expect($commune->code)->toBe('80021')
        ->and($commune->nom)->toBe('Amiens')
        ->and($commune->codeDepartement)->toBe($departement->code)
        ->and($commune->codeRegion)->toBe($region->code)
        ->and($commune->codeEpci)->toBe($epci->code)
        ->and($commune->centre)->toBeInstanceOf(Coordonnees::class)
        ->and($departement->codeRegion)->toBe($region->code)
        ->and($epci->nom)->toBe('CC Test')
        ->and($epci->codesDepartements)->toBe([$departement->code]);
});

it('finds stored items by code and records the calls', function () {
    $fake = (new FakeGeo)->with(Factories::commune(), Factories::departement(), Factories::region(), Factories::epci());

    expect($fake->commune('80021')->nom)->toBe('Amiens')
        ->and($fake->commune(' 80021 ')->nom)->toBe('Amiens')
        ->and($fake->departement('80')->nom)->toBe('Somme')
        ->and($fake->region('32')->nom)->toBe('Hauts-de-France')
        ->and($fake->epci('248000531')->nom)->toBe('CA Amiens Métropole')
        ->and($fake->calls)->toBe([['commune', '80021'], ['commune', '80021'], ['departement', '80'], ['region', '32'], ['epci', '248000531']]);
});

it('throws NotFoundException for unknown codes', function () {
    $fake = new FakeGeo;

    expect(fn () => $fake->commune('80021'))->toThrow(NotFoundException::class)
        ->and(fn () => $fake->departement('80'))->toThrow(NotFoundException::class)
        ->and(fn () => $fake->region('32'))->toThrow(NotFoundException::class)
        ->and(fn () => $fake->epci('248000531'))->toThrow(NotFoundException::class)
        ->and(fn () => $fake->communesDuDepartement('80'))->toThrow(NotFoundException::class)
        ->and(fn () => $fake->departementsDeLaRegion('32'))->toThrow(NotFoundException::class);
});

it('rejects malformed input like the real client', function () {
    $fake = (new FakeGeo)->with(Factories::commune());

    expect(fn () => $fake->commune('8002'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => $fake->communesParCodePostal('abc'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => $fake->rechercherCommunes(' '))->toThrow(InvalidArgumentException::class)
        ->and(fn () => $fake->communeParCoordonnees(NAN, 0.0))->toThrow(InvalidArgumentException::class)
        ->and(fn () => $fake->region('1'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => $fake->epci('123'))->toThrow(InvalidArgumentException::class)
        ->and($fake->calls)->toBe([]);
});

it('filters communes by postal code, name and departement, and finds the nearest one', function () {
    $amiens = Factories::commune();
    $albert = Factories::commune(['code' => '80016', 'nom' => 'Albert', 'codesPostaux' => ['80300'], 'centre' => new Coordonnees(50.0, 2.65)]);
    $ajaccio = Factories::commune(['code' => '2A004', 'nom' => 'Ajaccio', 'codesPostaux' => ['20000'], 'codeDepartement' => '2A', 'codeRegion' => '94', 'centre' => new Coordonnees(41.93, 8.74)]);
    $fake = (new FakeGeo)->with($amiens, $albert, $ajaccio, Factories::departement(), Factories::departement(['code' => '2A', 'nom' => 'Corse-du-Sud', 'codeRegion' => '94']));

    expect($fake->communesParCodePostal('80300'))->toBe([$albert])
        ->and($fake->communesParCodePostal('99999'))->toBe([])
        ->and($fake->rechercherCommunes('a'))->toBe([$amiens, $albert, $ajaccio])
        ->and($fake->rechercherCommunes('a', 2))->toBe([$amiens, $albert])
        ->and($fake->rechercherCommunes('AJAC'))->toBe([$ajaccio])
        ->and($fake->communesDuDepartement('80'))->toBe([$amiens, $albert])
        ->and($fake->communesDuDepartement('2a'))->toBe([$ajaccio])
        ->and($fake->communeParCoordonnees(41.9, 8.7))->toBe($ajaccio)
        ->and($fake->communeParCoordonnees(49.9, 2.3))->toBe($amiens)
        ->and((new FakeGeo)->communeParCoordonnees(0.0, 0.0))->toBeNull();
});

it('lists departements, regions and EPCI', function () {
    $somme = Factories::departement();
    $corse = Factories::departement(['code' => '2A', 'nom' => 'Corse-du-Sud', 'codeRegion' => '94']);
    $hdf = Factories::region();
    $amiensMetropole = Factories::epci();
    $nordPicardie = Factories::epci(['code' => '200070951', 'nom' => 'CC du Territoire Nord Picardie']);
    $fake = (new FakeGeo)->with($somme, $corse, $hdf, $amiensMetropole, $nordPicardie);

    expect($fake->departements())->toBe([$somme, $corse])
        ->and($fake->regions())->toBe([$hdf])
        ->and($fake->departementsDeLaRegion('32'))->toBe([$somme])
        ->and($fake->epcisDuDepartement('80'))->toBe([$amiensMetropole, $nordPicardie])
        ->and($fake->epcisDuDepartement('2A'))->toBe([]);
});

it('groups the geo fake in FakeApiGouv', function () {
    expect((new FakeApiGouv)->geo())->toBeInstanceOf(FakeGeo::class);
});
