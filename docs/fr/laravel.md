# Laravel

Le paquet fonctionne avec Laravel 12 et 13. Laravel 11 ne reçoit plus de correctifs de sécurité, il n'est donc pas pris en charge. Le fournisseur de services et l'alias `ApiGouv` sont trouvés automatiquement.

## Publier la config

```bash
php artisan vendor:publish --tag=api-gouv-config
```

Cela crée `config/api-gouv.php`. Vous en avez besoin seulement si vous voulez changer une valeur.

## Clés de config

| Clé | Défaut | Sens |
| --- | --- | --- |
| `timeout` | `10` | Secondes d'attente pour une réponse de l'API. |
| `attempts` | `3` | Nombre total d'essais quand l'API répond 429 (limite de débit). |
| `retry_delay_ms` | `300` | Pause entre deux essais, en millisecondes. |
| `cache.enabled` | `false` | Active ou coupe le cache des réponses. |
| `cache.store` | `null` | Nom d'un store de cache. `null` utilise le store par défaut. |
| `entreprises.base_url` | `https://recherche-entreprises.api.gouv.fr` | URL de base de l'API des entreprises. |
| `entreprises.cache_ttl` | `3600` | Durée de cache des réponses entreprises, en secondes. |
| `adresse.base_url` | `https://data.geopf.fr/geocodage` | URL de base de l'API d'adresses. |
| `adresse.cache_ttl` | `86400` | Durée de cache des réponses adresses, en secondes. |

## La façade

```php
use Kaveraa\ApiGouv\Laravel\ApiGouv;

ApiGouv::entreprises()->parSiren('812487973');
ApiGouv::adresse()->rechercher('8 bd du port amiens', 1);
```

## Injection de dépendances

Vous pouvez injecter les interfaces `EntreprisesApi` et `AdresseApi` :

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

`ApiGouv::fake()` remplace aussi les interfaces injectées. Voir [Tests](testing.md).

## Règles de validation

Les règles sont dans `Kaveraa\ApiGouv\Laravel\Rules`.

```php
use Kaveraa\ApiGouv\Laravel\Rules\EntrepriseExiste;
use Kaveraa\ApiGouv\Laravel\Rules\Siren;
use Kaveraa\ApiGouv\Laravel\Rules\Siret;

$request->validate([
    'siren' => ['required', new Siren],
    'siret' => ['required', new Siret],
]);
```

- `Siren` vérifie que la valeur a 9 chiffres et une clé de contrôle valide (Luhn). Les espaces sont acceptés. Elle n'appelle pas l'API.
- `Siret` vérifie que la valeur a 14 chiffres et une clé de contrôle valide. Elle accepte aussi les SIRET particuliers de La Poste. Elle n'appelle pas l'API.
- `EntrepriseExiste` vérifie le SIREN comme `Siren`, puis appelle l'API pour voir si l'entreprise existe.

Les objets de règle sont ignorés quand la valeur est vide. Ajoutez `required` si le champ est obligatoire.

### EntrepriseExiste est optionnelle

`EntrepriseExiste` fait un appel HTTP à chaque validation. Votre formulaire dépend alors d'un service extérieur. Elle n'est donc jamais ajoutée pour vous. Utilisez-la seulement là où vous acceptez cela.

```php
$request->validate([
    'siren' => ['required', new EntrepriseExiste],
]);
```

Elle échoue en mode fermé. Quand l'API n'est pas disponible (erreur réseau, limite de débit, erreur de l'API), la valeur est refusée avec le message "n'a pas pu être vérifié car le service des entreprises est indisponible". Ce message est différent de "ne correspond à aucune entreprise connue", utilisé quand l'API dit que l'entreprise n'existe pas. Activez le cache pour réduire les appels.

## Activer le cache

Le cache est désactivé par défaut. Dans `config/api-gouv.php` :

```php
'cache' => [
    'enabled' => true,
    'store' => null,   // ou le nom d'un store, par exemple 'redis'
],
```

Seules les réponses réussies sont mises en cache. Voir [Erreurs, cache et limite de débit](errors-cache-rate-limit.md).

## Traductions

Les messages des règles existent en anglais et en français. Pour les changer, publiez les fichiers :

```bash
php artisan vendor:publish --tag=api-gouv-lang
```

Les fichiers sont copiés dans `lang/vendor/api-gouv`. Les clés sont `siren`, `siret`, `entreprise_existe` et `entreprise_indisponible`.
