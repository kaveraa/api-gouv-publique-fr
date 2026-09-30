<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Tests\Symfony;

use Kaveraa\ApiGouv\Symfony\ApiGouvBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;

/** Minimal application: FrameworkBundle plus the bundle under test, with everything written to a temporary folder. */
final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    private readonly string $dir;

    /**
     * @param  array<string, mixed>  $apiGouv  the api_gouv configuration
     * @param  class-string<ApiGouvBundle>  $bundleClass  a subclass can simulate a missing package
     */
    public function __construct(
        private readonly array $apiGouv = [],
        private readonly bool $mockHttp = true,
        private readonly string $bundleClass = ApiGouvBundle::class,
    ) {
        $this->dir = sys_get_temp_dir().'/api-gouv-bundle-'.bin2hex(random_bytes(4));

        parent::__construct('test', true);
    }

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle;
        yield new ($this->bundleClass);
    }

    public function getProjectDir(): string
    {
        return $this->dir;
    }

    public function getCacheDir(): string
    {
        return $this->dir.'/cache';
    }

    public function getLogDir(): string
    {
        return $this->dir.'/log';
    }

    protected function build(ContainerBuilder $container): void
    {
        // Unused private services are removed at compile time, so the tests could not read them.
        // Making the bundle services public keeps them in the test container.
        $container->addCompilerPass(new class implements CompilerPassInterface
        {
            public function process(ContainerBuilder $container): void
            {
                $isOurs = static fn (string $id): bool => str_starts_with($id, 'api_gouv.') || str_starts_with($id, 'Kaveraa\\ApiGouv\\');

                foreach ($container->getDefinitions() as $id => $definition) {
                    if ($isOurs($id)) {
                        $definition->setPublic(true);
                    }
                }
                foreach ($container->getAliases() as $id => $alias) {
                    if ($isOurs($id)) {
                        $alias->setPublic(true);
                    }
                }
            }
        }, PassConfig::TYPE_BEFORE_REMOVING);
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $framework = [
            'secret' => 'test',
            'test' => true,
            'http_method_override' => false,
            'handle_all_throwables' => true,
            'php_errors' => ['log' => true],
            'default_locale' => 'en',
            'translator' => ['fallbacks' => ['en']],
            'validation' => ['email_validation_mode' => 'html5'],
        ];
        if ($this->mockHttp) {
            // Every request of the application http_client is answered by MockResponses.
            $framework['http_client'] = ['mock_response_factory' => MockResponses::class];
        }

        $container->extension('framework', $framework);
        $container->extension('api_gouv', $this->apiGouv);
        $container->services()->set(MockResponses::class);
    }
}
