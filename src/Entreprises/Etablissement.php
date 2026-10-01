<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Entreprises;

use DateTimeImmutable;
use Kaveraa\ApiGouv\Coordonnees;
use Kaveraa\ApiGouv\Exceptions\InvalidResponseException;
use Kaveraa\ApiGouv\Support\Dates;
use Kaveraa\ApiGouv\Support\Payload;

final readonly class Etablissement
{
    /** @param list<string> $enseignes */
    public function __construct(
        public string $siret,
        public string $siren,
        public bool $estSiege,
        public ?string $etatAdministratif,
        public ?string $adresse,
        public ?string $codePostal,
        public ?string $commune,
        public ?string $codeCommune,
        public ?string $activitePrincipale,
        public ?DateTimeImmutable $dateCreation,
        public ?Coordonnees $coordonnees,
        public array $enseignes,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $siret = Payload::text($data['siret'] ?? null) ?? throw new InvalidResponseException('Missing "siret" in an establishment.');
        $latitude = Payload::float($data['latitude'] ?? null);
        $longitude = Payload::float($data['longitude'] ?? null);

        return new self(
            siret: $siret,
            siren: substr($siret, 0, 9),
            estSiege: (bool) ($data['est_siege'] ?? false),
            etatAdministratif: Payload::text($data['etat_administratif'] ?? null),
            adresse: Payload::text($data['adresse'] ?? null),
            codePostal: Payload::text($data['code_postal'] ?? null),
            commune: Payload::text($data['libelle_commune'] ?? null),
            codeCommune: Payload::text($data['commune'] ?? null),
            activitePrincipale: Payload::text($data['activite_principale'] ?? null),
            dateCreation: Dates::parse(Payload::text($data['date_creation'] ?? null)),
            // A point needs both values; the API sometimes gives only one.
            coordonnees: $latitude === null || $longitude === null ? null : new Coordonnees(latitude: $latitude, longitude: $longitude),
            enseignes: Payload::strings($data['liste_enseignes'] ?? null),
        );
    }
}
