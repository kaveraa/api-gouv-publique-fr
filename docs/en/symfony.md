# Symfony

The bundle `ApiGouvBundle` registers the three API clients as services. It also gives a response cache on a Symfony cache pool, three validation constraints and a fake mode for tests. It sends the requests through the `http_client` of your application. You need PHP 8.3+ and Symfony 7.2 or 8. Symfony 8 needs PHP 8.4.

## Install

```bash
composer require kaveraa/api-gouv-publique-fr symfony/http-client nyholm/psr7
```

Then enable the bundle in `config/bundles.php`:

```php
return [
    // ...
    Kaveraa\ApiGouv\Symfony\ApiGouvBundle::class => ['all' => true],
];
```

The package has no Symfony Flex recipe, so you add the bundle line by hand.

## Configuration

Create `config/packages/api_gouv.yaml`. Every key is optional. This is the full file with the default values:

```yaml
api_gouv:
    cache:
        enabled: false
        pool: cache.app
    entreprises:
        base_url: 'https://recherche-entreprises.api.gouv.fr'
        cache_ttl: 3600
    adresse:
        base_url: 'https://data.geopf.fr/geocodage'
        cache_ttl: 86400
    geo:
        base_url: 'https://geo.api.gouv.fr'
        cache_ttl: 86400
    fake: false
```

| Key | Default | Meaning |
| --- | --- | --- |
| `cache.enabled` | `false` | Cache the successful answers. Needs `symfony/cache`. |
| `cache.pool` | `cache.app` | Service id of the PSR-6 cache pool to use. |
| `<api>.base_url` | see the file above | Base URL of the API. `<api>` is `entreprises`, `adresse` or `geo`. |
| `<api>.cache_ttl` | `3600` for `entreprises`, `86400` for `adresse` and `geo` | Cache time of the answers, in seconds. |
| `fake` | `false` | Replace the clients with in-memory fakes. Meant for tests. |

Timeouts, proxies and retries belong to `framework.http_client`, which the bundle uses.

## Use the clients

The bundle registers `EntreprisesApi`, `AdresseApi`, `GeoApi` and `ApiGouvClient` for autowiring. Ask for them in a constructor:

```php
use Kaveraa\ApiGouv\Entreprises\EntreprisesApi;
use Kaveraa\ApiGouv\Geo\GeoApi;

final class CompanyController
{
    public function __construct(
        private readonly EntreprisesApi $entreprises,
        private readonly GeoApi $geo,
    ) {}

    public function show(string $siren): array
    {
        $company = $this->entreprises->parSiren($siren);   // OCTO for 812487973
        $commune = $this->geo->commune('80021');            // Amiens

        return ['company' => $company->nomComplet, 'commune' => $commune->nom];
    }
}
```

The `show()` method returns an array to keep the example short. In a real controller, return it with `new JsonResponse(...)`. `AdresseApi` works the same way.

`ApiGouvClient` is the single entry point. It gives the three clients:

```php
use Kaveraa\ApiGouv\ApiGouvClient;

final class AddressFinder
{
    public function __construct(private readonly ApiGouvClient $api) {}

    public function firstLabel(string $query): ?string
    {
        return $this->api->adresse()->rechercher($query, 1)[0]->label ?? null;
    }
}

// firstLabel('8 bd du port amiens') -> '8 Boulevard du Port 80000 Amiens'
```

The services are private. Inject them, do not read them from the container.

## Retry on rate limit (HTTP 429)

The bundle does not retry by itself. Use the retry of the Symfony HTTP client, in `config/packages/framework.yaml`:

```yaml
framework:
    http_client:
        default_options:
            retry_failed:
                http_codes: [429, 500, 502, 503, 504]
                max_retries: 3
                delay: 300
```

The bundle wraps the application client, so this applies to every API call; the `RateLimitException` is thrown when the retries are exhausted. These options also apply to the other requests of your application.

## Cache

The cache is off by default. Turn it on:

```yaml
api_gouv:
    cache:
        enabled: true
        pool: cache.app
```

`pool` is the service id of any PSR-6 cache pool. You can declare a pool for the package only:

```yaml
framework:
    cache:
        pools:
            cache.api_gouv:
                adapter: cache.adapter.filesystem

api_gouv:
    cache:
        enabled: true
        pool: cache.api_gouv
```

- Only successful answers are cached. Errors are never stored.
- An empty list is a successful answer, so it is cached too. A company created recently keeps returning "not found" until `cache_ttl` expires.
- The default cache time is 3600 seconds for `entreprises`, 86400 seconds for `adresse` and 86400 seconds for `geo`. Change it with `<api>.cache_ttl`.
- The fake mode ignores the cache.

See [Errors, cache and rate limit](errors-cache-rate-limit.md).

## Validation

The constraints need the Validator component and the translator:

```bash
composer require symfony/validator symfony/translation
```

The messages are translation keys, so the translator is needed. Without it, users see a key such as `api_gouv.siren`.

Put them on a property of a DTO or an entity:

```php
use Kaveraa\ApiGouv\Symfony\Validator\Siren;
use Kaveraa\ApiGouv\Symfony\Validator\Siret;
use Symfony\Component\Validator\Constraints as Assert;

final class CompanyForm
{
    #[Assert\NotBlank]
    #[Siren]
    public ?string $siren = null;

    #[Siret]
    public ?string $siret = null;
}
```

- `Siren` checks that the value has 9 digits and a valid check digit (Luhn). Spaces are allowed. It does not call the API.
- `Siret` checks that the value has 14 digits and a valid check digit. It also accepts the special SIRET numbers of La Poste. It does not call the API.
- `EntrepriseExiste` checks the SIREN like `Siren`, then calls the API to see if the company exists.

The three constraints accept an empty value (`null` or `''`). Add `NotBlank` if the value is required. In the example, `siren` is required and `siret` is not.

### EntrepriseExiste is opt-in

`EntrepriseExiste` makes an HTTP call for each validation. Your form then depends on an outside service. So it is never added for you. Use it only where you accept this.

```php
use Kaveraa\ApiGouv\Symfony\Validator\EntrepriseExiste;
use Symfony\Component\Validator\Constraints as Assert;

final class SupplierForm
{
    #[Assert\NotBlank]
    #[EntrepriseExiste]
    public ?string $siren = null;
}
```

It fails closed. When the API is not available (network error, rate limit, API error), the value is refused with the message "could not be verified because the company service is unavailable". This is a different message from "does not match any known company", which is used when the API says the company does not exist. A malformed SIREN gets the `Siren` message, without any call. Turn the cache on to reduce the calls.

### Custom messages

Each constraint takes a `message` argument. `EntrepriseExiste` also takes `unavailableMessage` (API not available) and `formatMessage` (malformed SIREN).

```php
use Kaveraa\ApiGouv\Symfony\Validator\EntrepriseExiste;
use Kaveraa\ApiGouv\Symfony\Validator\Siren;

final class SignupForm
{
    #[Siren(message: 'Please enter a valid SIREN.')]
    public ?string $siren = null;

    #[EntrepriseExiste(
        message: 'We do not know this company.',
        unavailableMessage: 'Please try again in a few minutes.',
        formatMessage: 'Please enter a valid SIREN.',
    )]
    public ?string $supplier = null;
}
```

They also take `groups` and `payload`, like any Symfony constraint. For example `#[Siren(groups: ['signup'])]`.

### Translations

The messages exist in English and French, in the translation domain `validators`. The keys are:

| Key | English text |
| --- | --- |
| `api_gouv.siren` | This value must be a valid SIREN number (9 digits). |
| `api_gouv.siret` | This value must be a valid SIRET number (14 digits). |
| `api_gouv.entreprise_existe` | This value does not match any known company. |
| `api_gouv.entreprise_indisponible` | This value could not be verified because the company service is unavailable. |

To change a text, add the key to the translations of your application, for example in `translations/validators.fr.yaml`:

```yaml
api_gouv.siren: 'Ce numéro SIREN est invalide.'
api_gouv.entreprise_existe: 'Cette entreprise est inconnue.'
```

The files of your application win over the files of the bundle.

If your `default_locale` is not `en` or `fr`, add `en` to `framework.translator.fallbacks` so the messages still appear in English.

## Tests

Turn the fake mode on for the test environment, in `config/packages/api_gouv.yaml`:

```yaml
when@test:
    api_gouv:
        fake: true
```

The clients are then `FakeEntreprises`, `FakeAdresse` and `FakeGeo`. The bundle registers no HTTP client, so a test cannot call the real APIs. `FakeApiGouv` holds the three fakes. Read it from the test container, fill it with `Factories`, then check the calls in `$calls`:

```php
namespace App\Tests\Controller;

use Kaveraa\ApiGouv\Testing\Factories;
use Kaveraa\ApiGouv\Testing\FakeApiGouv;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CompanyControllerTest extends WebTestCase
{
    public function testShowsTheCompany(): void
    {
        $client = static::createClient();

        $fake = static::getContainer()->get(FakeApiGouv::class);
        $fake->entreprises()->with(Factories::entreprise(['siren' => '812487973', 'nomComplet' => 'OCTO']));
        $fake->geo()->with(Factories::commune());   // Amiens, code 80021

        $client->request('GET', '/company/812487973');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('OCTO', (string) $client->getResponse()->getContent());
        self::assertSame([['parSiren', '812487973']], $fake->entreprises()->calls);
        self::assertSame([['commune', '80021']], $fake->geo()->calls);
    }
}
```

This test supposes a route `/company/{siren}` that returns the result of `CompanyController::show()` as JSON. Fill the fakes after `createClient()` and before the request. The client boots a new kernel between two requests, which empties the fakes. Call `$client->disableReboot()` if one test sends several requests.

The fake mode is a configuration value, not a switch at runtime, because the Symfony container is compiled: the fakes replace the clients when the container is built, and the bundle registers no HTTP service for the APIs.

The `Factories` defaults and the matching rules of the fakes are in [Testing](testing.md).

## Errors

The bundle checks the needed packages when the container is built:

| Message | What to do |
| --- | --- |
| `api_gouv: install symfony/http-client and nyholm/psr7 to call the APIs, or set api_gouv.fake to true in tests.` | Run `composer require symfony/http-client nyholm/psr7`. |
| `api_gouv: install symfony/cache to enable api_gouv.cache, or set cache.enabled to false.` | Run `composer require symfony/cache`, or turn the cache off. |

Both are a `LogicException`. Without `symfony/validator`, the constraints are not registered and there is no error.

The API errors (`NotFoundException`, `RateLimitException`, `ApiException`) are the same as in plain PHP. See [Errors, cache and rate limit](errors-cache-rate-limit.md).
