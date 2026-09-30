<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Adresse;

use InvalidArgumentException;
use Kaveraa\ApiGouv\Http\Requester;

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
        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            throw new InvalidArgumentException('The coordinates are out of range.');
        }

        $features = $this->http->getJson('reverse', ['lat' => $latitude, 'lon' => $longitude, 'limit' => 1])['features'] ?? [];
        $first = is_array($features) ? ($features[0] ?? null) : null;

        return is_array($first) ? Adresse::fromFeature($first) : null;
    }

    /** @return list<Adresse> */
    private function search(string $query, int $limit, bool $autocomplete): array
    {
        if (trim($query) === '') {
            throw new InvalidArgumentException('The search text must not be empty.');
        }
        if ($limit < 1 || $limit > 50) {
            throw new InvalidArgumentException('The limit must be between 1 and 50.');
        }

        $data = $this->http->getJson('search', ['q' => $query, 'limit' => $limit, 'autocomplete' => (int) $autocomplete]);

        return array_values(array_map(Adresse::fromFeature(...), (array) ($data['features'] ?? [])));
    }
}