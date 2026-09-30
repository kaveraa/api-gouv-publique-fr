<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Support;

use DateTimeImmutable;
use Exception;

final class Dates
{
    public static function parse(?string $value): ?DateTimeImmutable
    {
        // Relative words such as "now" are not API dates.
        if ($value === null || preg_match('/^\d{4}-\d{2}-\d{2}/', $value) !== 1) {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (Exception) {
            return null;
        }
    }
}
