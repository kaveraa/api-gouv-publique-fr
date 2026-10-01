<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Testing;

use DateTimeImmutable;
use Kaveraa\ApiGouv\Adresse\Adresse;
use Kaveraa\ApiGouv\Coordonnees;
use Kaveraa\ApiGouv\Entreprises\Dirigeant;
use Kaveraa\ApiGouv\Entreprises\Entreprise;
use Kaveraa\ApiGouv\Entreprises\Etablissement;
use Kaveraa\ApiGouv\Entreprises\SearchResult;
use Kaveraa\ApiGouv\Geo\Commune;
use Kaveraa\ApiGouv\Geo\Departement;
use Kaveraa\ApiGouv\Geo\Epci;
use Kaveraa\ApiGouv\Geo\Region;

final class Factories
{
    /** @param array{siret?: string, siren?: string, estSiege?: bool, etatAdministratif?: ?string, adresse?: ?string, codePostal?: ?string, commune?: ?string, codeCommune?: ?string, activitePrincipale?: ?string, dateCreation?: ?DateTimeImmutable, coordonnees?: ?Coordonnees, enseignes?: list<string>} $attributes Constructor argument names of the DTO. */
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
            'coordonnees' => self::coordonnees(),
            'enseignes' => [],
        ], $attributes));
    }

    /** @param array{siren?: string, nomComplet?: string, sigle?: ?string, activitePrincipale?: ?string, categorie?: ?string, natureJuridique?: ?string, etatAdministratif?: ?string, dateCreation?: ?DateTimeImmutable, trancheEffectif?: ?string, nombreEtablissements?: ?int, nombreEtablissementsOuverts?: ?int, siege?: ?Etablissement, dirigeants?: list<Dirigeant>, etablissementsCorrespondants?: list<Etablissement>} $attributes Constructor argument names of the DTO. */
    public static function entreprise(array $attributes = []): Entreprise
    {
        // A changed SIREN must not keep the default head office of another company.
        if (isset($attributes['siren']) && ! array_key_exists('siege', $attributes)) {
            $attributes['siege'] = self::etablissement(['siren' => $attributes['siren'], 'siret' => $attributes['siren'].'00040']);
        }

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
            'coordonnees' => self::coordonnees(),
        ], $attributes));
    }

    /** @param array{code?: string, nom?: string, codesPostaux?: list<string>, population?: ?int, codeDepartement?: ?string, codeRegion?: ?string, siren?: ?string, codeEpci?: ?string, centre?: ?Coordonnees, score?: ?float} $attributes Constructor argument names of the DTO. */
    public static function commune(array $attributes = []): Commune
    {
        return new Commune(...array_merge([
            'code' => '80021',
            'nom' => 'Amiens',
            'codesPostaux' => ['80000', '80080', '80090'],
            'population' => 136449,
            'codeDepartement' => '80',
            'codeRegion' => '32',
            'siren' => '218000198',
            'codeEpci' => '248000531',
            'centre' => new Coordonnees(latitude: 49.8987, longitude: 2.2847),
            'score' => null,
        ], $attributes));
    }

    /** @param array{code?: string, nom?: string, codeRegion?: ?string} $attributes Constructor argument names of the DTO. */
    public static function departement(array $attributes = []): Departement
    {
        return new Departement(...array_merge([
            'code' => '80',
            'nom' => 'Somme',
            'codeRegion' => '32',
        ], $attributes));
    }

    /** @param array{code?: string, nom?: string} $attributes Constructor argument names of the DTO. */
    public static function region(array $attributes = []): Region
    {
        return new Region(...array_merge([
            'code' => '32',
            'nom' => 'Hauts-de-France',
        ], $attributes));
    }

    /** @param array{code?: string, nom?: string, population?: ?int, codesDepartements?: list<string>, codesRegions?: list<string>} $attributes Constructor argument names of the DTO. */
    public static function epci(array $attributes = []): Epci
    {
        return new Epci(...array_merge([
            'code' => '248000531',
            'nom' => 'CA Amiens Métropole',
            'population' => 182854,
            'codesDepartements' => ['80'],
            'codesRegions' => ['32'],
        ], $attributes));
    }

    /** @param array{type?: string, qualite?: ?string, nom?: ?string, prenoms?: ?string, denomination?: ?string, siren?: ?string, anneeDeNaissance?: ?string} $attributes Constructor argument names of the DTO. */
    public static function dirigeant(array $attributes = []): Dirigeant
    {
        return new Dirigeant(...array_merge([
            'type' => 'personne physique',
            'qualite' => 'Président',
            'nom' => 'HIDIER',
            'prenoms' => 'BRUNO',
            'denomination' => null,
            'siren' => null,
            'anneeDeNaissance' => '1972',
        ], $attributes));
    }

    /** @param array{results?: list<Entreprise>, total?: int, page?: int, perPage?: int, totalPages?: int} $attributes Constructor argument names of the DTO. */
    public static function searchResult(array $attributes = []): SearchResult
    {
        return new SearchResult(...array_merge([
            'results' => [self::entreprise()],
            'total' => 1,
            'page' => 1,
            'perPage' => 10,
            'totalPages' => 1,
        ], $attributes));
    }

    /** @param array{latitude?: float, longitude?: float} $attributes Constructor argument names of the DTO. */
    public static function coordonnees(array $attributes = []): Coordonnees
    {
        return new Coordonnees(...array_merge([
            'latitude' => 48.86,
            'longitude' => 2.34,
        ], $attributes));
    }
}
