<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Adresse;

use Kaveraa\ApiGouv\Exceptions\InvalidResponseException;

final readonly class Adresse
{
    public function __construct(
        public string $id,
        public string $label,
        public ?string $numero,
        public ?string $rue,
        public ?string $nom,
        public ?string $codePostal,
        public ?string $codeCommune,
        public ?string $commune,
        public ?string $contexte,
        public ?string $type,
        public ?float $score,
        public Coordonnees $coordonnees,
    ) {}

    /** @param array<string, mixed> $feature A GeoJSON feature from the API. */
    public static function fromFeature(array $feature): self
    {
        $point = $feature['geometry']['coordinates'] ?? null;
        if (! is_array($point) || ! isset($point[0], $point[1])) {
            throw new InvalidResponseException('An address has no coordinates.');
        }

        $props = (array) ($feature['properties'] ?? []);

        return new self(
            id: (string) ($props['id'] ?? ''),
            label: (string) ($props['label'] ?? ''),
            numero: self::text($props['housenumber'] ?? null),
            rue: self::text($props['street'] ?? null),
            nom: self::text($props['name'] ?? null),
            codePostal: self::text($props['postcode'] ?? null),
            codeCommune: self::text($props['citycode'] ?? null),
            commune: self::text($props['city'] ?? null),
            contexte: self::text($props['context'] ?? null),
            type: self::text($props['type'] ?? null),
            score: isset($props['score']) ? (float) $props['score'] : null,
            // GeoJSON order is [longitude, latitude].
            coordonnees: new Coordonnees(latitude: (float) $point[1], longitude: (float) $point[0]),
        );
    }

    private static function text(mixed $value): ?string
    {
        return is_scalar($value) && $value !== '' ? (string) $value : null;
    }
}