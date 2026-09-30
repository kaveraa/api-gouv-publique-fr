<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Support;

use InvalidArgumentException;

/** Checks the codes accepted by the Geo API before any network call. */
final class GeoCodes
{
    public static function insee(string $value): string
    {
        return self::match($value, '/^(\d{5}|2[AB]\d{3})$/', 'A commune code must have 5 characters, like 80021 or 2A004.');
    }

    public static function postal(string $value): string
    {
        return self::match($value, '/^\d{5}$/', 'A postal code must have 5 digits.');
    }

    public static function departement(string $value): string
    {
        return self::match($value, '/^(\d{2}|2[AB]|97\d)$/', 'A departement code must be like 01, 2A or 971.');
    }

    public static function region(string $value): string
    {
        return self::match($value, '/^\d{2}$/', 'A region code must have 2 digits, like 32 or 01.');
    }

    public static function epci(string $value): string
    {
        return self::match($value, '/^\d{9}$/', 'An EPCI code must have 9 digits.');
    }

    public static function nom(string $value): string
    {
        $nom = trim($value);
        if ($nom === '') {
            throw new InvalidArgumentException('The name must not be empty.');
        }

        return $nom;
    }

    public static function limit(int $value): int
    {
        if ($value < 1 || $value > 50) {
            throw new InvalidArgumentException('The limit must be between 1 and 50.');
        }

        return $value;
    }

    public static function coordinates(float $latitude, float $longitude): void
    {
        if (! is_finite($latitude) || ! is_finite($longitude) || abs($latitude) > 90 || abs($longitude) > 180) {
            throw new InvalidArgumentException('The coordinates are out of range.');
        }
    }

    private static function match(string $value, string $pattern, string $message): string
    {
        // Pasted codes often carry spaces, and 2a must read as 2A.
        $code = strtoupper(Identifiers::normalize($value));
        if (! preg_match($pattern, $code)) {
            throw new InvalidArgumentException($message);
        }

        return $code;
    }
}
