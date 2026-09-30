<?php

declare(strict_types=1);

use Kaveraa\ApiGouv\Support\Dates;

it('parses api dates and tolerates bad input', function () {
    expect(Dates::parse('2015-06-30')?->format('Y-m-d'))->toBe('2015-06-30')
        ->and(Dates::parse(null))->toBeNull()
        ->and(Dates::parse(''))->toBeNull()
        ->and(Dates::parse('not a date'))->toBeNull();
});
