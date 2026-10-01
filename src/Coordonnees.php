<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv;

/** A WGS84 point. Shared by addresses, communes and establishments. */
final readonly class Coordonnees
{
    public function __construct(
        public float $latitude,
        public float $longitude,
    ) {}
}
