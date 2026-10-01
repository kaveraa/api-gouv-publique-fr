<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Symfony\Validator;

use Kaveraa\ApiGouv\Entreprises\EntreprisesApi;
use Kaveraa\ApiGouv\Exceptions\ApiException;
use Kaveraa\ApiGouv\Exceptions\NotFoundException;
use Kaveraa\ApiGouv\Support\Identifiers;
use Kaveraa\ApiGouv\Support\Payload;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class EntrepriseExisteValidator extends ConstraintValidator
{
    public function __construct(private readonly EntreprisesApi $entreprises) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof EntrepriseExiste) {
            throw new UnexpectedTypeException($constraint, EntrepriseExiste::class);
        }
        if ($value === null || $value === '') {
            return;
        }
        if (! Identifiers::isSiren($value)) {
            $this->context->buildViolation($constraint->formatMessage)->addViolation();

            return;
        }

        try {
            $this->entreprises->parSiren(Identifiers::normalize(Payload::string($value)));
        } catch (NotFoundException) {
            $this->context->buildViolation($constraint->message)->addViolation();
        } catch (ApiException) {
            // Fail closed: a company we could not check is not accepted as existing.
            $this->context->buildViolation($constraint->unavailableMessage)->addViolation();
        }
    }
}
