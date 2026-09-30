<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Entreprises;

interface EntreprisesApi
{
    public function rechercher(SearchQuery|string $query): SearchResult;

    /** @throws \Kaveraa\ApiGouv\Exceptions\NotFoundException When no company has this SIREN. */
    public function parSiren(string $siren): Entreprise;

    /** @throws \Kaveraa\ApiGouv\Exceptions\NotFoundException When no establishment has this SIRET. */
    public function parSiret(string $siret): Etablissement;
}
