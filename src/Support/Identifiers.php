<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Support;

final class Identifiers
{
    // La Poste SIRET values do not follow Luhn; their digit sum must be a multiple of 5.
    private const LA_POSTE_SIREN = '356000000';

    public static function normalize(string $value): string
    {
        // The u flag also removes U+00A0 and U+202F. Invalid UTF-8 gives null, so it becomes '' and is rejected.
        return (string) preg_replace('/\s+/u', '', $value);
    }

    public static function isSiren(mixed $value): bool
    {
        $digits = self::digits($value);

        return $digits !== null && strlen($digits) === 9 && Luhn::isValid($digits);
    }

    public static function isSiret(mixed $value): bool
    {
        $digits = self::digits($value);
        if ($digits === null || strlen($digits) !== 14) {
            return false;
        }

        if (str_starts_with($digits, self::LA_POSTE_SIREN)) {
            return Luhn::isValid($digits) || array_sum(array_map('intval', str_split($digits))) % 5 === 0;
        }

        return Luhn::isValid($digits);
    }

    private static function digits(mixed $value): ?string
    {
        if (is_int($value)) {
            $value = (string) $value;
        }
        if (! is_string($value)) {
            return null;
        }

        $digits = self::normalize($value);

        return ctype_digit($digits) ? $digits : null;
    }
}
