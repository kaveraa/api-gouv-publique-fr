# API Geo

L'API Geo donne la liste officielle des communes, des départements, des régions et des EPCI (groupements de communes) de France. Elle est ouverte et n'a pas besoin de clé. L'URL de base par défaut est `https://geo.api.gouv.fr`.

La classe cliente est `GeoClient`. Elle implémente l'interface `GeoApi`. Dans Laravel, on l'obtient avec `ApiGouv::geo()`. La version 0.2.0 couvre les communes, les départements, les régions et les EPCI. Elle ne couvre pas les contours (les formes des zones).

## Communes

```php
use Kaveraa\ApiGouv\Laravel\ApiGouv;

$commune = ApiGouv::geo()->commune('80021');
echo $commune->nom;                         // Amiens
echo implode(', ', $commune->codesPostaux); // 80000, 80080, 80090
echo $commune->centre?->latitude;           // 49.8987

$communes = ApiGouv::geo()->communesParCodePostal('80300');   // 35 communes partagent ce code postal
$matches = ApiGouv::geo()->rechercherCommunes('amiens', 5);   // meilleurs résultats d'abord, grandes communes favorisées
$here = ApiGouv::geo()->communeParCoordonnees(49.897442, 2.290084); // Amiens, ou null en mer
```

- `commune(string $codeInsee)` renvoie une `Commune`.
- `communesParCodePostal(string $codePostal)` renvoie une liste de `Commune`.
- `rechercherCommunes(string $nom, int $limit = 10)` cherche par nom. Le nom ne doit pas être vide.
- `communeParCoordonnees(float $latitude, float $longitude)` renvoie la commune à ce point, ou `null`.

### Commune

| Champ | Type | Sens |
| --- | --- | --- |
| `code` | string | Code de la commune (INSEE), par exemple "80021" ou "2A004". |
| `nom` | string | Nom de la commune. |
| `codesPostaux` | liste de string | Codes postaux de la commune. |
| `population` | ?int | Nombre d'habitants. |
| `codeDepartement` | ?string | Code du département. |
| `codeRegion` | ?string | Code de la région. |
| `siren` | ?string | SIREN de la commune. |
| `codeEpci` | ?string | Code de l'EPCI. |
| `centre` | ?Coordonnees | Le point central, avec `latitude` et `longitude`. |
| `score` | ?float | Score de correspondance d'une recherche par nom. |

`centre` est un `Kaveraa\ApiGouv\Coordonnees`, la même classe que celle du client d'adresses et d'`Etablissement` (avec `latitude` et `longitude`).

`score` est rempli seulement par `rechercherCommunes`. Il vaut `null` pour toutes les autres méthodes.

## Départements et régions

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

| Champ | Type | Sens |
| --- | --- | --- |
| `code` | string | Code, par exemple "80", "2A" ou "971". |
| `nom` | string | Nom. |
| `codeRegion` | ?string | Code de la région. |

### Region

| Champ | Type | Sens |
| --- | --- | --- |
| `code` | string | Code, par exemple "32". |
| `nom` | string | Nom. |

## EPCI

```php
$epci = ApiGouv::geo()->epci('248000531');
echo $epci->nom;                     // CA Amiens Métropole

$epcis = ApiGouv::geo()->epcisDuDepartement('80');
```

### Epci

| Champ | Type | Sens |
| --- | --- | --- |
| `code` | string | Code de 9 chiffres (le SIREN de l'EPCI). |
| `nom` | string | Nom. |
| `population` | ?int | Nombre d'habitants. |
| `codesDepartements` | liste de string | Codes des départements couverts. |
| `codesRegions` | liste de string | Codes des régions couvertes. |

## Vérification des entrées

Le client vérifie les valeurs avant d'appeler l'API. Une mauvaise valeur lance `InvalidArgumentException`.

| Entrée | Format accepté |
| --- | --- |
| Code de commune (INSEE) | 5 caractères : 5 chiffres, ou la Corse comme `2A004`. |
| Code postal | 5 chiffres. |
| Code de département | 2 chiffres comme `01`, la Corse `2A` ou `2B`, ou l'outre-mer comme `971`. |
| Code de région | 2 chiffres, comme `32` ou `01`. |
| Code d'EPCI | 9 chiffres. |
| Nom | Non vide. |
| `limit` | De 1 à 50. |
| Coordonnées | Latitude de -90 à 90, longitude de -180 à 180. |

Les espaces sont retirés et `2a` devient `2A`. Un code collé comme " 2a004 " fonctionne donc.

## Quand rien n'est trouvé

- Une méthode de détail lance `NotFoundException`. Ce sont `commune`, `departement`, `region`, `epci`, `communesDuDepartement`, `departementsDeLaRegion` et `epcisDuDepartement`. Un département connu sans EPCI donne une liste vide.
- Une méthode de liste renvoie une liste vide. Ce sont `communesParCodePostal` et `rechercherCommunes`.
- `communeParCoordonnees` renvoie `null`, par exemple pour un point en mer.

```php
use Kaveraa\ApiGouv\Exceptions\NotFoundException;

try {
    $commune = ApiGouv::geo()->commune('99999');
} catch (NotFoundException) {
    // aucune commune n'a ce code
}
```

## Cache

La durée de cache de l'API Geo est de 86400 secondes (un jour) par défaut dans Laravel. En PHP simple, `Requester` utilise le `$ttl` que vous donnez (3600 par défaut). Dans Laravel, changez-la avec `geo.cache_ttl`. Les listes de départements et de régions changent rarement, une longue durée convient donc. Un résultat vide est mis en cache comme toute réponse réussie. Le cache est désactivé par défaut. Voir [Erreurs, cache et limite de débit](errors-cache-rate-limit.md).

## Cas d'usage

### Du code postal à une liste de communes

Beaucoup de codes postaux couvrent plusieurs communes. Ajoutez une route que votre formulaire appelle après la saisie du code postal :

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

Validez d'abord le code postal, ou attrapez `InvalidArgumentException`, car un mauvais code lance une exception.

### Listes de départements et de régions pour une page de réglages

```php
$options = [
    'regions' => collect(ApiGouv::geo()->regions())->pluck('nom', 'code'),
    'departements' => collect(ApiGouv::geo()->departements())->pluck('nom', 'code'),
];
```

Activez le cache pour que ces deux appels ne touchent pas l'API à chaque fois.

## PHP simple

Construisez le client comme les autres clients dans [PHP simple](plain-php.md) :

```php
use Kaveraa\ApiGouv\Geo\GeoClient;
use Kaveraa\ApiGouv\Http\Requester;

$geo = new GeoClient(new Requester($transport, 'https://geo.api.gouv.fr'));

echo $geo->commune('80021')->nom;   // Amiens
```
