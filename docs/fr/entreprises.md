# Recherche d'entreprises

Cette partie utilise l'API "Recherche d'entreprises" (`https://recherche-entreprises.api.gouv.fr`). La classe cliente est `EntreprisesClient`. Elle implémente l'interface `EntreprisesApi`. Dans Laravel, on l'obtient avec `ApiGouv::entreprises()`.

## Trouver une entreprise par SIREN

```php
$company = ApiGouv::entreprises()->parSiren('812487973');

echo $company->nomComplet;       // OCTO
echo $company->siege->commune;   // BORDEAUX
```

Les espaces sont retirés, donc `'812 487 973'` fonctionne aussi. Le SIREN doit avoir 9 chiffres, sinon la méthode lance `InvalidArgumentException` avant tout appel. Si aucune entreprise n'a ce SIREN, elle lance `NotFoundException`.

## Trouver un établissement par SIRET

```php
$etablissement = ApiGouv::entreprises()->parSiret('81248797300040');

echo $etablissement->adresse;
echo $etablissement->estSiege ? 'siège' : 'autre site';
```

Le SIRET doit avoir 14 chiffres. Un SIRET inconnu lance `NotFoundException`.

## Chercher avec un texte

```php
$result = ApiGouv::entreprises()->rechercher('octo technology');

echo $result->total;                       // nombre de résultats, toutes pages
echo count($result);                       // entreprises de cette page
foreach ($result as $company) {
    echo $company->siren.' '.$company->nomComplet.PHP_EOL;
}
```

## Chercher avec SearchQuery

`SearchQuery` ajoute la pagination et des filtres. Tous les paramètres sont nommés.

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

| Paramètre | Type | Défaut | Sens |
| --- | --- | --- | --- |
| `q` | string | obligatoire | Texte cherché. Il ne doit pas être vide. |
| `page` | int | 1 | Numéro de page. Il doit être 1 ou plus. |
| `perPage` | int | 10 | Taille de page. Elle doit être de 1 à 25. |
| `codePostal` | ?string | null | Filtre par code postal. |
| `departement` | ?string | null | Filtre par code de département. |
| `activitePrincipale` | ?string | null | Filtre par code d'activité principale (NAF). |
| `etatAdministratif` | ?string | null | Filtre par état. "A" veut dire actif. |

Une taille de page hors de 1 à 25 lance `InvalidArgumentException`. Cela évite une erreur HTTP 400 de l'API.

## Champs

### Entreprise

| Champ | Type | Sens |
| --- | --- | --- |
| `siren` | string | Le numéro à 9 chiffres. |
| `nomComplet` | string | Nom complet. |
| `sigle` | ?string | Nom court. |
| `activitePrincipale` | ?string | Code d'activité principale (NAF). |
| `categorie` | ?string | Catégorie de taille, par exemple "PME". |
| `natureJuridique` | ?string | Code de la forme juridique. |
| `etatAdministratif` | ?string | État. "A" veut dire actif. |
| `dateCreation` | ?DateTimeImmutable | Date de création. |
| `trancheEffectif` | ?string | Code de la tranche d'effectif. |
| `nombreEtablissements` | ?int | Nombre d'établissements, ou `null` s'il est inconnu. |
| `nombreEtablissementsOuverts` | ?int | Nombre d'établissements ouverts, ou `null` s'il est inconnu. |
| `siege` | ?Etablissement | Le siège. |
| `dirigeants` | liste de Dirigeant | Les dirigeants. |
| `etablissementsCorrespondants` | liste d'Etablissement | Les établissements qui correspondent à la recherche. |

### Etablissement

| Champ | Type | Sens |
| --- | --- | --- |
| `siret` | string | Le numéro à 14 chiffres. |
| `siren` | string | Les 9 premiers chiffres du SIRET. |
| `estSiege` | bool | Vrai pour le siège. |
| `etatAdministratif` | ?string | État. |
| `adresse` | ?string | Adresse complète sur une ligne. |
| `codePostal` | ?string | Code postal. |
| `commune` | ?string | Nom de la commune. |
| `codeCommune` | ?string | Code de la commune (INSEE). |
| `activitePrincipale` | ?string | Code d'activité principale. |
| `dateCreation` | ?DateTimeImmutable | Date de création. |
| `coordonnees` | ?Coordonnees | La position (`latitude`, `longitude`), ou `null`. |
| `enseignes` | liste de string | Enseignes. |

### Dirigeant

| Champ | Type | Sens |
| --- | --- | --- |
| `type` | string | Le genre de dirigeant. Vaut "inconnu" quand l'API ne le dit pas. |
| `qualite` | ?string | Rôle. |
| `nom` | ?string | Nom de famille. |
| `prenoms` | ?string | Prénoms. |
| `denomination` | ?string | Nom de la société, quand le dirigeant est une société. |
| `siren` | ?string | SIREN, quand le dirigeant est une société. |
| `anneeDeNaissance` | ?string | Année de naissance. |

### SearchResult

| Champ | Type | Sens |
| --- | --- | --- |
| `results` | liste d'Entreprise | Les entreprises de cette page. |
| `total` | int | Nombre total de résultats. |
| `page` | int | Page actuelle. |
| `perPage` | int | Taille de page. |
| `totalPages` | int | Nombre de pages. |

`SearchResult` est itérable et comptable : `foreach ($result as $company)` et `count($result)` portent sur les entreprises de la page. `total` compte les résultats de toutes les pages.

## Cas d'usage : vérifier une entreprise à l'inscription

Un utilisateur donne un SIREN dans votre formulaire d'inscription. Vous voulez vérifier que l'entreprise existe et qu'elle est active, et remplir son nom. Validez d'abord le numéro, pour n'appeler l'API qu'avec un SIREN bien formé.

```php
use Kaveraa\ApiGouv\Exceptions\ApiException;
use Kaveraa\ApiGouv\Exceptions\NotFoundException;
use Kaveraa\ApiGouv\Laravel\ApiGouv;
use Kaveraa\ApiGouv\Laravel\Rules\Siren;

$request->validate(['siren' => ['required', new Siren]]);

try {
    $company = ApiGouv::entreprises()->parSiren($request->input('siren'));
} catch (NotFoundException) {
    return back()->withErrors(['siren' => "Cette entreprise n'existe pas."]);
} catch (ApiException) {
    return back()->withErrors(['siren' => 'Nous ne pouvons pas vérifier ce numéro maintenant. Réessayez.']);
}

if ($company->etatAdministratif !== 'A') {
    return back()->withErrors(['siren' => 'Cette entreprise est fermée.']);
}

$name = $company->nomComplet;
```

Dans Laravel, vous pouvez aussi utiliser les règles `Siren` et `EntrepriseExiste`. Voir [Laravel](laravel.md).
