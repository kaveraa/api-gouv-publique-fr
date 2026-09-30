<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Geo;

use Kaveraa\ApiGouv\Http\Requester;
use Kaveraa\ApiGouv\Support\GeoCodes;
use Kaveraa\ApiGouv\Support\Payload;

final class GeoClient implements GeoApi
{
    // Explicit fields keep the Commune object stable and include the centre. The contour is never requested.
    private const COMMUNE_FIELDS = 'code,nom,codesPostaux,population,codeDepartement,codeRegion,siren,codeEpci,centre';

    public function __construct(private readonly Requester $http) {}

    public function commune(string $codeInsee): Commune
    {
        $code = GeoCodes::insee($codeInsee);

        return Commune::fromArray(Payload::map($this->http->getJson('communes/'.$code, ['fields' => self::COMMUNE_FIELDS])));
    }

    public function communesParCodePostal(string $codePostal): array
    {
        $code = GeoCodes::postal($codePostal);

        return $this->communes('communes', ['codePostal' => $code]);
    }

    public function rechercherCommunes(string $nom, int $limit = 10): array
    {
        $nom = GeoCodes::nom($nom);
        $limit = GeoCodes::limit($limit);

        return $this->communes('communes', ['nom' => $nom, 'boost' => 'population', 'limit' => $limit]);
    }

    public function communeParCoordonnees(float $latitude, float $longitude): ?Commune
    {
        GeoCodes::coordinates($latitude, $longitude);

        return $this->communes('communes', ['lat' => $latitude, 'lon' => $longitude])[0] ?? null;
    }

    public function departements(): array
    {
        return array_map(Departement::fromArray(...), Payload::maps($this->http->getJson('departements')));
    }

    public function departement(string $code): Departement
    {
        $code = GeoCodes::departement($code);

        return Departement::fromArray(Payload::map($this->http->getJson('departements/'.$code)));
    }

    public function communesDuDepartement(string $code): array
    {
        $code = GeoCodes::departement($code);

        return $this->communes('departements/'.$code.'/communes', []);
    }

    public function regions(): array
    {
        return array_map(Region::fromArray(...), Payload::maps($this->http->getJson('regions')));
    }

    public function region(string $code): Region
    {
        $code = GeoCodes::region($code);

        return Region::fromArray(Payload::map($this->http->getJson('regions/'.$code)));
    }

    public function departementsDeLaRegion(string $code): array
    {
        $code = GeoCodes::region($code);

        return array_map(Departement::fromArray(...), Payload::maps($this->http->getJson('regions/'.$code.'/departements')));
    }

    public function epci(string $code): Epci
    {
        $code = GeoCodes::epci($code);

        return Epci::fromArray(Payload::map($this->http->getJson('epcis/'.$code)));
    }

    public function epcisDuDepartement(string $code): array
    {
        $code = GeoCodes::departement($code);

        return array_map(Epci::fromArray(...), Payload::maps($this->http->getJson('epcis', ['codeDepartement' => $code])));
    }

    /**
     * @param  array<string, scalar>  $query
     * @return list<Commune>
     */
    private function communes(string $path, array $query): array
    {
        $query['fields'] = self::COMMUNE_FIELDS;

        return array_map(Commune::fromArray(...), Payload::maps($this->http->getJson($path, $query)));
    }
}
