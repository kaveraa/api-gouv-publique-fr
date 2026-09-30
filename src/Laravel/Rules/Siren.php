<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Laravel\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Kaveraa\ApiGouv\Support\Identifiers;

final class Siren implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! Identifiers::isSiren($value)) {
            $fail('api-gouv::validation.siren')->translate();
        }
    }
}
