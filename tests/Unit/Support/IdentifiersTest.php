<?php

declare(strict_types=1);

use Kaveraa\ApiGouv\Support\Identifiers;
use Kaveraa\ApiGouv\Support\Luhn;

it('checks the Luhn sum', function () {
    expect(Luhn::isValid('812487973'))->toBeTrue()
        ->and(Luhn::isValid('812487974'))->toBeFalse()
        ->and(Luhn::isValid(''))->toBeFalse()
        ->and(Luhn::isValid('12a4'))->toBeFalse();
});

it('accepts valid SIREN values, with or without spaces', function (mixed $value) {
    expect(Identifiers::isSiren($value))->toBeTrue();
})->with(['812487973', '812 487 973', 812487973, "812\u{A0}487\u{A0}973"]);

it('rejects a negative or zero integer as a SIREN', function (int $value) {
    expect(Identifiers::isSiren($value))->toBeFalse();
})->with([-812487973, 0]);

it('rejects invalid SIREN values without throwing', function (mixed $value) {
    expect(Identifiers::isSiren($value))->toBeFalse();
})->with(['', '812487974', '81248797', '8124879731', 'abcdefghi', null, [[]], 1.5, true]);

it('accepts valid SIRET values, with or without spaces', function (mixed $value) {
    expect(Identifiers::isSiret($value))->toBeTrue();
})->with(['81248797300040', '812 487 973 00040', 81248797300040, "812\u{202F}487\u{202F}973\u{202F}00040"]);

it('rejects invalid SIRET values without throwing', function (mixed $value) {
    expect(Identifiers::isSiret($value))->toBeFalse();
})->with(['', '81248797300041', '812487973', 'x', null, [[]]]);

it('rejects invalid UTF-8 without throwing', function () {
    expect(Identifiers::isSiren("812\xC3\x28487973"))->toBeFalse()
        ->and(Identifiers::isSiret("812\xC3\x28487973 00040"))->toBeFalse();
});

it('applies the La Poste rule to SIRET values that start with 356000000', function () {
    // These fail the Luhn sum but have a digit sum that is a multiple of 5.
    expect(Identifiers::isSiret('35600000000001'))->toBeTrue()
        ->and(Identifiers::isSiret('35600000000010'))->toBeTrue()
        // These fail both rules.
        ->and(Identifiers::isSiret('35600000000000'))->toBeFalse()
        ->and(Identifiers::isSiret('35600000000002'))->toBeFalse()
        // This one passes the Luhn sum but not the digit sum rule.
        ->and(Identifiers::isSiret('35600000000030'))->toBeTrue();
});

it('removes every kind of whitespace when normalizing', function () {
    expect(Identifiers::normalize(" 812\t487 973\n"))->toBe('812487973');
});
