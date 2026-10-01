<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Symfony\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

/** The value must be a valid SIRET number (14 digits, Luhn, with the La Poste rule). Empty values pass: add NotBlank to require one. */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class Siret extends Constraint
{
    public string $message = 'This value must be a valid SIRET number (14 digits).';

    /** @param list<string>|null $groups */
    public function __construct(?string $message = null, ?array $groups = null, mixed $payload = null)
    {
        parent::__construct(null, $groups, $payload);

        $this->message = $message ?? $this->message;
    }
}
