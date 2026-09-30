<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Entreprises;

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
            type: (string) ($data['type_dirigeant'] ?? 'inconnu'),
            qualite: self::text($data['qualite'] ?? null),
            nom: self::text($data['nom'] ?? null),
            prenoms: self::text($data['prenoms'] ?? null),
            denomination: self::text($data['denomination'] ?? null),
            siren: self::text($data['siren'] ?? null),
            anneeDeNaissance: self::text($data['annee_de_naissance'] ?? null),
        );
    }

    private static function text(mixed $value): ?string
    {
        return is_scalar($value) && $value !== '' ? (string) $value : null;
    }
}
