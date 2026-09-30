# API Gouv Publique FR

<p align="center"><img src="https://raw.githubusercontent.com/kaveraa/api-gouv-publique-fr/main/art/banner.svg" alt="API Gouv Publique FR" width="100%"></p>

[![Tests](https://github.com/kaveraa/api-gouv-publique-fr/actions/workflows/tests.yml/badge.svg)](https://github.com/kaveraa/api-gouv-publique-fr/actions/workflows/tests.yml)
[![Packagist](https://img.shields.io/packagist/v/kaveraa/api-gouv-publique-fr.svg)](https://packagist.org/packages/kaveraa/api-gouv-publique-fr)
[![License](https://img.shields.io/github/license/kaveraa/api-gouv-publique-fr.svg)](https://github.com/kaveraa/api-gouv-publique-fr/blob/main/LICENSE)
[![Downloads](https://img.shields.io/packagist/dt/kaveraa/api-gouv-publique-fr.svg)](https://packagist.org/packages/kaveraa/api-gouv-publique-fr)
[![PHP](https://img.shields.io/packagist/dependency-v/kaveraa/api-gouv-publique-fr/php.svg)](https://packagist.org/packages/kaveraa/api-gouv-publique-fr)

**English** - [Français](https://github.com/kaveraa/api-gouv-publique-fr/blob/main/README.fr.md)

A typed PHP client for French public APIs. Version 1 covers company search ("Recherche d'entreprises") and address search (the BAN, served by the Geoplateforme). Version 0.2 adds the Geo API (communes, departements, regions, EPCI). It works in any PHP project. It has an optional bridge for Laravel.

This is an unofficial project. It is not affiliated with the French State.

## Requirements

- PHP 8.3 or newer
- Laravel 12 or 13 (optional)

## Install

```bash
composer require kaveraa/api-gouv-publique-fr
```

## Laravel example

```php
use Kaveraa\ApiGouv\Laravel\ApiGouv;

$company = ApiGouv::entreprises()->parSiren('812487973');
echo $company->nomComplet;            // OCTO
echo $company->siege->commune;        // BORDEAUX

$addresses = ApiGouv::adresse()->rechercher('8 bd du port amiens', 1);
echo $addresses[0]->label;            // 8 Boulevard du Port 80000 Amiens

$commune = ApiGouv::geo()->commune('80021');
echo $commune->nom;                   // Amiens
```

## Plain PHP example

This example uses Symfony HttpClient as the PSR-18 client and Nyholm as the PSR-17 factory.

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

- [Quick start](docs/en/quick-start.md)
- [Company search](docs/en/entreprises.md)
- [Address search](docs/en/adresse.md)
- [Geo API](docs/en/geo.md)
- [Laravel](docs/en/laravel.md)
- [Plain PHP and Symfony](docs/en/plain-php.md)
- [Errors, cache and rate limit](docs/en/errors-cache-rate-limit.md)
- [Testing](docs/en/testing.md)
- [FAQ](docs/en/faq.md)

## Features

- Typed objects (DTOs) for companies, establishments, managers, addresses, communes, departements, regions and EPCI.
- One exception class per problem, all with a common parent: `ApiException`.
- Optional response cache, off by default. It works with any PSR-16 cache.
- Rate limit retry in Laravel (HTTP 429).
- Laravel validation rules: `Siren`, `Siret` and `EntrepriseExiste`.
- Test fakes and factories for your own tests.

## Not included

The INSEE SIRENE API is not included.

## Unofficial notice

This package is not made by the French State and is not affiliated with it. "api.gouv.fr" and the API names belong to their owners. Please read the terms of use of each API.

## License

MIT. See [LICENSE](LICENSE).

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md). To report a security problem, see [SECURITY.md](SECURITY.md).
