<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Adresse;

interface AdresseApi
{
    /** @return list<Adresse> */
    public function rechercher(string $query, int $limit = 5): array;

    /** @return list<Adresse> */
    public function autocompleter(string $query, int $limit = 5): array;

    public function geocoderInverse(float $latitude, float $longitude): ?Adresse;
}