<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Support;

final class Luhn
{
    public static function isValid(string $digits): bool
    {
        if ($digits === '' || ! ctype_digit($digits)) {
            return false;
        }

        $sum = 0;
        $double = false;
        for ($i = strlen($digits) - 1; $i >= 0; $i--) {
            $digit = (int) $digits[$i];
            if ($double) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            $sum += $digit;
            $double = ! $double;
        }

        return $sum % 10 === 0;
    }
}
