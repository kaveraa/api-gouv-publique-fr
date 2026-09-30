<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Tests\Symfony;

use Kaveraa\ApiGouv\Symfony\ApiGouvBundle;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

/** Boots the test application with a given api_gouv configuration and removes its files afterwards. */
abstract class BundleTestCase extends TestCase
{
    protected ?TestKernel $kernel = null;

    protected function tearDown(): void
    {
        if ($this->kernel !== null) {
            $dir = $this->kernel->getProjectDir();
            $this->kernel->shutdown();
            (new Filesystem)->remove($dir);
            $this->kernel = null;
        }
        MockResponses::reset();
    }

    /**
     * @param  array<string, mixed>  $config  the api_gouv configuration
     * @param  class-string<ApiGouvBundle>|null  $bundleClass
     * @return ContainerInterface the test container, which can read private services
     */
    protected function boot(array $config = [], bool $mockHttp = true, ?string $bundleClass = null): ContainerInterface
    {
        $this->kernel = new TestKernel($config, $mockHttp, $bundleClass ?? ApiGouvBundle::class);
        $this->kernel->boot();

        $container = $this->kernel->getContainer()->get('test.service_container');
        assert($container instanceof ContainerInterface);

        return $container;
    }
}
