<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Entreprises;

use Kaveraa\ApiGouv\Support\Payload;

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
            results: array_map(Entreprise::fromArray(...), Payload::maps($data['results'] ?? null)),
            total: Payload::int($data['total_results'] ?? null),
            page: Payload::int($data['page'] ?? null, 1),
            perPage: Payload::int($data['per_page'] ?? null, 10),
            totalPages: Payload::int($data['total_pages'] ?? null),
        );
    }
}
