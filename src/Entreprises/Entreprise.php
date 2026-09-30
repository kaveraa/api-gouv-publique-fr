<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Entreprises;

use DateTimeImmutable;
use Kaveraa\ApiGouv\Exceptions\InvalidResponseException;
use Kaveraa\ApiGouv\Support\Dates;

final readonly class Entreprise
{
    /**
     * @param  list<Dirigeant>  $dirigeants
     * @param  list<Etablissement>  $etablissementsCorrespondants  Establishments that matched the search.
     */
    public function __construct(
        public string $siren,
        public string $nomComplet,
        public ?string $sigle,
        public ?string $activitePrincipale,
        public ?string $categorie,
        public ?string $natureJuridique,
        public ?string $etatAdministratif,
        public ?DateTimeImmutable $dateCreation,
        public ?string $trancheEffectif,
        public int $nombreEtablissements,
        public int $nombreEtablissementsOuverts,
        public ?Etablissement $siege,
        public array $dirigeants,
        public array $etablissementsCorrespondants,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $siren = (string) ($data['siren'] ?? throw new InvalidResponseException('Missing "siren" in a company.'));

        return new self(
            siren: $siren,
            nomComplet: (string) ($data['nom_complet'] ?? ''),
            sigle: self::text($data['sigle'] ?? null),
            activitePrincipale: self::text($data['activite_principale'] ?? null),
            categorie: self::text($data['categorie_entreprise'] ?? null),
            natureJuridique: self::text($data['nature_juridique'] ?? null),
            etatAdministratif: self::text($data['etat_administratif'] ?? null),
            dateCreation: Dates::parse(self::text($data['date_creation'] ?? null)),
            trancheEffectif: self::text($data['tranche_effectif_salarie'] ?? null),
            nombreEtablissements: (int) ($data['nombre_etablissements'] ?? 0),
            nombreEtablissementsOuverts: (int) ($data['nombre_etablissements_ouverts'] ?? 0),
            siege: isset($data['siege']['siret']) ? Etablissement::fromArray($data['siege']) : null,
            dirigeants: array_values(array_map(Dirigeant::fromArray(...), (array) ($data['dirigeants'] ?? []))),
            etablissementsCorrespondants: array_values(array_map(
                Etablissement::fromArray(...),
                (array) ($data['matching_etablissements'] ?? []),
            )),
        );
    }

    private static function text(mixed $value): ?string
    {
        return is_scalar($value) && $value !== '' ? (string) $value : null;
    }
}
