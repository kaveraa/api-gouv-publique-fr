<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Tests\Symfony;

use Kaveraa\ApiGouv\Symfony\ApiGouvBundle;

/** Simulates a project where symfony/http-client is not installed. */
final class NoHttpClientBundle extends ApiGouvBundle
{
    protected function httpClientAvailable(): bool
    {
        return false;
    }
}
