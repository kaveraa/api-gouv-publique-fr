<?php

declare(strict_types=1);

use Kaveraa\ApiGouv\Tests\Laravel\TestCase;
use Kaveraa\ApiGouv\Tests\Symfony\BundleTestCase;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;

// Only the Laravel folder needs Testbench, so the Unit tests run without Laravel.
// The guard keeps the "core only" run working when Testbench is not installed.
if (class_exists(Orchestra\Testbench\TestCase::class) && class_exists(TestCase::class)) {
    pest()->extend(TestCase::class)->in('Laravel');
}

// The Symfony suite needs FrameworkBundle; the core-only CI job runs without it.
if (class_exists(FrameworkBundle::class) && class_exists(BundleTestCase::class)) {
    pest()->extend(BundleTestCase::class)->in('Symfony');
}

function loadFixture(string $name): string
{
    return (string) file_get_contents(__DIR__.'/fixtures/'.$name);
}
