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

## Wire the clients

```php
use Kaveraa\ApiGouv\Adresse\AdresseClient;
use Kaveraa\ApiGouv\ApiGouvClient;
use Kaveraa\ApiGouv\Entreprises\EntreprisesClient;
use Kaveraa\ApiGouv\Geo\GeoClient;
use Kaveraa\ApiGouv\Http\Requester;

$api = new ApiGouvClient(
    new EntreprisesClient(new Requester($transport, 'https://recherche-entreprises.api.gouv.fr')),
    new AdresseClient(new Requester($transport, 'https://data.geopf.fr/geocodage')),
    new GeoClient(new Requester($transport, 'https://geo.api.gouv.fr')),
);

echo $api->entreprises()->parSiren('812487973')->nomComplet;
echo $api->adresse()->rechercher('8 bd du port amiens', 1)[0]->label;
```

`ApiGouvClient` is a small holder. You can also use `EntreprisesClient`, `AdresseClient` and `GeoClient` alone. The three clients are required since 0.4.0.

## Symfony

In a Symfony application, the bundle does this wiring for you. See [Symfony](symfony.md).

If you prefer to declare the services yourself, wire the same classes as above: `Psr18Transport`, one `Requester` per API, then `EntreprisesClient`, `AdresseClient` and `GeoClient`, with the aliases `EntreprisesApi`, `AdresseApi` and `GeoApi`.
