<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Testing;

use Kaveraa\ApiGouv\Exceptions\NotFoundException;
use Kaveraa\ApiGouv\Geo\Commune;
use Kaveraa\ApiGouv\Geo\Departement;
use Kaveraa\ApiGouv\Geo\Epci;
use Kaveraa\ApiGouv\Geo\GeoApi;
use Kaveraa\ApiGouv\Geo\Region;
use Kaveraa\ApiGouv\Support\GeoCodes;

/** In-memory Geo API for tests. Matching is by code, postal code, name substring or nearest centre. */
final class FakeGeo implements GeoApi
{
    /** @var list<array{0: string, 1: string}> */
    public array $calls = [];

    /** @var list<Commune> */
    private array $communes = [];

    /** @var list<Departement> */
    private array $departements = [];

    /** @var list<Region> */
    private array $regions = [];

    /** @var list<Epci> */
    private array $epcis = [];

    public function with(Commune|Departement|Region|Epci ...$items): self
    {
        foreach ($items as $item) {
            if ($item instanceof Commune) {
                $this->communes[] = $item;
            } elseif ($item instanceof Departement) {
                $this->departements[] = $item;
            } elseif ($item instanceof Region) {
                $this->regions[] = $item;
            } else {
                $this->epcis[] = $item;
            }
        }

        return $this;
    }

    public function commune(string $codeInsee): Commune
    {
        $code = GeoCodes::insee($codeInsee);
        $this->calls[] = ['commune', $code];

        foreach ($this->communes as $commune) {
            if ($commune->code === $code) {
                return $commune;
            }
        }

        throw new NotFoundException("No commune found for code {$code}.", 404);
    }

    public function communesParCodePostal(string $codePostal): array
    {
        $code = GeoCodes::postal($codePostal);
        $this->calls[] = ['communesParCodePostal', $code];

        return array_values(array_filter($this->communes, static fn (Commune $c) => in_array($code, $c->codesPostaux, true)));
    }

    public function rechercherCommunes(string $nom, int $limit = 10): array
    {
        $nom = GeoCodes::nom($nom);
        $limit = GeoCodes::limit($limit);
        $this->calls[] = ['rechercherCommunes', $nom];

        $found = array_filter($this->communes, static fn (Commune $c) => stripos($c->nom, $nom) !== false);

        return array_slice(array_values($found), 0, $limit);
    }

    public function communeParCoordonnees(float $latitude, float $longitude): ?Commune
    {
        GeoCodes::coordinates($latitude, $longitude);
        $this->calls[] = ['communeParCoordonnees', $latitude.','.$longitude];

        $nearest = null;
        $best = INF;
        foreach ($this->communes as $commune) {
            if ($commune->centre === null) {
                continue;
            }
            $distance = ($commune->centre->latitude - $latitude) ** 2 + ($commune->centre->longitude - $longitude) ** 2;
            if ($distance < $best) {
                $best = $distance;
                $nearest = $commune;
            }
        }

        return $nearest;
    }

    public function departements(): array
    {
        $this->calls[] = ['departements', ''];

        return $this->departements;
    }

    public function departement(string $code): Departement
    {
        $code = GeoCodes::departement($code);
        $this->calls[] = ['departement', $code];

        return $this->findDepartement($code);
    }

    public function communesDuDepartement(string $code): array
    {
        $code = GeoCodes::departement($code);
        $this->calls[] = ['communesDuDepartement', $code];
        $this->findDepartement($code);

        return array_values(array_filter($this->communes, static fn (Commune $c) => $c->codeDepartement === $code));
    }

    public function regions(): array
    {
        $this->calls[] = ['regions', ''];

        return $this->regions;
    }

    public function region(string $code): Region
    {
        $code = GeoCodes::region($code);
        $this->calls[] = ['region', $code];

        return $this->findRegion($code);
    }

    public function departementsDeLaRegion(string $code): array
    {
        $code = GeoCodes::region($code);
        $this->calls[] = ['departementsDeLaRegion', $code];
        $this->findRegion($code);

        return array_values(array_filter($this->departements, static fn (Departement $d) => $d->codeRegion === $code));
    }

    public function epci(string $code): Epci
    {
        $code = GeoCodes::epci($code);
        $this->calls[] = ['epci', $code];

        foreach ($this->epcis as $epci) {
            if ($epci->code === $code) {
                return $epci;
            }
        }

        throw new NotFoundException("No EPCI found for code {$code}.", 404);
    }

    public function epcisDuDepartement(string $code): array
    {
        $code = GeoCodes::departement($code);
        $this->calls[] = ['epcisDuDepartement', $code];
        $this->findDepartement($code);

        return array_values(array_filter($this->epcis, static fn (Epci $e) => in_array($code, $e->codesDepartements, true)));
    }

    private function findDepartement(string $code): Departement
    {
        foreach ($this->departements as $departement) {
            if ($departement->code === $code) {
                return $departement;
            }
        }

        throw new NotFoundException("No departement found for code {$code}.", 404);
    }

    private function findRegion(string $code): Region
    {
        foreach ($this->regions as $region) {
            if ($region->code === $code) {
                return $region;
            }
        }

        throw new NotFoundException("No region found for code {$code}.", 404);
    }
}
