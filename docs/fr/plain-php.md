# PHP simple

Vous n'avez pas besoin de Laravel. Le paquet de base a seulement besoin d'un client HTTP PSR-18 et d'une fabrique de requêtes PSR-17. Le cache est optionnel et demande un cache PSR-16.

```bash
composer require kaveraa/api-gouv-publique-fr symfony/http-client nyholm/psr7
```

## Construire le transport

`Psr18Transport` enveloppe n'importe quel client PSR-18. Il a besoin d'une `RequestFactoryInterface` PSR-17.

```php
use Kaveraa\ApiGouv\Http\Psr18Transport;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\HttpClient\Psr18Client;

$transport = new Psr18Transport(new Psr18Client(), new Psr17Factory());
```

Le transport PHP simple ne fait pas de nouvel essai. Voir [Erreurs, cache et limite de débit](errors-cache-rate-limit.md).

## Construire un Requester

`Requester` connaît l'URL de base. Donnez-lui le transport et l'URL de base :

```php
use Kaveraa\ApiGouv\Http\Requester;

$requester = new Requester($transport, 'https://recherche-entreprises.api.gouv.fr');
```

La signature complète est `new Requester(Transport $transport, string $baseUrl, ?ResponseCache $cache = null, int $ttl = 3600)`.

## Ajouter un cache (optionnel)

Enveloppez n'importe quel cache PSR-16 dans un `ResponseCache` :

```php
use Kaveraa\ApiGouv\Cache\ResponseCache;

$cache = new ResponseCache($psr16Cache);   // tout Psr\SimpleCache\CacheInterface

$requester = new Requester($transport, 'https://recherche-entreprises.api.gouv.fr', $cache, 3600);
```

Le dernier argument est la durée de cache en secondes.

## Brancher les clients

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

`ApiGouvClient` est un petit conteneur. Vous pouvez aussi utiliser `EntreprisesClient`, `AdresseClient` et `GeoClient` seuls.

## Exemple de services Symfony

Quand `symfony/http-client` est installé, Symfony enregistre un client PSR-18 sous le nom `Psr\Http\Client\ClientInterface`. Dans `config/services.yaml` :

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

    app.api_gouv.requester.geo:
        class: Kaveraa\ApiGouv\Http\Requester
        arguments:
            - '@Kaveraa\ApiGouv\Http\Psr18Transport'
            - 'https://geo.api.gouv.fr'

    Kaveraa\ApiGouv\Entreprises\EntreprisesClient:
        arguments: ['@app.api_gouv.requester.entreprises']

    Kaveraa\ApiGouv\Entreprises\EntreprisesApi:
        alias: Kaveraa\ApiGouv\Entreprises\EntreprisesClient

    Kaveraa\ApiGouv\Adresse\AdresseClient:
        arguments: ['@app.api_gouv.requester.adresse']

    Kaveraa\ApiGouv\Adresse\AdresseApi:
        alias: Kaveraa\ApiGouv\Adresse\AdresseClient

    Kaveraa\ApiGouv\Geo\GeoClient:
        arguments: ['@app.api_gouv.requester.geo']

    Kaveraa\ApiGouv\Geo\GeoApi:
        alias: Kaveraa\ApiGouv\Geo\GeoClient
```

Ensuite, injectez `EntreprisesApi`, `AdresseApi` ou `GeoApi` dans vos services.
