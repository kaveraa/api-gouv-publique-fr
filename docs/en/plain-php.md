# Plain PHP

You do not need Laravel. The core package only needs a PSR-18 HTTP client and a PSR-17 request factory. The cache is optional and needs a PSR-16 cache.

```bash
composer require kaveraa/api-gouv-publique-fr symfony/http-client nyholm/psr7
```

## Build the transport

`Psr18Transport` wraps any PSR-18 client. It needs a PSR-17 `RequestFactoryInterface`.

```php
use Kaveraa\ApiGouv\Http\Psr18Transport;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\HttpClient\Psr18Client;

$transport = new Psr18Transport(new Psr18Client(), new Psr17Factory());
```

The plain PHP transport does not retry. See [Errors, cache and rate limit](errors-cache-rate-limit.md).

## Build a Requester

`Requester` knows the base URL. Give it the transport and the base URL:

```php
use Kaveraa\ApiGouv\Http\Requester;

$requester = new Requester($transport, 'https://recherche-entreprises.api.gouv.fr');
```

The full signature is `new Requester(Transport $transport, string $baseUrl, ?ResponseCache $cache = null, int $ttl = 3600)`.

## Add a cache (optional)

Wrap any PSR-16 cache in a `ResponseCache`:

```php
use Kaveraa\ApiGouv\Cache\ResponseCache;

$cache = new ResponseCache($psr16Cache);   // any Psr\SimpleCache\CacheInterface

$requester = new Requester($transport, 'https://recherche-entreprises.api.gouv.fr', $cache, 3600);
```

The last argument is the cache time in seconds.

## Wire both clients

```php
use Kaveraa\ApiGouv\Adresse\AdresseClient;
use Kaveraa\ApiGouv\ApiGouvClient;
use Kaveraa\ApiGouv\Entreprises\EntreprisesClient;
use Kaveraa\ApiGouv\Http\Requester;

$api = new ApiGouvClient(
    new EntreprisesClient(new Requester($transport, 'https://recherche-entreprises.api.gouv.fr')),
    new AdresseClient(new Requester($transport, 'https://data.geopf.fr/geocodage')),
);

echo $api->entreprises()->parSiren('812487973')->nomComplet;
echo $api->adresse()->rechercher('8 bd du port amiens', 1)[0]->label;
```

`ApiGouvClient` is a small holder. You can also use `EntreprisesClient` and `AdresseClient` alone.

## Symfony service example

When `symfony/http-client` is installed, Symfony registers a PSR-18 client under the name `Psr\Http\Client\ClientInterface`. In `config/services.yaml`:

```yaml
services:
    Nyholm\Psr7\Factory\Psr17Factory: ~

    Kaveraa\ApiGouv\Http\Psr18Transport:
        arguments:
            - '@Psr\Http\Client\ClientInterface'
            - '@Nyholm\Psr7\Factory\Psr17Factory'

    app.api_gouv.requester.entreprises:
        class: Kaveraa\ApiGouv\Http\Requester
        arguments:
            - '@Kaveraa\ApiGouv\Http\Psr18Transport'
            - 'https://recherche-entreprises.api.gouv.fr'

    app.api_gouv.requester.adresse:
        class: Kaveraa\ApiGouv\Http\Requester
        arguments:
            - '@Kaveraa\ApiGouv\Http\Psr18Transport'
            - 'https://data.geopf.fr/geocodage'

    Kaveraa\ApiGouv\Entreprises\EntreprisesClient:
        arguments: ['@app.api_gouv.requester.entreprises']

    Kaveraa\ApiGouv\Entreprises\EntreprisesApi:
        alias: Kaveraa\ApiGouv\Entreprises\EntreprisesClient

    Kaveraa\ApiGouv\Adresse\AdresseClient:
        arguments: ['@app.api_gouv.requester.adresse']

    Kaveraa\ApiGouv\Adresse\AdresseApi:
        alias: Kaveraa\ApiGouv\Adresse\AdresseClient
```

Then inject `EntreprisesApi` or `AdresseApi` in your services.
