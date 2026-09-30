# Company search

This part uses the "Recherche d'entreprises" API (`https://recherche-entreprises.api.gouv.fr`). The client class is `EntreprisesClient`. It implements the `EntreprisesApi` interface. In Laravel, get it with `ApiGouv::entreprises()`.

## Find a company by SIREN

```php
$company = ApiGouv::entreprises()->parSiren('812487973');

echo $company->nomComplet;       // OCTO
echo $company->siege->commune;   // BORDEAUX
```

Spaces are removed, so `'812 487 973'` works too. The SIREN must have 9 digits, or the method throws `InvalidArgumentException` before any call. If no company has this SIREN, it throws `NotFoundException`.

## Find an establishment by SIRET

```php
$etablissement = ApiGouv::entreprises()->parSiret('81248797300040');

echo $etablissement->adresse;
echo $etablissement->estSiege ? 'head office' : 'other site';
```

The SIRET must have 14 digits. An unknown SIRET throws `NotFoundException`.

## Search with a string

```php
$result = ApiGouv::entreprises()->rechercher('octo technology');

echo $result->total;                       // number of matches
foreach ($result->results as $company) {
    echo $company->siren.' '.$company->nomComplet.PHP_EOL;
}
```

## Search with SearchQuery

`SearchQuery` adds paging and filters. All parameters are named.

```php
use Kaveraa\ApiGouv\Entreprises\SearchQuery;

$result = ApiGouv::entreprises()->rechercher(new SearchQuery(
    q: 'boulangerie',
    page: 2,
    perPage: 20,
    codePostal: '33300',
    departement: '33',
    activitePrincipale: '10.71C',
    etatAdministratif: 'A',
));
```

| Parameter | Type | Default | Meaning |
| --- | --- | --- | --- |
| `q` | string | required | Search text. It must not be empty. |
| `page` | int | 1 | Page number. It must be 1 or more. |
| `perPage` | int | 10 | Page size. It must be from 1 to 25. |
| `codePostal` | ?string | null | Filter by postal code. |
| `departement` | ?string | null | Filter by department code. |
| `activitePrincipale` | ?string | null | Filter by main activity code (NAF). |
| `etatAdministratif` | ?string | null | Filter by status. "A" means active. |

A page size outside 1 to 25 throws `InvalidArgumentException`. This avoids an HTTP 400 from the API.

## Fields

### Entreprise

| Field | Type | Meaning |
| --- | --- | --- |
| `siren` | string | The 9 digit number. |
| `nomComplet` | string | Full name. |
| `sigle` | ?string | Short name. |
| `activitePrincipale` | ?string | Main activity code (NAF). |
| `categorie` | ?string | Size category, for example "PME". |
| `natureJuridique` | ?string | Legal form code. |
| `etatAdministratif` | ?string | Status. "A" means active. |
| `dateCreation` | ?DateTimeImmutable | Creation date. |
| `trancheEffectif` | ?string | Staff size code. |
| `nombreEtablissements` | int | Number of establishments. |
| `nombreEtablissementsOuverts` | int | Number of open establishments. |
| `siege` | ?Etablissement | The head office. |
| `dirigeants` | list of Dirigeant | The managers. |
| `etablissementsCorrespondants` | list of Etablissement | Establishments that matched the search. |

### Etablissement

| Field | Type | Meaning |
| --- | --- | --- |
| `siret` | string | The 14 digit number. |
| `siren` | string | The first 9 digits of the SIRET. |
| `estSiege` | bool | True for the head office. |
| `etatAdministratif` | ?string | Status. |
| `adresse` | ?string | Full address on one line. |
| `codePostal` | ?string | Postal code. |
| `commune` | ?string | Town name. |
| `codeCommune` | ?string | Town code (INSEE). |
| `activitePrincipale` | ?string | Main activity code. |
| `dateCreation` | ?DateTimeImmutable | Creation date. |
| `latitude` | ?float | Latitude. |
| `longitude` | ?float | Longitude. |
| `enseignes` | list of string | Trade names. |

### Dirigeant

| Field | Type | Meaning |
| --- | --- | --- |
| `type` | string | The kind of manager. It is "inconnu" when the API does not say. |
| `qualite` | ?string | Role. |
| `nom` | ?string | Last name. |
| `prenoms` | ?string | First names. |
| `denomination` | ?string | Company name, when the manager is a company. |
| `siren` | ?string | SIREN, when the manager is a company. |
| `anneeDeNaissance` | ?string | Year of birth. |

### SearchResult

| Field | Type | Meaning |
| --- | --- | --- |
| `results` | list of Entreprise | The companies on this page. |
| `total` | int | Total number of matches. |
| `page` | int | Current page. |
| `perPage` | int | Page size. |
| `totalPages` | int | Number of pages. |

## Use case: check a company at sign-up

A user gives a SIREN in your sign-up form. You want to check that the company exists and is active, and fill in the name. Validate the number first, so the API is called only with a well formed SIREN.

```php
use Kaveraa\ApiGouv\Exceptions\ApiException;
use Kaveraa\ApiGouv\Exceptions\NotFoundException;
use Kaveraa\ApiGouv\Laravel\ApiGouv;
use Kaveraa\ApiGouv\Laravel\Rules\Siren;

$request->validate(['siren' => ['required', new Siren]]);

try {
    $company = ApiGouv::entreprises()->parSiren($request->input('siren'));
} catch (NotFoundException) {
    return back()->withErrors(['siren' => 'This company does not exist.']);
} catch (ApiException) {
    return back()->withErrors(['siren' => 'We cannot check this number now. Please try again.']);
}

if ($company->etatAdministratif !== 'A') {
    return back()->withErrors(['siren' => 'This company is closed.']);
}

$name = $company->nomComplet;
```

In Laravel you can also use the `Siren` and `EntrepriseExiste` rules. See [Laravel](laravel.md).
