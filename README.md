<p align="center">
  <img src="art/banner.svg" alt="api-gouv-publique-fr - Typed PHP client for French public APIs" width="100%">
</p>

# api-gouv-publique-fr

Read this in French: [README.fr.md](README.fr.md)

[![Latest version](https://img.shields.io/packagist/v/kaveraa/api-gouv-publique-fr)](https://packagist.org/packages/kaveraa/api-gouv-publique-fr)
[![PHP version](https://img.shields.io/packagist/php-v/kaveraa/api-gouv-publique-fr)](https://packagist.org/packages/kaveraa/api-gouv-publique-fr)
[![Tests](https://github.com/kaveraa/api-gouv-publique-fr/actions/workflows/tests.yml/badge.svg)](https://github.com/kaveraa/api-gouv-publique-fr/actions/workflows/tests.yml)
[![License](https://img.shields.io/packagist/l/kaveraa/api-gouv-publique-fr)](LICENSE)

A typed PHP client for French public APIs. Version 1 covers company search ("Recherche d'entreprises") and address search (the BAN, served by the Geoplateforme). It works in any PHP project. It has an optional bridge for Laravel.

This is an unofficial project. It is not affiliated with the French State.

## Requirements

- PHP 8.3 or newer
- Laravel 11, 12 or 13 (optional)

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
- [Laravel](docs/en/laravel.md)
- [Plain PHP and Symfony](docs/en/plain-php.md)
- [Errors, cache and rate limit](docs/en/errors-cache-rate-limit.md)
- [Testing](docs/en/testing.md)
- [FAQ](docs/en/faq.md)

## Features

- Typed objects (DTOs) for companies, establishments, managers and addresses.
- One exception class per problem, all with a common parent: `ApiException`.
- Optional response cache, off by default. It works with any PSR-16 cache.
- Rate limit retry in Laravel (HTTP 429).
- Laravel validation rules: `Siren`, `Siret` and `EntrepriseExiste`.
- Test fakes and factories for your own tests.

## Not included in v1

The Geo API and the INSEE SIRENE API are not included.

## Unofficial notice

This package is not made by the French State and is not affiliated with it. "api.gouv.fr" and the API names belong to their owners. Please read the terms of use of each API.

## License

MIT. See [LICENSE](LICENSE).

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md). To report a security problem, see [SECURITY.md](SECURITY.md).
