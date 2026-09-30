# Recherche d'adresses

Cette partie utilise la Base Adresse Nationale (BAN). Depuis la fermeture de l'ancienne API d'adresses, la BAN est servie par la Géoplateforme. L'URL de base par défaut est `https://data.geopf.fr/geocodage`.

La classe cliente est `AdresseClient`. Elle implémente l'interface `AdresseApi`. Dans Laravel, on l'obtient avec `ApiGouv::adresse()`.

## Chercher une adresse

```php
$addresses = ApiGouv::adresse()->rechercher('8 bd du port amiens', 1);

echo $addresses[0]->label;                    // 8 Boulevard du Port 80000 Amiens
echo $addresses[0]->coordonnees->latitude;    // 49.897442
```

`rechercher(string $query, int $limit = 5)` renvoie une liste d'`Adresse`. La liste est vide quand rien ne correspond.

## Autocomplétion

```php
$suggestions = ApiGouv::adresse()->autocompleter('8 bd du po', 5);
```

`autocompleter(string $query, int $limit = 5)` fonctionne comme `rechercher`, mais il est fait pour un texte partiel que l'utilisateur est en train de taper.

## Géocodage inverse

```php
$address = ApiGouv::adresse()->geocoderInverse(49.897442, 2.290084);

echo $address?->commune;   // Amiens
```

`geocoderInverse(float $latitude, float $longitude)` renvoie l'`Adresse` la plus proche, ou `null` s'il n'y en a pas. La latitude doit être de -90 à 90 et la longitude de -180 à 180. Sinon la méthode lance `InvalidArgumentException`.

## Limites

- `limit` doit être de 1 à 50. Sinon `InvalidArgumentException` est lancée.
- Le texte cherché ne doit pas être vide.

## Champs

### Adresse

| Champ | Type | Sens |
| --- | --- | --- |
| `id` | string | Identifiant de l'adresse. |
| `label` | string | Adresse complète sur une ligne. |
| `numero` | ?string | Numéro dans la rue. |
| `rue` | ?string | Nom de la rue. |
| `nom` | ?string | Numéro et rue, ou nom du lieu. |
| `codePostal` | ?string | Code postal. |
| `codeCommune` | ?string | Code de la commune (INSEE). |
| `commune` | ?string | Nom de la commune. |
| `contexte` | ?string | Département, nom du département et région. |
| `type` | ?string | Genre de résultat, par exemple "housenumber" ou "street". |
| `score` | ?float | Score de correspondance de 0 à 1. |
| `coordonnees` | Coordonnees | La position. |

### Coordonnees

| Champ | Type | Sens |
| --- | --- | --- |
| `latitude` | float | Latitude. |
| `longitude` | float | Longitude. |

## Cas d'usage : formulaire d'adresse avec autocomplétion

Ajoutez une route que votre formulaire appelle pendant la saisie :

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

Activez le cache pour économiser des appels. Voir [Erreurs, cache et limite de débit](errors-cache-rate-limit.md).

## À propos de l'URL de base

L'ancien service d'adresses a déménagé vers la Géoplateforme. Ce paquet utilise la nouvelle URL par défaut. Si elle change encore, modifiez `adresse.base_url` dans la config Laravel, ou donnez une autre URL de base à `Requester` en PHP simple.
