<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Geo;

use Kaveraa\ApiGouv\Exceptions\InvalidResponseException;
use Kaveraa\ApiGouv\Support\Payload;

final readonly class Departement
{
    /** @internal Build it with Factories in tests; the package builds it from the API payload. */
    public function __construct(
        public string $code,
        public string $nom,
        public ?string $codeRegion = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            code: Payload::text($data['code'] ?? null) ?? throw new InvalidResponseException('Missing "code" in a departement.'),
            nom: Payload::text($data['nom'] ?? null) ?? throw new InvalidResponseException('Missing "nom" in a departement.'),
            codeRegion: Payload::text($data['codeRegion'] ?? null),
        );
    }
}
