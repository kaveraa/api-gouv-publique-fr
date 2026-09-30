<?php

declare(strict_types=1);

use Kaveraa\ApiGouv\Support\GeoCodes;

it('accepts and normalizes valid codes', function (string $method, string $input, string $expected) {
    expect(GeoCodes::$method($input))->toBe($expected);
})->with([
    ['insee', '80021', '80021'],
    ['insee', '2A004', '2A004'],
    ['insee', ' 2a004 ', '2A004'],
    ['insee', '2B033', '2B033'],
    ['insee', '97101', '97101'],
    ['insee', "80\u{A0}021", '80021'],
    ['postal', '80000', '80000'],
    ['postal', '20000', '20000'],
    ['departement', '01', '01'],
    ['departement', '2a', '2A'],
    ['departement', '971', '971'],
    ['departement', '976', '976'],
    ['region', '32', '32'],
    ['region', '01', '01'],
    ['epci', '248000531', '248000531'],
    ['nom', '  Amiens ', 'Amiens'],
]);

it('rejects invalid codes', function (string $method, string $input) {
    GeoCodes::$method($input);
})->with([
    ['insee', ''],
    ['insee', '8002'],
    ['insee', '800210'],
    ['insee', 'ABCDE'],
    ['insee', '2C004'],
    ['postal', '8000'],
    ['postal', 'abcde'],
    ['departement', '1'],
    ['departement', '980'],
    ['departement', '2C'],
    ['region', '1'],
    ['region', '032'],
    ['epci', '12345678'],
    ['epci', 'A48000531'],
    ['nom', '   '],
])->throws(InvalidArgumentException::class);

it('checks the limit and the coordinates', function () {
    expect(GeoCodes::limit(1))->toBe(1)->and(GeoCodes::limit(50))->toBe(50);
    expect(fn () => GeoCodes::limit(0))->toThrow(InvalidArgumentException::class);
    expect(fn () => GeoCodes::limit(51))->toThrow(InvalidArgumentException::class);
    GeoCodes::coordinates(49.9, 2.3);
    GeoCodes::coordinates(0.0, 0.0);
    expect(fn () => GeoCodes::coordinates(NAN, 2.3))->toThrow(InvalidArgumentException::class);
    expect(fn () => GeoCodes::coordinates(49.9, INF))->toThrow(InvalidArgumentException::class);
    expect(fn () => GeoCodes::coordinates(91.0, 0.0))->toThrow(InvalidArgumentException::class);
    expect(fn () => GeoCodes::coordinates(0.0, -181.0))->toThrow(InvalidArgumentException::class);
});
