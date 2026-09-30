<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Laravel\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Kaveraa\ApiGouv\Support\Identifiers;

final class Siret implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! Identifiers::isSiret($value)) {
            $fail('api-gouv::validation.siret')->translate();
        }
    }
}
