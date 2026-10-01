<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Tests\Symfony;

use Kaveraa\ApiGouv\Symfony\ApiGouvBundle;

/** Simulates a project where symfony/validator is not installed. */
final class NoValidatorBundle extends ApiGouvBundle
{
    protected function validatorAvailable(): bool
    {
        return false;
    }
}
