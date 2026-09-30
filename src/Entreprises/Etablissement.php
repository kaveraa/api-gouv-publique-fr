<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Entreprises;

use DateTimeImmutable;
use Kaveraa\ApiGouv\Exceptions\InvalidResponseException;
use Kaveraa\ApiGouv\Support\Dates;

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
        public ?float $latitude,
        public ?float $longitude,
        public array $enseignes,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $siret = (string) ($data['siret'] ?? throw new InvalidResponseException('Missing "siret" in an establishment.'));

        return new self(
            siret: $siret,
            siren: substr($siret, 0, 9),
            estSiege: (bool) ($data['est_siege'] ?? false),
            etatAdministratif: self::text($data['etat_administratif'] ?? null),
            adresse: self::text($data['adresse'] ?? null),
            codePostal: self::text($data['code_postal'] ?? null),
            commune: self::text($data['libelle_commune'] ?? null),
            codeCommune: self::text($data['commune'] ?? null),
            activitePrincipale: self::text($data['activite_principale'] ?? null),
            dateCreation: Dates::parse(self::text($data['date_creation'] ?? null)),
            latitude: isset($data['latitude']) ? (float) $data['latitude'] : null,
            longitude: isset($data['longitude']) ? (float) $data['longitude'] : null,
            enseignes: array_values(array_map('strval', (array) ($data['liste_enseignes'] ?? []))),
        );
    }

    private static function text(mixed $value): ?string
    {
        return is_scalar($value) && $value !== '' ? (string) $value : null;
    }
}
