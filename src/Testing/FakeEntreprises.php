<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Testing;

use Kaveraa\ApiGouv\Entreprises\Entreprise;
use Kaveraa\ApiGouv\Entreprises\EntreprisesApi;
use Kaveraa\ApiGouv\Entreprises\Etablissement;
use Kaveraa\ApiGouv\Entreprises\SearchQuery;
use Kaveraa\ApiGouv\Entreprises\SearchResult;
use Kaveraa\ApiGouv\Exceptions\NotFoundException;
use Kaveraa\ApiGouv\Support\Identifiers;

final class FakeEntreprises implements EntreprisesApi
{
    /** @var list<array{0: string, 1: string}> */
    public array $calls = [];

    /** @var list<Entreprise> */
    private array $entreprises = [];

    public function with(Entreprise ...$entreprises): self
    {
        array_push($this->entreprises, ...$entreprises);

        return $this;
    }

    public function rechercher(SearchQuery|string $query): SearchResult
    {
        $text = is_string($query) ? $query : $query->q;
        $this->calls[] = ['rechercher', $text];

        $found = array_values(array_filter(
            $this->entreprises,
            static fn (Entreprise $e) => stripos($e->nomComplet, $text) !== false,
        ));

        return new SearchResult($found, count($found), 1, is_string($query) ? 10 : $query->perPage, $found === [] ? 0 : 1);
    }

    public function parSiren(string $siren): Entreprise
    {
        $siren = Identifiers::normalize($siren);
        $this->calls[] = ['parSiren', $siren];

        foreach ($this->entreprises as $entreprise) {
            if ($entreprise->siren === $siren) {
                return $entreprise;
            }
        }

        throw new NotFoundException("No company found for SIREN {$siren}.", 404);
    }

    public function parSiret(string $siret): Etablissement
    {
        $siret = Identifiers::normalize($siret);
        $this->calls[] = ['parSiret', $siret];

        foreach ($this->entreprises as $entreprise) {
            foreach ([$entreprise->siege, ...$entreprise->etablissementsCorrespondants] as $etablissement) {
                if ($etablissement?->siret === $siret) {
                    return $etablissement;
                }
            }
        }

        throw new NotFoundException("No establishment found for SIRET {$siret}.", 404);
    }
}
