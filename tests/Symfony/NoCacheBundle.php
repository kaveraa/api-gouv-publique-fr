<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Tests\Symfony;

use Kaveraa\ApiGouv\Symfony\ApiGouvBundle;

/** Simulates a project where symfony/cache is not installed. */
final class NoCacheBundle extends ApiGouvBundle
{
    protected function cacheAvailable(): bool
    {
        return false;
    }
}
