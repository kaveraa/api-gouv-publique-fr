<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Geo;

use Kaveraa\ApiGouv\Exceptions\InvalidResponseException;
use Kaveraa\ApiGouv\Support\Payload;

final readonly class Epci
{
    /**
     * @internal Build it with Factories in tests; the package builds it from the API payload.
     *
     * @param  list<string>  $codesDepartements
     * @param  list<string>  $codesRegions
     */
    public function __construct(
        public string $code,
        public string $nom,
        public ?int $population = null,
        public array $codesDepartements = [],
        public array $codesRegions = [],
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            code: Payload::text($data['code'] ?? null) ?? throw new InvalidResponseException('Missing "code" in an EPCI.'),
            nom: Payload::text($data['nom'] ?? null) ?? throw new InvalidResponseException('Missing "nom" in an EPCI.'),
            population: Payload::intOrNull($data['population'] ?? null),
            codesDepartements: Payload::strings($data['codesDepartements'] ?? null),
            codesRegions: Payload::strings($data['codesRegions'] ?? null),
        );
    }
}
