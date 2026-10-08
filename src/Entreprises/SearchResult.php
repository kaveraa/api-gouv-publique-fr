<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Entreprises;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Kaveraa\ApiGouv\Support\Payload;

/**
 * One page of a company search. Iterating and counting work on the page; `total` covers every page.
 *
 * @implements IteratorAggregate<int, Entreprise>
 */
final readonly class SearchResult implements Countable, IteratorAggregate
{
    /**
     * @internal Build it with Factories in tests; the package builds it from the API payload.
     *
     * @param  list<Entreprise>  $results
     */
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

    /** @return ArrayIterator<int, Entreprise> */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->results);
    }

    public function count(): int
    {
        return count($this->results);
    }
}
