<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Adresse;

use Kaveraa\ApiGouv\Exceptions\InvalidResponseException;
use Kaveraa\ApiGouv\Support\Payload;

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
        $point = Payload::map($feature['geometry'] ?? null)['coordinates'] ?? null;
        $point = is_array($point) ? $point : [];
        $longitude = Payload::float($point[0] ?? null);
        $latitude = Payload::float($point[1] ?? null);
        if ($longitude === null || $latitude === null) {
            throw new InvalidResponseException('An address has no coordinates.');
        }

        $props = Payload::map($feature['properties'] ?? null);

        return new self(
            id: Payload::string($props['id'] ?? null),
            label: Payload::string($props['label'] ?? null),
            numero: Payload::text($props['housenumber'] ?? null),
            rue: Payload::text($props['street'] ?? null),
            nom: Payload::text($props['name'] ?? null),
            codePostal: Payload::text($props['postcode'] ?? null),
            codeCommune: Payload::text($props['citycode'] ?? null),
            commune: Payload::text($props['city'] ?? null),
            contexte: Payload::text($props['context'] ?? null),
            type: Payload::text($props['type'] ?? null),
            score: Payload::float($props['score'] ?? null),
            // GeoJSON order is [longitude, latitude].
            coordonnees: new Coordonnees(latitude: $latitude, longitude: $longitude),
        );
    }
}
