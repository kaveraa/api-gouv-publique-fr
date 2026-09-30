<?php

declare(strict_types=1);

use Kaveraa\ApiGouv\Entreprises\SearchQuery;

it('builds api parameters and skips empty filters', function () {
    $query = new SearchQuery('octo', page: 2, perPage: 5, codePostal: '33300', etatAdministratif: 'A');

    expect($query->toParams())->toBe([
        'q' => 'octo',
        'page' => 2,
        'per_page' => 5,
        'code_postal' => '33300',
        'etat_administratif' => 'A',
    ]);
});

it('rejects a page size outside 1..25 and a page below 1', function (int $page, int $perPage) {
    new SearchQuery('octo', page: $page, perPage: $perPage);
})->with([[1, 0], [1, 26], [0, 10]])->throws(InvalidArgumentException::class);

it('rejects an empty query', function () {
    new SearchQuery('   ');
})->throws(InvalidArgumentException::class);
