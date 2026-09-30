<?php

declare(strict_types=1);

use Kaveraa\ApiGouv\Support\Payload;

it('reads text, dropping empty and non scalar values', function () {
    expect(Payload::text('a'))->toBe('a')
        ->and(Payload::text(12))->toBe('12')
        ->and(Payload::text(''))->toBeNull()
        ->and(Payload::text(['a']))->toBeNull()
        ->and(Payload::text(null))->toBeNull();
});

it('reads strings, ints and floats with a fallback', function () {
    expect(Payload::string(5))->toBe('5')
        ->and(Payload::string(null, 'x'))->toBe('x')
        ->and(Payload::int('7'))->toBe(7)
        ->and(Payload::int('nope', 3))->toBe(3)
        ->and(Payload::float('1.5'))->toBe(1.5)
        ->and(Payload::float('nope'))->toBeNull();
});

it('reads maps, lists of maps and lists of strings', function () {
    expect(Payload::map(['a' => 1]))->toBe(['a' => 1])
        ->and(Payload::map('x'))->toBe([])
        ->and(Payload::maps([['a' => 1], 'skip', ['b' => 2]]))->toBe([['a' => 1], ['b' => 2]])
        ->and(Payload::maps(null))->toBe([])
        ->and(Payload::strings(['a', 2, ['x'], null]))->toBe(['a', '2'])
        ->and(Payload::strings('a'))->toBe([]);
});
