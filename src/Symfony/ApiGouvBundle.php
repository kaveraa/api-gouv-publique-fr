<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Symfony;

use Http\Discovery\Psr17FactoryDiscovery;
use Kaveraa\ApiGouv\Adresse\AdresseApi;
use Kaveraa\ApiGouv\Adresse\AdresseClient;
use Kaveraa\ApiGouv\ApiGouvClient;
use Kaveraa\ApiGouv\Cache\ResponseCache;
use Kaveraa\ApiGouv\Entreprises\EntreprisesApi;
use Kaveraa\ApiGouv\Entreprises\EntreprisesClient;
use Kaveraa\ApiGouv\Geo\GeoApi;
use Kaveraa\ApiGouv\Geo\GeoClient;
use Kaveraa\ApiGouv\Http\Psr18Transport;
use Kaveraa\ApiGouv\Http\Requester;
use Kaveraa\ApiGouv\Http\Transport;
use Kaveraa\ApiGouv\Symfony\Validator\EntrepriseExisteValidator;
use Kaveraa\ApiGouv\Symfony\Validator\SirenValidator;
use Kaveraa\ApiGouv\Symfony\Validator\SiretValidator;
use Kaveraa\ApiGouv\Testing\FakeAdresse;
use Kaveraa\ApiGouv\Testing\FakeApiGouv;
use Kaveraa\ApiGouv\Testing\FakeEntreprises;
use Kaveraa\ApiGouv\Testing\FakeGeo;
use LogicException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\Component\Validator\Constraint;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

/**
 * Symfony bundle: configuration, the three API clients, the response cache,
 * the validation constraints and a fake mode for tests.
 *
 * Enable it in config/bundles.php:
 *
 *     Kaveraa\ApiGouv\Symfony\ApiGouvBundle::class => ['all' => true],
 *
 * Configure it in config/packages/api_gouv.yaml (every key is optional):
 *
 *     api_gouv:
 *         cache: { enabled: false, pool: cache.app }
 *         entreprises: { base_url: 'https://recherche-entreprises.api.gouv.fr', cache_ttl: 3600 }
 *         adresse: { base_url: 'https://data.geopf.fr/geocodage', cache_ttl: 86400 }
 *         geo: { base_url: 'https://geo.api.gouv.fr', cache_ttl: 86400 }
 *         fake: false
 */
class ApiGouvBundle extends AbstractBundle
{
    protected string $extensionAlias = 'api_gouv';

    private const APIS = [
        // name => [default base url, default ttl, client class, interface]
        'entreprises' => ['https://recherche-entreprises.api.gouv.fr', 3600, EntreprisesClient::class, EntreprisesApi::class],
        'adresse' => ['https://data.geopf.fr/geocodage', 86400, AdresseClient::class, AdresseApi::class],
        'geo' => ['https://geo.api.gouv.fr', 86400, GeoClient::class, GeoApi::class],
    ];

    /** AbstractBundle looks one folder up, so point it at this folder to find the translations. */
    public function getPath(): string
    {
        return __DIR__;
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $children = $definition->rootNode()->children();

        $children
            ->booleanNode('fake')
            ->info('Replace the API clients with in-memory fakes. Meant for the test environment.')
            ->defaultFalse()
            ->end()
            ->arrayNode('cache')
            ->addDefaultsIfNotSet()
            ->children()
            ->booleanNode('enabled')
            ->info('Cache successful answers in a PSR-6 pool (needs symfony/cache).')
            ->defaultFalse()
            ->end()
            ->scalarNode('pool')
            ->info('Service id of the PSR-6 pool to use.')
            ->cannotBeEmpty()
            ->defaultValue('cache.app')
            ->end()
            ->end()
            ->end();

        foreach (self::APIS as $name => [$url, $ttl]) {
            $api = new ArrayNodeDefinition($name);
            $api
                ->addDefaultsIfNotSet()
                ->children()
                ->scalarNode('base_url')
                ->info('Base URL of the API.')
                ->cannotBeEmpty()
                ->defaultValue($url)
                ->end()
                ->integerNode('cache_ttl')
                ->info('Cache time of the answers, in seconds.')
                ->min(0)
                ->defaultValue($ttl)
                ->end()
                ->end();
            $children->append($api);
        }

        $children->end();
    }

    /**
     * @param array{
     *     fake: bool,
     *     cache: array{enabled: bool, pool: string},
     *     entreprises: array{base_url: string, cache_ttl: int},
     *     adresse: array{base_url: string, cache_ttl: int},
     *     geo: array{base_url: string, cache_ttl: int},
     * } $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $parameters = $container->parameters();
        $parameters->set('api_gouv.fake', $config['fake']);
        $parameters->set('api_gouv.cache.enabled', $config['cache']['enabled']);
        foreach (array_keys(self::APIS) as $name) {
            $parameters->set("api_gouv.{$name}.base_url", $config[$name]['base_url']);
            $parameters->set("api_gouv.{$name}.cache_ttl", $config[$name]['cache_ttl']);
        }

        $services = $container->services();

        $this->registerConstraints($services);

        if ($config['fake']) {
            $this->registerFakes($services);

            return;
        }

        $this->registerClients($services, $config);
    }

    /** @internal Overridden in tests to simulate a project without symfony/http-client. */
    protected function httpClientAvailable(): bool
    {
        return class_exists(Psr18Client::class)
            && (class_exists(Psr17Factory::class) || class_exists(Psr17FactoryDiscovery::class));
    }

    /** @internal Overridden in tests to simulate a project without symfony/cache. */
    protected function cacheAvailable(): bool
    {
        return class_exists(Psr16Cache::class);
    }

    /** @internal Overridden in tests to simulate a project without symfony/validator. */
    protected function validatorAvailable(): bool
    {
        return class_exists(Constraint::class);
    }

    /**
     * @param array{
     *     cache: array{enabled: bool, pool: string},
     *     entreprises: array{base_url: string, cache_ttl: int},
     *     adresse: array{base_url: string, cache_ttl: int},
     *     geo: array{base_url: string, cache_ttl: int},
     * } $config
     */
    private function registerClients(ServicesConfigurator $services, array $config): void
    {
        if (! $this->httpClientAvailable()) {
            throw new LogicException('api_gouv: install symfony/http-client and nyholm/psr7 to call the APIs, or set api_gouv.fake to true in tests.');
        }

        // Psr18Client wraps the application http_client, so framework.http_client settings
        // (timeout, proxy, retry_failed) apply. It is also a PSR-17 request factory.
        $services->set('api_gouv.psr18_client', Psr18Client::class)
            ->args([service('http_client')]);

        $services->set('api_gouv.transport', Psr18Transport::class)
            ->args([service('api_gouv.psr18_client'), service('api_gouv.psr18_client')]);
        $services->alias(Transport::class, 'api_gouv.transport');

        $cache = null;
        if ($config['cache']['enabled']) {
            if (! $this->cacheAvailable()) {
                throw new LogicException('api_gouv: install symfony/cache to enable api_gouv.cache, or set cache.enabled to false.');
            }

            $services->set('api_gouv.psr16_cache', Psr16Cache::class)
                ->args([service($config['cache']['pool'])]);
            $services->set('api_gouv.cache', ResponseCache::class)
                ->args([service('api_gouv.psr16_cache')]);
            $cache = service('api_gouv.cache');
        }

        foreach (self::APIS as $name => [, , $client, $interface]) {
            $services->set("api_gouv.requester.{$name}", Requester::class)
                ->args([service(Transport::class), $config[$name]['base_url'], $cache, $config[$name]['cache_ttl']]);
            $services->set($client)
                ->args([service("api_gouv.requester.{$name}")]);
            $services->alias($interface, $client);
        }

        $services->set(ApiGouvClient::class)
            ->args([service(EntreprisesApi::class), service(AdresseApi::class), service(GeoApi::class)]);
    }

    /** The constraint validators, only when the Validator component is installed. */
    private function registerConstraints(ServicesConfigurator $services): void
    {
        if (! $this->validatorAvailable()) {
            return;
        }

        $services->set(SirenValidator::class)->tag('validator.constraint_validator');
        $services->set(SiretValidator::class)->tag('validator.constraint_validator');
        $services->set(EntrepriseExisteValidator::class)
            ->args([service(EntreprisesApi::class)])
            ->tag('validator.constraint_validator');
    }

    /** No transport and no HTTP client: a test cannot reach the network by accident. */
    private function registerFakes(ServicesConfigurator $services): void
    {
        $services->set(FakeEntreprises::class);
        $services->set(FakeAdresse::class);
        $services->set(FakeGeo::class);
        $services->alias(EntreprisesApi::class, FakeEntreprises::class);
        $services->alias(AdresseApi::class, FakeAdresse::class);
        $services->alias(GeoApi::class, FakeGeo::class);

        $services->set(FakeApiGouv::class)
            ->args([service(FakeEntreprises::class), service(FakeAdresse::class), service(FakeGeo::class)])
            ->public();
        $services->alias(ApiGouvClient::class, FakeApiGouv::class)
            ->public();
    }
}
