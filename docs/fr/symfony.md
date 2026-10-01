# Symfony

Le bundle `ApiGouvBundle` enregistre les trois clients d'API comme services. Il donne aussi un cache des réponses sur un pool de cache Symfony, trois contraintes de validation et un mode factice pour les tests. Il envoie les requêtes avec le `http_client` de votre application. Il faut PHP 8.3+ et Symfony 7.4 ou 8. Symfony 8 demande PHP 8.4.

## Installation

```bash
composer require kaveraa/api-gouv-publique-fr symfony/http-client nyholm/psr7
```

Puis activez le bundle dans `config/bundles.php` :

```php
return [
    // ...
    Kaveraa\ApiGouv\Symfony\ApiGouvBundle::class => ['all' => true],
];
```

Le paquet n'a pas de recette Symfony Flex, donc vous ajoutez la ligne du bundle à la main.

## Configuration

Créez `config/packages/api_gouv.yaml`. Toutes les clés sont optionnelles. Voici le fichier complet avec les valeurs par défaut :

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

| Clé | Défaut | Sens |
| --- | --- | --- |
| `cache.enabled` | `false` | Met en cache les réponses réussies. Demande `symfony/cache`. |
| `cache.pool` | `cache.app` | Identifiant du service du pool de cache PSR-6 à utiliser. |
| `<api>.base_url` | voir le fichier ci-dessus | URL de base de l'API. `<api>` vaut `entreprises`, `adresse` ou `geo`. |
| `<api>.cache_ttl` | `3600` pour `entreprises`, `86400` pour `adresse` et `geo` | Durée de cache des réponses, en secondes. |
| `fake` | `false` | Remplace les clients par des faux en mémoire. Prévu pour les tests. |

Les délais d'attente, les proxys et les nouveaux essais relèvent de `framework.http_client`, que le bundle utilise.

## Utiliser les clients

Le bundle enregistre `EntreprisesApi`, `AdresseApi`, `GeoApi` et `ApiGouvClient` pour l'autowiring. Demandez-les dans un constructeur :

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
        $company = $this->entreprises->parSiren($siren);   // OCTO pour 812487973
        $commune = $this->geo->commune('80021');            // Amiens

        return ['company' => $company->nomComplet, 'commune' => $commune->nom];
    }
}
```

La méthode `show()` renvoie un tableau pour garder l'exemple court. Dans un vrai contrôleur, renvoyez-le avec `new JsonResponse(...)`. `AdresseApi` fonctionne de la même façon.

`ApiGouvClient` est le point d'entrée unique. Il donne les trois clients :

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

Les services sont privés. Injectez-les, ne les lisez pas dans le conteneur.

## Nouvel essai en cas de limite de débit (HTTP 429)

Le bundle ne refait pas d'essai tout seul. Utilisez les nouveaux essais du client HTTP de Symfony, dans `config/packages/framework.yaml` :

```yaml
framework:
    http_client:
        default_options:
            retry_failed:
                http_codes: [429, 500, 502, 503, 504]
                max_retries: 3
                delay: 300
```

Le bundle enveloppe le client de l'application, donc ce réglage s'applique à chaque appel d'API ; la `RateLimitException` est levée quand les essais sont épuisés. Ces options s'appliquent aussi aux autres requêtes de votre application.

## Cache

Le cache est désactivé par défaut. Activez-le :

```yaml
api_gouv:
    cache:
        enabled: true
        pool: cache.app
```

`pool` est l'identifiant du service de n'importe quel pool de cache PSR-6. Vous pouvez déclarer un pool réservé au paquet :

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

- Seules les réponses réussies sont mises en cache. Les erreurs ne sont jamais stockées.
- Une liste vide est une réponse réussie, donc elle est aussi mise en cache. Une entreprise créée récemment continue de renvoyer "non trouvée" jusqu'à la fin de `cache_ttl`.
- La durée de cache par défaut est de 3600 secondes pour `entreprises`, 86400 secondes pour `adresse` et 86400 secondes pour `geo`. Changez-la avec `<api>.cache_ttl`.
- Le mode factice ignore le cache.

Voir [Erreurs, cache et limite de débit](errors-cache-rate-limit.md).

## Validation

Installez le composant Validator et le traducteur :

```bash
composer require symfony/validator symfony/translation
```

`symfony/translation` est nécessaire pour les messages en français. Les messages en anglais sont intégrés.

Placez les contraintes sur une propriété d'un DTO ou d'une entité :

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

- `Siren` vérifie que la valeur a 9 chiffres et une clé de contrôle valide (Luhn). Les espaces sont acceptés. Elle n'appelle pas l'API.
- `Siret` vérifie que la valeur a 14 chiffres et une clé de contrôle valide. Elle accepte aussi les SIRET particuliers de La Poste. Elle n'appelle pas l'API.
- `EntrepriseExiste` vérifie le SIREN comme `Siren`, puis appelle l'API pour voir si l'entreprise existe.

Les trois contraintes acceptent une valeur vide (`null` ou `''`). Ajoutez `NotBlank` si la valeur est obligatoire. Dans l'exemple, `siren` est obligatoire et `siret` ne l'est pas.

### EntrepriseExiste est optionnelle

`EntrepriseExiste` fait un appel HTTP à chaque validation. Votre formulaire dépend alors d'un service extérieur. Elle n'est donc jamais ajoutée pour vous. Utilisez-la seulement là où vous acceptez cela.

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

Elle échoue en mode fermé. Quand l'API n'est pas disponible (erreur réseau, limite de débit, erreur de l'API), la valeur est refusée avec le message "n'a pas pu être vérifiée car le service des entreprises est indisponible". Ce message est différent de "ne correspond à aucune entreprise connue", utilisé quand l'API dit que l'entreprise n'existe pas. Un SIREN mal formé reçoit le message de `Siren`, sans aucun appel. Activez le cache pour réduire les appels.

### Messages personnalisés

Chaque contrainte accepte un argument `message`. `EntrepriseExiste` accepte aussi `unavailableMessage` (API indisponible) et `formatMessage` (SIREN mal formé).

```php
use Kaveraa\ApiGouv\Symfony\Validator\EntrepriseExiste;
use Kaveraa\ApiGouv\Symfony\Validator\Siren;

final class SignupForm
{
    #[Siren(message: 'Saisissez un SIREN valide.')]
    public ?string $siren = null;

    #[EntrepriseExiste(
        message: 'Nous ne connaissons pas cette entreprise.',
        unavailableMessage: 'Réessayez dans quelques minutes.',
        formatMessage: 'Saisissez un SIREN valide.',
    )]
    public ?string $supplier = null;
}
```

Elles acceptent aussi `groups` et `payload`, comme toute contrainte Symfony. Par exemple `#[Siren(groups: ['signup'])]`.

### Traductions

Les messages par défaut sont des phrases en anglais, utilisées comme clés de traduction comme pour les contraintes de Symfony, dans le domaine `validators`. Les traductions françaises sont livrées avec le bundle.

| Message |
| --- |
| Cette valeur doit être un numéro SIREN valide (9 chiffres). |
| Cette valeur doit être un numéro SIRET valide (14 chiffres). |
| Cette valeur ne correspond à aucune entreprise connue. |
| Cette valeur n'a pas pu être vérifiée car le service des entreprises est indisponible. |

Pour changer un texte, utilisez la phrase comme clé de votre traduction, par exemple dans `translations/validators.fr.yaml` :

```yaml
# translations/validators.fr.yaml
'This value must be a valid SIREN number (9 digits).': 'Ce numéro SIREN est invalide.'
```

Les fichiers de votre application passent avant ceux du bundle.

Si votre `default_locale` n'est ni `en` ni `fr`, ajoutez `en` à `framework.translator.fallbacks` pour que les messages s'affichent quand même en anglais.

## Tests

Activez le mode factice pour l'environnement de test, dans `config/packages/api_gouv.yaml` :

```yaml
when@test:
    api_gouv:
        fake: true
```

Les clients sont alors `FakeEntreprises`, `FakeAdresse` et `FakeGeo`. Le bundle n'enregistre aucun client HTTP, donc un test ne peut pas appeler les vraies API. `FakeApiGouv` contient les trois faux. Lisez-le dans le conteneur de test, remplissez-le avec `Factories`, puis vérifiez les appels dans `$calls` :

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

Ce test suppose une route `/company/{siren}` qui renvoie le résultat de `CompanyController::show()` en JSON. Remplissez les faux après `createClient()` et avant la requête. Le client démarre un nouveau noyau entre deux requêtes, ce qui vide les faux. Appelez `$client->disableReboot()` si un test envoie plusieurs requêtes.

Le mode factice est une valeur de configuration, pas un interrupteur à l'exécution, car le conteneur Symfony est compilé : les faux remplacent les clients quand le conteneur est construit, et le bundle n'enregistre aucun service HTTP pour les API.

Les valeurs par défaut de `Factories` et les règles de correspondance des faux sont dans [Tests](testing.md).

## Erreurs

Le bundle vérifie les paquets nécessaires quand le conteneur est construit :

| Message | Que faire |
| --- | --- |
| `api_gouv: install symfony/http-client and nyholm/psr7 to call the APIs, or set api_gouv.fake to true in tests.` | Lancez `composer require symfony/http-client nyholm/psr7`. |
| `api_gouv: install symfony/cache to enable api_gouv.cache, or set cache.enabled to false.` | Lancez `composer require symfony/cache`, ou désactivez le cache. |

Les deux sont une `LogicException`. Sans `symfony/validator`, les contraintes ne sont pas enregistrées et il n'y a pas d'erreur.

Les erreurs d'API (`NotFoundException`, `RateLimitException`, `ApiException`) sont les mêmes qu'en PHP simple. Voir [Erreurs, cache et limite de débit](errors-cache-rate-limit.md).
