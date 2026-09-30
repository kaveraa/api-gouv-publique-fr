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
use Kaveraa\ApiGouv\Http\Requester;
use Kaveraa\ApiGouv\Http\Transport;

final class ApiGouvServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/api-gouv.php', 'api-gouv');

        $this->app->singleton(Transport::class, fn () => new LaravelHttpTransport(
            timeout: (int) config('api-gouv.timeout'),
            attempts: (int) config('api-gouv.attempts'),
            retryDelayMs: (int) config('api-gouv.retry_delay_ms'),
        ));

        $this->app->singleton(ApiGouvClient::class, fn (Application $app) => new ApiGouvClient(
            new EntreprisesClient($this->requester($app, 'entreprises')),
            new AdresseClient($this->requester($app, 'adresse')),
        ));

        // Resolved on each call so ApiGouv::fake() also reaches injected interfaces.
        $this->app->bind(EntreprisesApi::class, fn (Application $app) => $app->make(ApiGouvClient::class)->entreprises());
        $this->app->bind(AdresseApi::class, fn (Application $app) => $app->make(ApiGouvClient::class)->adresse());
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../../lang', 'api-gouv');

        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../../config/api-gouv.php' => config_path('api-gouv.php')], 'api-gouv-config');
            $this->publishes([__DIR__.'/../../lang' => $this->app->langPath('vendor/api-gouv')], 'api-gouv-lang');
        }
    }

    private function requester(Application $app, string $api): Requester
    {
        $cache = null;
        if (config('api-gouv.cache.enabled')) {
            $cache = new ResponseCache(Cache::store(config('api-gouv.cache.store')));
        }

        return new Requester(
            $app->make(Transport::class),
            (string) config("api-gouv.{$api}.base_url"),
            $cache,
            (int) config("api-gouv.{$api}.cache_ttl"),
        );
    }
}
