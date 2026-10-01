<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Adresse;

use Kaveraa\ApiGouv\Http\Requester;
use Kaveraa\ApiGouv\Support\GeoCodes;
use Kaveraa\ApiGouv\Support\Payload;

final class AdresseClient implements AdresseApi
{
    public function __construct(private readonly Requester $http) {}

    public function rechercher(string $query, int $limit = 5): array
    {
        return $this->search($query, $limit, autocomplete: false);
    }

    public function autocompleter(string $query, int $limit = 5): array
    {
        return $this->search($query, $limit, autocomplete: true);
    }

    public function geocoderInverse(float $latitude, float $longitude): ?Adresse
    {
        GeoCodes::coordinates($latitude, $longitude);

        $data = $this->http->getJson('reverse', ['lat' => $latitude, 'lon' => $longitude, 'limit' => 1]);
        $first = Payload::maps($data['features'] ?? null)[0] ?? null;

        return $first === null ? null : Adresse::fromFeature($first);
    }

    /** @return list<Adresse> */
    private function search(string $query, int $limit, bool $autocomplete): array
    {
        GeoCodes::text($query);
        GeoCodes::limit($limit);

        $data = $this->http->getJson('search', ['q' => $query, 'limit' => $limit, 'autocomplete' => (int) $autocomplete]);

        return array_map(Adresse::fromFeature(...), Payload::maps($data['features'] ?? null));
    }
}
