<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Laravel\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Kaveraa\ApiGouv\Entreprises\EntreprisesApi;
use Kaveraa\ApiGouv\Exceptions\ApiException;
use Kaveraa\ApiGouv\Exceptions\NotFoundException;
use Kaveraa\ApiGouv\Support\Identifiers;
use Kaveraa\ApiGouv\Support\Payload;

/** Opt-in rule: it calls the API, so validation depends on an external service. */
final class EntrepriseExiste implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! Identifiers::isSiren($value)) {
            $fail('api-gouv::validation.siren')->translate();

            return;
        }

        try {
            app(EntreprisesApi::class)->parSiren(Identifiers::normalize(Payload::string($value)));
        } catch (NotFoundException) {
            $fail('api-gouv::validation.entreprise_existe')->translate();
        } catch (ApiException) {
            // Fail closed: a company we could not check is not accepted as existing.
            $fail('api-gouv::validation.entreprise_indisponible')->translate();
        }
    }
}
