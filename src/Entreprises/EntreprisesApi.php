<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Entreprises;

use Kaveraa\ApiGouv\Exceptions\NotFoundException;

interface EntreprisesApi
{
    public function rechercher(SearchQuery|string $query): SearchResult;

    /** @throws NotFoundException When no company has this SIREN. */
    public function parSiren(string $siren): Entreprise;

    /** @throws NotFoundException When no establishment has this SIRET. */
    public function parSiret(string $siret): Etablissement;
}
