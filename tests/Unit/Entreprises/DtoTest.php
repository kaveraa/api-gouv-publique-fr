<?php

declare(strict_types=1);

use Kaveraa\ApiGouv\Entreprises\Dirigeant;
use Kaveraa\ApiGouv\Entreprises\Entreprise;
use Kaveraa\ApiGouv\Entreprises\Etablissement;
use Kaveraa\ApiGouv\Exceptions\InvalidResponseException;

it('builds an Entreprise from the recorded response', function () {
    $data = json_decode(loadFixture('entreprises_siren.json'), true)['results'][0];

    $entreprise = Entreprise::fromArray($data);

    expect($entreprise->siren)->toBe('812487973')
        ->and($entreprise->nomComplet)->toBe('OCTO')
        ->and($entreprise->categorie)->toBe('PME')
        ->and($entreprise->etatAdministratif)->toBe('A')
        ->and($entreprise->dateCreation?->format('Y-m-d'))->toBe('2015-06-30')
        ->and($entreprise->nombreEtablissements)->toBe(6)
        ->and($entreprise->siege)->toBeInstanceOf(Etablissement::class)
        ->and($entreprise->siege->siret)->toBe('81248797300040')
        ->and($entreprise->siege->siren)->toBe('812487973')
        ->and($entreprise->siege->estSiege)->toBeTrue()
        ->and($entreprise->siege->codePostal)->toBe('33300')
        ->and($entreprise->siege->commune)->toBe('BORDEAUX')
        ->and($entreprise->siege->latitude)->toBe(44.870115287)
        ->and($entreprise->siege->enseignes)->toBe(['SUPMODE'])
        ->and($entreprise->dirigeants)->toHaveCount(3)
        ->and($entreprise->dirigeants[0])->toBeInstanceOf(Dirigeant::class)
        ->and($entreprise->dirigeants[0]->nom)->toBe('HIDIER')
        ->and($entreprise->dirigeants[2]->type)->toBe('personne morale')
        ->and($entreprise->dirigeants[2]->denomination)->toBe('DEIXIS');
});

it('builds an Entreprise from minimal data', function () {
    $entreprise = Entreprise::fromArray(['siren' => '123456789', 'nom_complet' => 'ACME']);

    expect($entreprise->siege)->toBeNull()
        ->and($entreprise->dirigeants)->toBe([])
        ->and($entreprise->etablissementsCorrespondants)->toBe([])
        ->and($entreprise->dateCreation)->toBeNull()
        ->and($entreprise->nombreEtablissements)->toBe(0);
});

it('handles null coordinates and enseignes in an Etablissement', function () {
    $etablissement = Etablissement::fromArray(['siret' => '81248797300040', 'latitude' => null, 'longitude' => null, 'liste_enseignes' => null]);

    expect($etablissement->latitude)->toBeNull()
        ->and($etablissement->longitude)->toBeNull()
        ->and($etablissement->enseignes)->toBe([])
        ->and($etablissement->estSiege)->toBeFalse();
});

it('raises InvalidResponseException when a key field is missing', function (string $class, array $data) {
    $class::fromArray($data);
})->with([
    'entreprise without siren' => [Entreprise::class, ['nom_complet' => 'ACME']],
    'etablissement without siret' => [Etablissement::class, ['adresse' => 'x']],
])->throws(InvalidResponseException::class);
