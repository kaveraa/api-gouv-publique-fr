<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Testing;

use DateTimeImmutable;
use Kaveraa\ApiGouv\Adresse\Adresse;
use Kaveraa\ApiGouv\Adresse\Coordonnees;
use Kaveraa\ApiGouv\Entreprises\Dirigeant;
use Kaveraa\ApiGouv\Entreprises\Entreprise;
use Kaveraa\ApiGouv\Entreprises\Etablissement;

final class Factories
{
    /** @param array{siret?: string, siren?: string, estSiege?: bool, etatAdministratif?: ?string, adresse?: ?string, codePostal?: ?string, commune?: ?string, codeCommune?: ?string, activitePrincipale?: ?string, dateCreation?: ?DateTimeImmutable, latitude?: ?float, longitude?: ?float, enseignes?: list<string>} $attributes Constructor argument names of the DTO. */
    public static function etablissement(array $attributes = []): Etablissement
    {
        return new Etablissement(...array_merge([
            'siret' => '81248797300040',
            'siren' => '812487973',
            'estSiege' => true,
            'etatAdministratif' => 'A',
            'adresse' => '1 RUE DE TEST 75001 PARIS',
            'codePostal' => '75001',
            'commune' => 'PARIS',
            'codeCommune' => '75101',
            'activitePrincipale' => '62.01Z',
            'dateCreation' => new DateTimeImmutable('2015-06-30'),
            'latitude' => 48.86,
            'longitude' => 2.34,
            'enseignes' => [],
        ], $attributes));
    }

    /** @param array{siren?: string, nomComplet?: string, sigle?: ?string, activitePrincipale?: ?string, categorie?: ?string, natureJuridique?: ?string, etatAdministratif?: ?string, dateCreation?: ?DateTimeImmutable, trancheEffectif?: ?string, nombreEtablissements?: int, nombreEtablissementsOuverts?: int, siege?: ?Etablissement, dirigeants?: list<Dirigeant>, etablissementsCorrespondants?: list<Etablissement>} $attributes Constructor argument names of the DTO. */
    public static function entreprise(array $attributes = []): Entreprise
    {
        return new Entreprise(...array_merge([
            'siren' => '812487973',
            'nomComplet' => 'ACME',
            'sigle' => null,
            'activitePrincipale' => '62.01Z',
            'categorie' => 'PME',
            'natureJuridique' => '5710',
            'etatAdministratif' => 'A',
            'dateCreation' => new DateTimeImmutable('2015-06-30'),
            'trancheEffectif' => '12',
            'nombreEtablissements' => 1,
            'nombreEtablissementsOuverts' => 1,
            'siege' => self::etablissement(),
            'dirigeants' => [],
            'etablissementsCorrespondants' => [],
        ], $attributes));
    }

    /** @param array{id?: string, label?: string, numero?: ?string, rue?: ?string, nom?: ?string, codePostal?: ?string, codeCommune?: ?string, commune?: ?string, contexte?: ?string, type?: ?string, score?: ?float, coordonnees?: Coordonnees} $attributes Constructor argument names of the DTO. */
    public static function adresse(array $attributes = []): Adresse
    {
        return new Adresse(...array_merge([
            'id' => '75101_1234_00001',
            'label' => '1 Rue de Test 75001 Paris',
            'numero' => '1',
            'rue' => 'Rue de Test',
            'nom' => '1 Rue de Test',
            'codePostal' => '75001',
            'codeCommune' => '75101',
            'commune' => 'Paris',
            'contexte' => '75, Paris, Ile-de-France',
            'type' => 'housenumber',
            'score' => 0.9,
            'coordonnees' => new Coordonnees(latitude: 48.86, longitude: 2.34),
        ], $attributes));
    }
}
