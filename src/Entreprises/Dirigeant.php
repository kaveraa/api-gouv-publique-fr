<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Entreprises;

use Kaveraa\ApiGouv\Support\Payload;

final readonly class Dirigeant
{
    public function __construct(
        public string $type,
        public ?string $qualite,
        public ?string $nom,
        public ?string $prenoms,
        public ?string $denomination,
        public ?string $siren,
        public ?string $anneeDeNaissance,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            type: Payload::string($data['type_dirigeant'] ?? null, 'inconnu'),
            qualite: Payload::text($data['qualite'] ?? null),
            nom: Payload::text($data['nom'] ?? null),
            prenoms: Payload::text($data['prenoms'] ?? null),
            denomination: Payload::text($data['denomination'] ?? null),
            siren: Payload::text($data['siren'] ?? null),
            anneeDeNaissance: Payload::text($data['annee_de_naissance'] ?? null),
        );
    }
}
