<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Entreprises;

use DateTimeImmutable;
use Kaveraa\ApiGouv\Exceptions\InvalidResponseException;
use Kaveraa\ApiGouv\Support\Dates;
use Kaveraa\ApiGouv\Support\Payload;

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
        public ?int $nombreEtablissements,
        public ?int $nombreEtablissementsOuverts,
        public ?Etablissement $siege,
        public array $dirigeants,
        public array $etablissementsCorrespondants,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $siren = Payload::text($data['siren'] ?? null) ?? throw new InvalidResponseException('Missing "siren" in a company.');

        $siege = Payload::map($data['siege'] ?? null);

        return new self(
            siren: $siren,
            nomComplet: Payload::string($data['nom_complet'] ?? null),
            sigle: Payload::text($data['sigle'] ?? null),
            activitePrincipale: Payload::text($data['activite_principale'] ?? null),
            categorie: Payload::text($data['categorie_entreprise'] ?? null),
            natureJuridique: Payload::text($data['nature_juridique'] ?? null),
            etatAdministratif: Payload::text($data['etat_administratif'] ?? null),
            dateCreation: Dates::parse(Payload::text($data['date_creation'] ?? null)),
            trancheEffectif: Payload::text($data['tranche_effectif_salarie'] ?? null),
            nombreEtablissements: Payload::intOrNull($data['nombre_etablissements'] ?? null),
            nombreEtablissementsOuverts: Payload::intOrNull($data['nombre_etablissements_ouverts'] ?? null),
            siege: isset($siege['siret']) ? Etablissement::fromArray($siege) : null,
            dirigeants: array_map(Dirigeant::fromArray(...), Payload::maps($data['dirigeants'] ?? null)),
            etablissementsCorrespondants: array_map(Etablissement::fromArray(...), Payload::maps($data['matching_etablissements'] ?? null)),
        );
    }
}
