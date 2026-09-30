<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Entreprises;

use InvalidArgumentException;

final readonly class SearchQuery
{
    public function __construct(
        public string $q,
        public int $page = 1,
        public int $perPage = 10,
        public ?string $codePostal = null,
        public ?string $departement = null,
        public ?string $activitePrincipale = null,
        public ?string $etatAdministratif = null,
    ) {
        if (trim($q) === '') {
            throw new InvalidArgumentException('The search text must not be empty.');
        }
        if ($page < 1) {
            throw new InvalidArgumentException('The page must be 1 or more.');
        }
        // The API answers HTTP 400 outside this range, so fail early.
        if ($perPage < 1 || $perPage > 25) {
            throw new InvalidArgumentException('The page size must be between 1 and 25.');
        }
    }

    /** @return array<string, scalar> */
    public function toParams(): array
    {
        return array_filter([
            'q' => $this->q,
            'page' => $this->page,
            'per_page' => $this->perPage,
            'code_postal' => $this->codePostal,
            'departement' => $this->departement,
            'activite_principale' => $this->activitePrincipale,
            'etat_administratif' => $this->etatAdministratif,
        ], static fn ($value) => $value !== null);
    }
}
