<?php

declare(strict_types=1);

namespace Kaveraa\ApiGouv\Laravel;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use Kaveraa\ApiGouv\Adresse\AdresseApi;
use Kaveraa\ApiGouv\Adresse\AdresseClient;
use Kaveraa\ApiGouv\ApiGouvClient;
use Kaveraa\ApiGouv\Cache\ResponseCache;
use Kaveraa\ApiGouv\Entreprises\EntreprisesApi;
use Kaveraa\ApiGouv\Entreprises\EntreprisesClient;
use Kaveraa\ApiGouv\Geo\GeoApi;
use Kaveraa\ApiGouv\Geo\GeoClient;
use Kaveraa\ApiGouv\Http\Requester;
use Kaveraa\ApiGouv\Http\Transport;
use Kaveraa\ApiGouv\Support\Payload;

final class ApiGouvServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/api-gouv.php', 'api-gouv');

        $this->app->singleton(Transport::class, fn () => new LaravelHttpTransport(
            timeout: Payload::int(config('api-gouv.timeout'), 10),
            attempts: Payload::int(config('api-gouv.attempts'), 3),
            retryDelayMs: Payload::int(config('api-gouv.retry_delay_ms'), 300),
        ));

        $this->app->singleton(ApiGouvClient::class, fn (Application $app) => new ApiGouvClient(
            new EntreprisesClient($this->requester($app, 'entreprises', 3600, 'https://recherche-entreprises.api.gouv.fr')),
            new AdresseClient($this->requester($app, 'adresse', 86400, 'https://data.geopf.fr/geocodage')),
            new GeoClient($this->requester($app, 'geo', 86400, 'https://geo.api.gouv.fr')),
        ));

        // Resolved on each call so ApiGouv::fake() also reaches injected interfaces.
        $this->app->bind(EntreprisesApi::class, fn (Application $app) => $app->make(ApiGouvClient::class)->entreprises());
        $this->app->bind(AdresseApi::class, fn (Application $app) => $app->make(ApiGouvClient::class)->adresse());
        $this->app->bind(GeoApi::class, fn (Application $app) => $app->make(ApiGouvClient::class)->geo());
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../../lang', 'api-gouv');

        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../../config/api-gouv.php' => config_path('api-gouv.php')], 'api-gouv-config');
            $this->publishes([__DIR__.'/../../lang' => $this->app->langPath('vendor/api-gouv')], 'api-gouv-lang');
        }
    }

    // The defaults match config/api-gouv.php, for a published config that leaves a key unset.
    private function requester(Application $app, string $api, int $defaultTtl, string $defaultBaseUrl): Requester
    {
        $cache = null;
        if (config('api-gouv.cache.enabled')) {
            $cache = new ResponseCache(Cache::store(Payload::text(config('api-gouv.cache.store'))));
        }

        return new Requester(
            $app->make(Transport::class),
            Payload::text(config("api-gouv.{$api}.base_url")) ?? $defaultBaseUrl,
            $cache,
            Payload::int(config("api-gouv.{$api}.cache_ttl"), $defaultTtl),
        );
    }
}
