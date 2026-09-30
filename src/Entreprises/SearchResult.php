<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Entreprises;

final readonly class SearchResult
{
    /** @param list<Entreprise> $results */
    public function __construct(
        public array $results,
        public int $total,
        public int $page,
        public int $perPage,
        public int $totalPages,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            results: array_values(array_map(Entreprise::fromArray(...), (array) ($data['results'] ?? []))),
            total: (int) ($data['total_results'] ?? 0),
            page: (int) ($data['page'] ?? 1),
            perPage: (int) ($data['per_page'] ?? 10),
            totalPages: (int) ($data['total_pages'] ?? 0),
        );
    }
}
