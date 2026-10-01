<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Adresse;

use Kaveraa\ApiGouv\Coordonnees;
use Kaveraa\ApiGouv\Exceptions\InvalidResponseException;
use Kaveraa\ApiGouv\Support\Payload;

final readonly class Adresse
{
    /** @internal Build it with Factories in tests; the package builds it from the API payload. */
    public function __construct(
        public string $id,
        public string $label,
        public Coordonnees $coordonnees,
        public ?string $numero = null,
        public ?string $rue = null,
        public ?string $nom = null,
        public ?string $codePostal = null,
        public ?string $codeCommune = null,
        public ?string $commune = null,
        public ?string $contexte = null,
        public ?string $type = null,
        public ?float $score = null,
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
