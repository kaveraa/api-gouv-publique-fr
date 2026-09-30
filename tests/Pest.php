<?php

declare(strict_types=1);

use Kaveraa\ApiGouv\Tests\Laravel\TestCase;

// Only the Laravel folder needs Testbench, so the Unit tests run without Laravel.
// The guard keeps the "core only" run working when Testbench is not installed.
if (class_exists(Orchestra\Testbench\TestCase::class) && class_exists(TestCase::class)) {
    pest()->extend(TestCase::class)->in('Laravel');
}

function loadFixture(string $name): string
{
    return (string) file_get_contents(__DIR__.'/fixtures/'.$name);
}
