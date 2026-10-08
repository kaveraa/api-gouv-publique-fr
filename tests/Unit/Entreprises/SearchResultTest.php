<?php

declare(strict_types=1);

use Kaveraa\ApiGouv\Entreprises\Entreprise;
use Kaveraa\ApiGouv\Entreprises\SearchResult;
use Kaveraa\ApiGouv\Testing\Factories;

it('iterates over the companies of the page in order', function () {
    $result = SearchResult::fromArray(json_decode(loadFixture('entreprises_search.json'), true));

    $sirens = [];
    foreach ($result as $company) {
        expect($company)->toBeInstanceOf(Entreprise::class);
        $sirens[] = $company->siren;
    }

    expect($result)->toBeInstanceOf(IteratorAggregate::class)
        ->and($sirens)->toBe(['812487973', '922076625']);
});

it('counts the companies of the page, not the total of all pages', function () {
    $result = SearchResult::fromArray(json_decode(loadFixture('entreprises_search.json'), true));

    expect($result)->toBeInstanceOf(Countable::class)
        ->and(count($result))->toBe(2)
        ->and($result->total)->toBe(281);
});

it('is empty when the search has no match', function () {
    $empty = SearchResult::fromArray(json_decode(loadFixture('entreprises_empty.json'), true));
    $seen = 0;
    foreach ($empty as $company) {
        $seen++;
    }

    expect(count($empty))->toBe(0)
        ->and($seen)->toBe(0)
        ->and(count(Factories::searchResult(['results' => [], 'total' => 0, 'totalPages' => 0])))->toBe(0);
});
