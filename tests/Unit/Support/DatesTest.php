<?php

declare(strict_types=1);

use Kaveraa\ApiGouv\Support\Dates;

it('parses api dates and tolerates bad input', function () {
    expect(Dates::parse('2015-06-30')?->format('Y-m-d'))->toBe('2015-06-30')
        ->and(Dates::parse('2015-06-30T10:20:30')?->format('Y-m-d H:i:s'))->toBe('2015-06-30 10:20:30')
        ->and(Dates::parse(null))->toBeNull()
        ->and(Dates::parse(''))->toBeNull()
        ->and(Dates::parse('not a date'))->toBeNull();
});

it('rejects relative dates', function (string $value) {
    expect(Dates::parse($value))->toBeNull();
})->with(['now', 'tomorrow', '+1 day', '15 June 2015']);
