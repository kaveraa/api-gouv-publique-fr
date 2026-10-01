<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Geo;

use Kaveraa\ApiGouv\Coordonnees;
use Kaveraa\ApiGouv\Exceptions\InvalidResponseException;
use Kaveraa\ApiGouv\Support\Payload;

final readonly class Commune
{
    /**
     * @internal Build it with Factories in tests; the package builds it from the API payload.
     *
     * @param  list<string>  $codesPostaux
     */
    public function __construct(
        public string $code,
        public string $nom,
        public array $codesPostaux = [],
        public ?int $population = null,
        public ?string $codeDepartement = null,
        public ?string $codeRegion = null,
        public ?string $siren = null,
        public ?string $codeEpci = null,
        public ?Coordonnees $centre = null,
        public ?float $score = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            code: Payload::text($data['code'] ?? null) ?? throw new InvalidResponseException('Missing "code" in a commune.'),
            nom: Payload::text($data['nom'] ?? null) ?? throw new InvalidResponseException('Missing "nom" in a commune.'),
            codesPostaux: Payload::strings($data['codesPostaux'] ?? null),
            population: Payload::intOrNull($data['population'] ?? null),
            codeDepartement: Payload::text($data['codeDepartement'] ?? null),
            codeRegion: Payload::text($data['codeRegion'] ?? null),
            siren: Payload::text($data['siren'] ?? null),
            codeEpci: Payload::text($data['codeEpci'] ?? null),
            centre: self::centre($data['centre'] ?? null),
            // Only a search by name gives a score.
            score: Payload::float($data['_score'] ?? null),
        );
    }

    private static function centre(mixed $geoJson): ?Coordonnees
    {
        $point = Payload::map($geoJson)['coordinates'] ?? null;
        $point = is_array($point) ? $point : [];
        $longitude = Payload::float($point[0] ?? null);
        $latitude = Payload::float($point[1] ?? null);

        // GeoJSON order is [longitude, latitude]. A missing centre is not an error.
        return $longitude === null || $latitude === null ? null : new Coordonnees(latitude: $latitude, longitude: $longitude);
    }
}
