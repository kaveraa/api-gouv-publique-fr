<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Entreprises;

use Kaveraa\ApiGouv\Exceptions\NotFoundException;
use Kaveraa\ApiGouv\Http\Requester;
use Kaveraa\ApiGouv\Support\Identifiers;
use Kaveraa\ApiGouv\Support\Payload;

final class EntreprisesClient implements EntreprisesApi
{
    public function __construct(private readonly Requester $http) {}

    public function rechercher(SearchQuery|string $query): SearchResult
    {
        $query = is_string($query) ? new SearchQuery($query) : $query;

        return SearchResult::fromArray(Payload::map($this->http->getJson('search', $query->toParams())));
    }

    public function parSiren(string $siren): Entreprise
    {
        $siren = Identifiers::digitsOrFail($siren, 9, 'SIREN');

        // The API has no lookup endpoint and answers 200 with an empty list for unknown numbers.
        foreach ($this->rechercher(new SearchQuery($siren, perPage: 1))->results as $entreprise) {
            if ($entreprise->siren === $siren) {
                return $entreprise;
            }
        }

        throw new NotFoundException("No company found for SIREN {$siren}.", 404);
    }

    public function parSiret(string $siret): Etablissement
    {
        $siret = Identifiers::digitsOrFail($siret, 14, 'SIRET');

        foreach ($this->rechercher(new SearchQuery($siret, perPage: 1))->results as $entreprise) {
            foreach ([$entreprise->siege, ...$entreprise->etablissementsCorrespondants] as $etablissement) {
                if ($etablissement?->siret === $siret) {
                    return $etablissement;
                }
            }
        }

        throw new NotFoundException("No establishment found for SIRET {$siret}.", 404);
    }
}
