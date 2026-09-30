<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Testing;

use Kaveraa\ApiGouv\Adresse\Adresse;
use Kaveraa\ApiGouv\Adresse\AdresseApi;

final class FakeAdresse implements AdresseApi
{
    /** @var list<array{0: string, 1: string}> */
    public array $calls = [];

    /** @var list<Adresse> */
    private array $adresses = [];

    public function with(Adresse ...$adresses): self
    {
        array_push($this->adresses, ...$adresses);

        return $this;
    }

    public function rechercher(string $query, int $limit = 5): array
    {
        $this->calls[] = ['rechercher', $query];

        return $this->matching($query, $limit);
    }

    public function autocompleter(string $query, int $limit = 5): array
    {
        $this->calls[] = ['autocompleter', $query];

        return $this->matching($query, $limit);
    }

    public function geocoderInverse(float $latitude, float $longitude): ?Adresse
    {
        $this->calls[] = ['geocoderInverse', $latitude.','.$longitude];

        $nearest = null;
        $best = INF;
        foreach ($this->adresses as $adresse) {
            $distance = ($adresse->coordonnees->latitude - $latitude) ** 2 + ($adresse->coordonnees->longitude - $longitude) ** 2;
            if ($distance < $best) {
                $best = $distance;
                $nearest = $adresse;
            }
        }

        return $nearest;
    }

    /** @return list<Adresse> */
    private function matching(string $query, int $limit): array
    {
        $found = array_filter($this->adresses, static fn (Adresse $a) => stripos($a->label, $query) !== false);

        return array_slice(array_values($found), 0, $limit);
    }
}
