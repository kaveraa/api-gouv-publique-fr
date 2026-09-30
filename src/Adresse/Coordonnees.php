<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Adresse;

final readonly class Coordonnees
{
    public function __construct(
        public float $latitude,
        public float $longitude,
    ) {}
}
