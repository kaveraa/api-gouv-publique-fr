# Geo API

The Geo API gives the official list of French communes, departements, regions and EPCI (groups of communes). It is open and needs no key. The default base URL is `https://geo.api.gouv.fr`.

The client class is `GeoClient`. It implements the `GeoApi` interface. In Laravel, get it with `ApiGouv::geo()`. Version 0.2.0 covers communes, departements, regions and EPCI. It does not cover contours (the shapes of the areas).

## Communes

```php
use Kaveraa\ApiGouv\Laravel\ApiGouv;

$commune = ApiGouv::geo()->commune('80021');
echo $commune->nom;                         // Amiens
echo implode(', ', $commune->codesPostaux); // 80000, 80080, 80090
echo $commune->centre?->latitude;           // 49.8987

$communes = ApiGouv::geo()->communesParCodePostal('80300');   // 35 communes share this postal code
$matches = ApiGouv::geo()->rechercherCommunes('amiens', 5);   // best matches first, large communes boosted
$here = ApiGouv::geo()->communeParCoordonnees(49.897442, 2.290084); // Amiens, or null at sea
```

- `commune(string $codeInsee)` returns one `Commune`.
- `communesParCodePostal(string $codePostal)` returns a list of `Commune`.
- `rechercherCommunes(string $nom, int $limit = 10)` searches by name. The name must not be empty.
- `communeParCoordonnees(float $latitude, float $longitude)` returns the commune at this point, or `null`.

### Commune

| Field | Type | Meaning |
| --- | --- | --- |
| `code` | string | Commune code (INSEE), for example "80021" or "2A004". |
| `nom` | string | Name of the commune. |
| `codesPostaux` | list of string | Postal codes of the commune. |
| `population` | ?int | Number of inhabitants. |
| `codeDepartement` | ?string | Code of the departement. |
| `codeRegion` | ?string | Code of the region. |
| `siren` | ?string | SIREN of the commune. |
| `codeEpci` | ?string | Code of the EPCI. |
| `centre` | ?Coordonnees | The centre point, with `latitude` and `longitude`. |
| `score` | ?float | Match score of a search by name. |

`centre` is a `Kaveraa\ApiGouv\Adresse\Coordonnees`, the same class the address client uses (with `latitude` and `longitude`).

`score` is only set by `rechercherCommunes`. It is `null` for all other methods.

## Departements and regions

```php
$departements = ApiGouv::geo()->departements();
$somme = ApiGouv::geo()->departement('80');
$communes = ApiGouv::geo()->communesDuDepartement('80');

$regions = ApiGouv::geo()->regions();
$hautsDeFrance = ApiGouv::geo()->region('32');
$departements = ApiGouv::geo()->departementsDeLaRegion('32');

echo $somme->nom;             // Somme
echo $hautsDeFrance->nom;     // Hauts-de-France
```

### Departement

| Field | Type | Meaning |
| --- | --- | --- |
| `code` | string | Code, for example "80", "2A" or "971". |
| `nom` | string | Name. |
| `codeRegion` | ?string | Code of the region. |

### Region

| Field | Type | Meaning |
| --- | --- | --- |
| `code` | string | Code, for example "32". |
| `nom` | string | Name. |

## EPCI

```php
$epci = ApiGouv::geo()->epci('248000531');
echo $epci->nom;                     // CA Amiens Métropole

$epcis = ApiGouv::geo()->epcisDuDepartement('80');
```

### Epci

| Field | Type | Meaning |
| --- | --- | --- |
| `code` | string | Code of 9 digits (the SIREN of the EPCI). |
| `nom` | string | Name. |
| `population` | ?int | Number of inhabitants. |
| `codesDepartements` | list of string | Codes of the departements it covers. |
| `codesRegions` | list of string | Codes of the regions it covers. |

## Input checks

The client checks the input before it calls the API. A bad value throws `InvalidArgumentException`.

| Input | Accepted format |
| --- | --- |
| Commune code (INSEE) | 5 characters: 5 digits, or Corsica like `2A004`. |
| Postal code | 5 digits. |
| Departement code | 2 digits like `01`, Corsica `2A` or `2B`, or overseas like `971`. |
| Region code | 2 digits, like `32` or `01`. |
| EPCI code | 9 digits. |
| Name | Not empty. |
| `limit` | From 1 to 50. |
| Coordinates | Latitude from -90 to 90, longitude from -180 to 180. |

Spaces are removed and `2a` becomes `2A`. So a pasted code like " 2a004 " works.

## When nothing is found

- A detail method throws `NotFoundException`. These are `commune`, `departement`, `region`, `epci`, `communesDuDepartement` and `departementsDeLaRegion`.
- A list method returns an empty list. These are `communesParCodePostal`, `rechercherCommunes` and `epcisDuDepartement`.
- `communeParCoordonnees` returns `null`, for example for a point at sea.

```php
use Kaveraa\ApiGouv\Exceptions\NotFoundException;

try {
    $commune = ApiGouv::geo()->commune('99999');
} catch (NotFoundException) {
    // no commune has this code
}
```

## Cache

The Geo API cache time is 86400 seconds (one day) by default in Laravel. In plain PHP, `Requester` uses the `$ttl` you pass (3600 by default). In Laravel, change it with `geo.cache_ttl`. The lists of departements and regions rarely change, so a long time is fine. An empty result is cached like any successful answer. The cache is off by default. See [Errors, cache and rate limit](errors-cache-rate-limit.md).

## Use cases

### Postal code to a commune select

Many postal codes cover several communes. Add a route that your form calls after the user types the postal code:

```php
use Illuminate\Http\Request;
use Kaveraa\ApiGouv\Laravel\ApiGouv;

Route::get('/communes', function (Request $request) {
    $codePostal = (string) $request->query('cp');

    return collect(ApiGouv::geo()->communesParCodePostal($codePostal))
        ->map(fn ($c) => ['code' => $c->code, 'nom' => $c->nom])
        ->values();
});
```

Validate the postal code first, or catch `InvalidArgumentException`, because a bad code throws.

### Lists of departements and regions for a settings page

```php
$options = [
    'regions' => collect(ApiGouv::geo()->regions())->pluck('nom', 'code'),
    'departements' => collect(ApiGouv::geo()->departements())->pluck('nom', 'code'),
];
```

Turn the cache on so that these two calls do not hit the API each time.

## Plain PHP

Build the client like the other clients in [Plain PHP](plain-php.md):

```php
use Kaveraa\ApiGouv\Geo\GeoClient;
use Kaveraa\ApiGouv\Http\Requester;

$geo = new GeoClient(new Requester($transport, 'https://geo.api.gouv.fr'));

echo $geo->commune('80021')->nom;   // Amiens
```
