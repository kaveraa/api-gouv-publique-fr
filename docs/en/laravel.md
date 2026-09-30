# Laravel

The package supports Laravel 12 and 13. Laravel 11 no longer receives security fixes, so it is not supported. The service provider and the `ApiGouv` alias are found automatically.

## Publish the config

```bash
php artisan vendor:publish --tag=api-gouv-config
```

This creates `config/api-gouv.php`. You only need it if you want to change a value.

## Config keys

| Key | Default | Meaning |
| --- | --- | --- |
| `timeout` | `10` | Seconds to wait for an API answer. |
| `attempts` | `3` | Total tries when the API answers 429 (rate limit). |
| `retry_delay_ms` | `300` | Pause between two tries, in milliseconds. |
| `cache.enabled` | `false` | Turn the response cache on or off. |
| `cache.store` | `null` | Name of a cache store. `null` uses the default store. |
| `entreprises.base_url` | `https://recherche-entreprises.api.gouv.fr` | Base URL of the company API. |
| `entreprises.cache_ttl` | `3600` | Cache time for company answers, in seconds. |
| `adresse.base_url` | `https://data.geopf.fr/geocodage` | Base URL of the address API. |
| `adresse.cache_ttl` | `86400` | Cache time for address answers, in seconds. |

## The facade

```php
use Kaveraa\ApiGouv\Laravel\ApiGouv;

ApiGouv::entreprises()->parSiren('812487973');
ApiGouv::adresse()->rechercher('8 bd du port amiens', 1);
```

## Dependency injection

You can inject the interfaces `EntreprisesApi` and `AdresseApi`:

```php
use Kaveraa\ApiGouv\Adresse\AdresseApi;
use Kaveraa\ApiGouv\Entreprises\EntreprisesApi;

class CompanyController
{
    public function __construct(
        private EntreprisesApi $entreprises,
        private AdresseApi $adresse,
    ) {}

    public function show(string $siren)
    {
        return $this->entreprises->parSiren($siren)->nomComplet;
    }
}
```

`ApiGouv::fake()` also replaces the injected interfaces. See [Testing](testing.md).

## Validation rules

The rules are in `Kaveraa\ApiGouv\Laravel\Rules`.

```php
use Kaveraa\ApiGouv\Laravel\Rules\EntrepriseExiste;
use Kaveraa\ApiGouv\Laravel\Rules\Siren;
use Kaveraa\ApiGouv\Laravel\Rules\Siret;

$request->validate([
    'siren' => ['required', new Siren],
    'siret' => ['required', new Siret],
]);
```

- `Siren` checks that the value has 9 digits and a valid check digit (Luhn). Spaces are allowed. It does not call the API.
- `Siret` checks that the value has 14 digits and a valid check digit. It also accepts the special SIRET numbers of La Poste. It does not call the API.
- `EntrepriseExiste` checks the SIREN like `Siren`, then calls the API to see if the company exists.

Rule objects are skipped when the value is empty. Add `required` if the field is mandatory.

### EntrepriseExiste is opt-in

`EntrepriseExiste` makes an HTTP call for each validation. Your form then depends on an outside service. So it is never added for you. Use it only where you accept this.

```php
$request->validate([
    'siren' => ['required', new EntrepriseExiste],
]);
```

It fails closed. When the API is not available (network error, rate limit, API error), the value is refused with the message "could not be verified because the company service is unavailable". This is a different message from "does not match any known company", which is used when the API says the company does not exist. Turn the cache on to reduce the calls.

## Enable the cache

The cache is off by default. In `config/api-gouv.php`:

```php
'cache' => [
    'enabled' => true,
    'store' => null,   // or the name of a store, for example 'redis'
],
```

Only successful answers are cached. See [Errors, cache and rate limit](errors-cache-rate-limit.md).

## Translations

The messages of the rules exist in English and French. To change them, publish the files:

```bash
php artisan vendor:publish --tag=api-gouv-lang
```

The files are copied to `lang/vendor/api-gouv`. The keys are `siren`, `siret`, `entreprise_existe` and `entreprise_indisponible`.
