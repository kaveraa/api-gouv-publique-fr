<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Geo;

use Kaveraa\ApiGouv\Exceptions\NotFoundException;

interface GeoApi
{
    /** @throws NotFoundException When no commune has this INSEE code. */
    public function commune(string $codeInsee): Commune;

    /** @return list<Commune> */
    public function communesParCodePostal(string $codePostal): array;

    /** @return list<Commune> Best matches first. */
    public function rechercherCommunes(string $nom, int $limit = 10): array;

    public function communeParCoordonnees(float $latitude, float $longitude): ?Commune;

    /** @return list<Departement> */
    public function departements(): array;

    /** @throws NotFoundException When no departement has this code. */
    public function departement(string $code): Departement;

    /**
     * @return list<Commune>
     *
     * @throws NotFoundException When no departement has this code.
     */
    public function communesDuDepartement(string $code): array;

    /** @return list<Region> */
    public function regions(): array;

    /** @throws NotFoundException When no region has this code. */
    public function region(string $code): Region;

    /**
     * @return list<Departement>
     *
     * @throws NotFoundException When no region has this code.
     */
    public function departementsDeLaRegion(string $code): array;

    /** @throws NotFoundException When no EPCI has this code. */
    public function epci(string $code): Epci;

    /** @return list<Epci> */
    public function epcisDuDepartement(string $code): array;
}
