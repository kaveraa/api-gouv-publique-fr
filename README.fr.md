# api-gouv-publique-fr

Lire en anglais : [README.md](README.md)

[![Dernière version](https://img.shields.io/packagist/v/kaveraa/api-gouv-publique-fr)](https://packagist.org/packages/kaveraa/api-gouv-publique-fr)
[![Version de PHP](https://img.shields.io/packagist/php-v/kaveraa/api-gouv-publique-fr)](https://packagist.org/packages/kaveraa/api-gouv-publique-fr)
[![Tests](https://github.com/kaveraa/api-gouv-publique-fr/actions/workflows/tests.yml/badge.svg)](https://github.com/kaveraa/api-gouv-publique-fr/actions/workflows/tests.yml)
[![Licence](https://img.shields.io/packagist/l/kaveraa/api-gouv-publique-fr)](LICENSE)

Un client PHP typé pour les API publiques françaises. La version 1 couvre la recherche d'entreprises et la recherche d'adresses (la BAN, servie par la Géoplateforme). Il fonctionne dans tout projet PHP. Il a un pont optionnel pour Laravel.

Ce projet est non officiel. Il n'est pas affilié à l'État français.

## Prérequis

- PHP 8.3 ou plus récent
- Laravel 11, 12 ou 13 (optionnel)

## Installation

```bash
composer require kaveraa/api-gouv-publique-fr
```

## Exemple avec Laravel

```php
use Kaveraa\ApiGouv\Laravel\ApiGouv;

$company = ApiGouv::entreprises()->parSiren('812487973');
echo $company->nomComplet;            // OCTO
echo $company->siege->commune;        // BORDEAUX

$addresses = ApiGouv::adresse()->rechercher('8 bd du port amiens', 1);
echo $addresses[0]->label;            // 8 Boulevard du Port 80000 Amiens
```

## Exemple en PHP simple

Cet exemple utilise Symfony HttpClient comme client PSR-18 et Nyholm comme fabrique PSR-17.

```php
use Kaveraa\ApiGouv\Entreprises\EntreprisesClient;
use Kaveraa\ApiGouv\Http\Psr18Transport;
use Kaveraa\ApiGouv\Http\Requester;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\HttpClient\Psr18Client;

$transport = new Psr18Transport(new Psr18Client(), new Psr17Factory());
$client = new EntreprisesClient(new Requester($transport, 'https://recherche-entreprises.api.gouv.fr'));

echo $client->parSiren('812487973')->nomComplet;
```

## Guides

- [Démarrage rapide](docs/fr/quick-start.md)
- [Recherche d'entreprises](docs/fr/entreprises.md)
- [Recherche d'adresses](docs/fr/adresse.md)
- [Laravel](docs/fr/laravel.md)
- [PHP simple et Symfony](docs/fr/plain-php.md)
- [Erreurs, cache et limite de débit](docs/fr/errors-cache-rate-limit.md)
- [Tests](docs/fr/testing.md)
- [FAQ](docs/fr/faq.md)

## Fonctionnalités

- Des objets typés (DTO) pour les entreprises, les établissements, les dirigeants et les adresses.
- Une classe d'exception par problème, avec un parent commun : `ApiException`.
- Un cache de réponses optionnel, désactivé par défaut. Il fonctionne avec tout cache PSR-16.
- Une nouvelle tentative en cas de limite de débit dans Laravel (HTTP 429).
- Des règles de validation Laravel : `Siren`, `Siret` et `EntrepriseExiste`.
- Des faux et des fabriques pour vos propres tests.

## Non inclus dans la v1

L'API Geo et l'API INSEE SIRENE ne sont pas incluses.

## Avis : projet non officiel

Ce paquet n'est pas créé par l'État français et n'est pas affilié à lui. "api.gouv.fr" et les noms des API appartiennent à leurs propriétaires. Merci de lire les conditions d'utilisation de chaque API.

## Licence

MIT. Voir [LICENSE](LICENSE).

## Contribuer

Voir [CONTRIBUTING.md](CONTRIBUTING.md) (en anglais). Pour signaler un problème de sécurité, voir [SECURITY.md](SECURITY.md).
