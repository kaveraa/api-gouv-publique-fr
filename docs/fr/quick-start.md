# Démarrage rapide

## Installation

```bash
composer require kaveraa/api-gouv-publique-fr
```

Il faut PHP 8.3 ou plus récent. Laravel 12 ou 13 est optionnel. Symfony 7.4 ou 8 est optionnel. Il n'y a pas besoin de clé d'API.

## Premier appel avec Laravel

Le paquet s'enregistre tout seul. Utilisez la façade `ApiGouv` :

```php
use Kaveraa\ApiGouv\Laravel\ApiGouv;

$company = ApiGouv::entreprises()->parSiren('812487973');
echo $company->nomComplet;            // OCTO

$addresses = ApiGouv::adresse()->rechercher('8 bd du port amiens', 1);
echo $addresses[0]->label;            // 8 Boulevard du Port 80000 Amiens

$commune = ApiGouv::geo()->commune('80021');
echo $commune->nom;                   // Amiens
```

L'API Geo donne les communes, les départements, les régions et les EPCI. Voir [API Geo](geo.md).

## Premier appel avec Symfony

Installez aussi `symfony/http-client` et `nyholm/psr7`. Puis activez le bundle dans `config/bundles.php` :

```php
Kaveraa\ApiGouv\Symfony\ApiGouvBundle::class => ['all' => true],
```

Le fichier `config/packages/api_gouv.yaml` est optionnel. Chaque clé a une valeur par défaut, donc il peut être vide :

```yaml
api_gouv: ~
```

Injectez un client dans un service ou un contrôleur :

```php
public function __construct(private readonly EntreprisesApi $entreprises) {}   // Kaveraa\ApiGouv\Entreprises\EntreprisesApi

$name = $this->entreprises->parSiren('812487973')->nomComplet;                  // OCTO
```

Voir [Symfony](symfony.md).

## Premier appel en PHP simple

Installez un client PSR-18 et une fabrique PSR-17, par exemple :

```bash
composer require symfony/http-client nyholm/psr7
```

Puis :

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

## Ce que vous recevez

Vous recevez des objets typés. Toutes les propriétés sont en lecture seule.

- `Entreprise` : `siren`, `nomComplet`, `sigle`, `activitePrincipale`, `categorie`, `natureJuridique`, `etatAdministratif`, `dateCreation`, `trancheEffectif`, `nombreEtablissements`, `nombreEtablissementsOuverts`, `siege`, `dirigeants`, `etablissementsCorrespondants`.
- `Etablissement` : `siret`, `siren`, `estSiege`, `etatAdministratif`, `adresse`, `codePostal`, `commune`, `codeCommune`, `activitePrincipale`, `dateCreation`, `latitude`, `longitude`, `enseignes`.
- `Adresse` : `id`, `label`, `numero`, `rue`, `nom`, `codePostal`, `codeCommune`, `commune`, `contexte`, `type`, `score`, `coordonnees`.
- `Commune` : `code`, `nom`, `codesPostaux`, `population`, `codeDepartement`, `codeRegion`, `siren`, `codeEpci`, `centre`, `score`. L'API Geo donne aussi `Departement`, `Region` et `Epci`.

Les tableaux complets sont dans [Recherche d'entreprises](entreprises.md) et [Recherche d'adresses](adresse.md).

## Pour continuer

- [Recherche d'entreprises](entreprises.md)
- [Recherche d'adresses](adresse.md)
- [API Geo](geo.md)
- [Laravel](laravel.md)
- [Symfony](symfony.md)
- [Erreurs, cache et limite de débit](errors-cache-rate-limit.md)
