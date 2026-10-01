<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Symfony\Validator;

use Kaveraa\ApiGouv\Support\Identifiers;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class SirenValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof Siren) {
            throw new UnexpectedTypeException($constraint, Siren::class);
        }
        if ($value === null || $value === '') {
            return;
        }
        if (! Identifiers::isSiren($value)) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
