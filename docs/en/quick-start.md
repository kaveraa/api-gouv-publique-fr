# Quick start

## Install

```bash
composer require kaveraa/api-gouv-publique-fr
```

You need PHP 8.3 or newer. Laravel 12 or 13 is optional. You do not need an API key.

## First call in Laravel

The package registers itself. Use the `ApiGouv` facade:

```php
use Kaveraa\ApiGouv\Laravel\ApiGouv;

$company = ApiGouv::entreprises()->parSiren('812487973');
echo $company->nomComplet;            // OCTO

$addresses = ApiGouv::adresse()->rechercher('8 bd du port amiens', 1);
echo $addresses[0]->label;            // 8 Boulevard du Port 80000 Amiens
```

## First call in plain PHP

Install a PSR-18 client and a PSR-17 factory, for example:

```bash
composer require symfony/http-client nyholm/psr7
```

Then:

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

## What you get back

You get typed objects. All properties are read-only.

- `Entreprise`: `siren`, `nomComplet`, `sigle`, `activitePrincipale`, `categorie`, `natureJuridique`, `etatAdministratif`, `dateCreation`, `trancheEffectif`, `nombreEtablissements`, `nombreEtablissementsOuverts`, `siege`, `dirigeants`, `etablissementsCorrespondants`.
- `Etablissement`: `siret`, `siren`, `estSiege`, `etatAdministratif`, `adresse`, `codePostal`, `commune`, `codeCommune`, `activitePrincipale`, `dateCreation`, `latitude`, `longitude`, `enseignes`.
- `Adresse`: `id`, `label`, `numero`, `rue`, `nom`, `codePostal`, `codeCommune`, `commune`, `contexte`, `type`, `score`, `coordonnees`.

See the full tables in [Company search](entreprises.md) and [Address search](adresse.md).

## Next steps

- [Company search](entreprises.md)
- [Address search](adresse.md)
- [Laravel](laravel.md)
- [Errors, cache and rate limit](errors-cache-rate-limit.md)
