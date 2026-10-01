<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Symfony\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

/** The value must be a valid SIREN number (9 digits, Luhn). Empty values pass: add NotBlank to require one. */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class Siren extends Constraint
{
    public string $message = 'api_gouv.siren';

    /** @param list<string>|null $groups */
    public function __construct(?string $message = null, ?array $groups = null, mixed $payload = null)
    {
        parent::__construct(null, $groups, $payload);

        $this->message = $message ?? $this->message;
    }
}
