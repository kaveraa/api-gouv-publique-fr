<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Tests\Laravel;

use Kaveraa\ApiGouv\Laravel\ApiGouvServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [ApiGouvServiceProvider::class];
    }
}
