<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Support;

use DateTimeImmutable;
use Exception;

final class Dates
{
    public static function parse(?string $value): ?DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (Exception) {
            return null;
        }
    }
}
