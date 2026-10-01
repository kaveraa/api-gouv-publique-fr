<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Symfony\Validator;

use Kaveraa\ApiGouv\Support\Identifiers;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class SiretValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof Siret) {
            throw new UnexpectedTypeException($constraint, Siret::class);
        }
        if ($value === null || $value === '') {
            return;
        }
        if (! Identifiers::isSiret($value)) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
