# Address search

This part uses the French address database (BAN). Since the old address API was closed, the BAN is served by the Geoplateforme. The default base URL is `https://data.geopf.fr/geocodage`.

The client class is `AdresseClient`. It implements the `AdresseApi` interface. In Laravel, get it with `ApiGouv::adresse()`.

## Search an address

```php
$addresses = ApiGouv::adresse()->rechercher('8 bd du port amiens', 1);

echo $addresses[0]->label;                    // 8 Boulevard du Port 80000 Amiens
echo $addresses[0]->coordonnees->latitude;    // 49.897442
```

`rechercher(string $query, int $limit = 5)` returns a list of `Adresse`. The list is empty when nothing matches.

## Autocomplete

```php
$suggestions = ApiGouv::adresse()->autocompleter('8 bd du po', 5);
```

`autocompleter(string $query, int $limit = 5)` works like `rechercher`, but it is made for partial text that a user is still typing.

## Reverse geocoding

```php
$address = ApiGouv::adresse()->geocoderInverse(49.897442, 2.290084);

echo $address?->commune;   // Amiens
```

`geocoderInverse(float $latitude, float $longitude)` returns the nearest `Adresse`, or `null` when there is none. The latitude must be from -90 to 90 and the longitude from -180 to 180. Otherwise it throws `InvalidArgumentException`.

## Limits

- `limit` must be from 1 to 50. Otherwise `InvalidArgumentException` is thrown.
- The search text must not be empty.

## Fields

### Adresse

| Field | Type | Meaning |
| --- | --- | --- |
| `id` | string | Identifier of the address. |
| `label` | string | Full address on one line. |
| `numero` | ?string | House number. |
| `rue` | ?string | Street name. |
| `nom` | ?string | Number and street, or the name of the place. |
| `codePostal` | ?string | Postal code. |
| `codeCommune` | ?string | Town code (INSEE). |
| `commune` | ?string | Town name. |
| `contexte` | ?string | Department, county and region. |
| `type` | ?string | Kind of result, for example "housenumber" or "street". |
| `score` | ?float | Match score from 0 to 1. |
| `coordonnees` | Coordonnees | The position. |

### Coordonnees

| Field | Type | Meaning |
| --- | --- | --- |
| `latitude` | float | Latitude. |
| `longitude` | float | Longitude. |

## Use case: address form with autocomplete

Add a route that your form calls while the user types:

```php
use Illuminate\Http\Request;
use Kaveraa\ApiGouv\Laravel\ApiGouv;

Route::get('/addresses', function (Request $request) {
    $text = trim((string) $request->query('q'));

    if (strlen($text) < 3) {
        return [];
    }

    return collect(ApiGouv::adresse()->autocompleter($text, 5))
        ->map(fn ($a) => [
            'label' => $a->label,
            'postcode' => $a->codePostal,
            'city' => $a->commune,
            'lat' => $a->coordonnees->latitude,
            'lon' => $a->coordonnees->longitude,
        ]);
});
```

Turn on the cache to save calls. See [Errors, cache and rate limit](errors-cache-rate-limit.md).

## About the base URL

The old address service moved to the Geoplateforme. This package uses the new URL by default. If it moves again, change `adresse.base_url` in the Laravel config, or pass another base URL to `Requester` in plain PHP.
