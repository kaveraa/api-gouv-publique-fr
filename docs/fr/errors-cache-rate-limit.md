# Erreurs, cache et limite de débit

## Exceptions

| Situation | Exception | Notes |
| --- | --- | --- |
| Erreur réseau (pas de réponse, délai dépassé) | `ApiException` | Le code est 0. L'erreur d'origine est l'exception précédente. |
| HTTP 429 (trop de requêtes) | `RateLimitException` | Le code est 429. Elle a `$retryAfter`. |
| HTTP 404, ou SIREN ou SIRET inconnu | `NotFoundException` | Le code est 404. |
| Autre statut HTTP 400 ou plus | `ApiException` | Le code est le statut HTTP. Le message contient le texte d'erreur de l'API. |
| La réponse n'est pas du JSON valide | `InvalidResponseException` | Le corps n'est pas du JSON, ou il manque une clé comme le SIREN. |
| Mauvaise entrée (texte vide, mauvaise taille de page, SIREN de mauvaise longueur) | `InvalidArgumentException` | Une exception PHP standard. Lancée avant tout appel. |

`RateLimitException`, `NotFoundException` et `InvalidResponseException` étendent toutes `ApiException`. Attrapez `ApiException` pour attraper tous les problèmes d'API.

```php
use Kaveraa\ApiGouv\Exceptions\ApiException;
use Kaveraa\ApiGouv\Exceptions\NotFoundException;

try {
    $company = $api->entreprises()->parSiren('812487973');
} catch (NotFoundException) {
    // cette entreprise n'existe pas
} catch (ApiException $e) {
    // tout autre problème d'API
    logger()->warning($e->getMessage());
}
```

Les exceptions sont dans l'espace de noms `Kaveraa\ApiGouv\Exceptions`.

## Limite de débit

Les API limitent le nombre d'appels. Quand vous dépassez la limite, l'API répond HTTP 429. Le paquet lance `RateLimitException`. Si l'API envoie un en-tête `Retry-After` avec un nombre de secondes, vous pouvez le lire :

```php
use Kaveraa\ApiGouv\Exceptions\RateLimitException;

try {
    $api->entreprises()->rechercher('octo');
} catch (RateLimitException $e) {
    $seconds = $e->retryAfter ?? 1;   // int ou null
    sleep($seconds);
}
```

### Nouvel essai dans Laravel

Le transport Laravel réessaie tout seul, et seulement pour HTTP 429. Les autres erreurs ne sont pas réessayées. Deux clés de config le règlent :

- `attempts` : nombre total d'essais (3 par défaut).
- `retry_delay_ms` : pause entre les essais (300 par défaut).

Si le dernier essai donne encore 429, `RateLimitException` est lancée.

### Nouvel essai en PHP simple

Le transport PHP simple ne réessaie pas. Attrapez `RateLimitException` et réessayez vous-même, ou utilisez un client PSR-18 qui sait réessayer.

## Cache

Le cache économise des appels et accélère votre code. Il est désactivé par défaut.

- Seules les réponses réussies sont mises en cache. Les erreurs ne sont jamais stockées.
- La clé est construite avec l'URL et les paramètres. L'ordre des paramètres n'a pas d'importance.
- La durée de cache est réglée par API : 3600 secondes pour les entreprises et 86400 secondes pour les adresses (défauts Laravel).
- Un résultat de recherche vide est mis en cache comme toute réponse réussie. Une entreprise créée récemment continue de renvoyer "introuvable" jusqu'à la fin de `cache_ttl`. Gardez `cache_ttl` court si vous cherchez de nouvelles entreprises, ou laissez le cache désactivé (par défaut).

Dans Laravel, mettez `cache.enabled` à `true`. Voir [Laravel](laravel.md). En PHP simple, donnez un `ResponseCache` à `Requester`. Voir [PHP simple](plain-php.md).
