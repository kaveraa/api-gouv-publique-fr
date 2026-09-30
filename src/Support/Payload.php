<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Support;

/** Reads loosely typed decoded JSON (or config) values into precise types. */
final class Payload
{
    /** Empty strings count as missing. */
    public static function text(mixed $value): ?string
    {
        return is_scalar($value) && $value !== '' ? (string) $value : null;
    }

    public static function string(mixed $value, string $default = ''): string
    {
        return is_scalar($value) ? (string) $value : $default;
    }

    public static function int(mixed $value, int $default = 0): int
    {
        return is_numeric($value) ? (int) $value : $default;
    }

    public static function float(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    /** @return array<string, mixed> */
    public static function map(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $map = [];
        foreach ($value as $key => $item) {
            $map[(string) $key] = $item;
        }

        return $map;
    }

    /** @return list<array<string, mixed>> Items that are not objects are skipped. */
    public static function maps(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $maps = [];
        foreach ($value as $item) {
            if (is_array($item)) {
                $maps[] = self::map($item);
            }
        }

        return $maps;
    }

    /** @return list<string> Items that are not scalar are skipped. */
    public static function strings(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $strings = [];
        foreach ($value as $item) {
            if (is_scalar($item)) {
                $strings[] = (string) $item;
            }
        }

        return $strings;
    }
}
