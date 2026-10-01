<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Entreprises;

use Kaveraa\ApiGouv\Support\Payload;

final readonly class Dirigeant
{
    /** @internal Build it with Factories in tests; the package builds it from the API payload. */
    public function __construct(
        public string $type = 'inconnu',
        public ?string $qualite = null,
        public ?string $nom = null,
        public ?string $prenoms = null,
        public ?string $denomination = null,
        public ?string $siren = null,
        public ?string $anneeDeNaissance = null,
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
