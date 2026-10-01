<?php

declare(strict_types=1);

use Kaveraa\ApiGouv\Coordonnees;
use Kaveraa\ApiGouv\Entreprises\Dirigeant;
use Kaveraa\ApiGouv\Entreprises\Entreprise;
use Kaveraa\ApiGouv\Entreprises\SearchResult;
use Kaveraa\ApiGouv\Geo\Commune;
use Kaveraa\ApiGouv\Geo\Epci;
use Kaveraa\ApiGouv\Testing\Factories;

it('builds a Dirigeant with defaults and overrides', function () {
    $default = Factories::dirigeant();
    $custom = Factories::dirigeant(['type' => 'personne morale', 'denomination' => 'DEIXIS', 'siren' => '508228426', 'nom' => null, 'prenoms' => null]);

    expect($default)->toBeInstanceOf(Dirigeant::class)
        ->and($default->type)->toBe('personne physique')
        ->and($default->nom)->toBe('HIDIER')
        ->and($custom->type)->toBe('personne morale')
        ->and($custom->denomination)->toBe('DEIXIS')
        ->and($custom->nom)->toBeNull();
});

it('builds a SearchResult around one company by default', function () {
    $default = Factories::searchResult();
    $acme = Factories::entreprise(['nomComplet' => 'ACME']);
    $custom = Factories::searchResult(['results' => [$acme, $acme], 'total' => 2, 'perPage' => 2]);

    expect($default)->toBeInstanceOf(SearchResult::class)
        ->and($default->results)->toHaveCount(1)
        ->and($default->results[0])->toBeInstanceOf(Entreprise::class)
        ->and($default->total)->toBe(1)
        ->and($default->page)->toBe(1)
        ->and($default->perPage)->toBe(10)
        ->and($default->totalPages)->toBe(1)
        ->and($custom->results)->toBe([$acme, $acme])
        ->and($custom->perPage)->toBe(2);
});

it('builds Coordonnees with the Paris default', function () {
    expect(Factories::coordonnees())->toEqual(new Coordonnees(48.86, 2.34))
        ->and(Factories::coordonnees(['latitude' => 45.76])->latitude)->toBe(45.76)
        ->and(Factories::etablissement()->coordonnees)->toEqual(Factories::coordonnees())
        ->and(Factories::adresse()->coordonnees)->toEqual(Factories::coordonnees());
});

it('lets the package build data objects with the required fields only', function () {
    $commune = new Commune('80021', 'Amiens');
    $entreprise = new Entreprise('812487973', 'OCTO');

    expect($commune->codesPostaux)->toBe([])
        ->and($commune->centre)->toBeNull()
        ->and((new Epci('248000531', 'CA Amiens Métropole'))->codesDepartements)->toBe([])
        ->and((new Dirigeant)->type)->toBe('inconnu')
        ->and($entreprise->dirigeants)->toBe([])
        ->and($entreprise->nombreEtablissements)->toBeNull();
});
