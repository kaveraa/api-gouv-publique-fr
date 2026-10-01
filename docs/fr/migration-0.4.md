# Migrer de 0.3 vers 0.4

La version 0.4.0 fait les changements nécessaires avant la 1.0. Chacun est listé avec ce qu'il faut faire. La version 1.0.0 est le même code, c'est donc la seule migration avant la 1.0.

## 1. `Coordonnees` passe dans l'espace de noms racine

```php
// Avant
use Kaveraa\ApiGouv\Adresse\Coordonnees;
// Après
use Kaveraa\ApiGouv\Coordonnees;
```

## 2. `Etablissement` a `coordonnees` au lieu de `latitude` et `longitude`

```php
// Avant
$lat = $etablissement->latitude;
// Après
$lat = $etablissement->coordonnees?->latitude;

// Dans les tests, avant
Factories::etablissement(['latitude' => 48.86, 'longitude' => 2.34]);
// Après
Factories::etablissement(['coordonnees' => new Coordonnees(48.86, 2.34)]);
```

## 3. Les compteurs d'une entreprise peuvent être `null`

`Entreprise::$nombreEtablissements` et `$nombreEtablissementsOuverts` sont des `?int`. Un nombre inconnu vaut `null`, pas `0`. Écrivez `$entreprise->nombreEtablissements ?? 0` quand vous avez besoin d'un nombre.

## 4. `ApiGouvClient` demande le client Geo

Seul le code PHP simple qui construisait `ApiGouvClient` à la main sans Geo est concerné. Passez le troisième client :

```php
$api = new ApiGouvClient(
    new EntreprisesClient(new Requester($transport, 'https://recherche-entreprises.api.gouv.fr')),
    new AdresseClient(new Requester($transport, 'https://data.geopf.fr/geocodage')),
    new GeoClient(new Requester($transport, 'https://geo.api.gouv.fr')),
);
```

`geo()` ne lance plus `LogicException`.

## 5. `epcisDuDepartement` lance une exception pour un département inconnu

Comme `communesDuDepartement`, elle lance `NotFoundException` quand le code de département n'existe pas. Un département connu sans EPCI donne toujours une liste vide. Voir la règle "Inconnu ou vide" dans [Compatibilité](compatibilite.md).

## 6. Les faux vérifient leurs entrées

`FakeEntreprises::rechercher('')`, `FakeAdresse::rechercher(' ')`, une limite hors de 1..50 ou des coordonnées hors limites lancent `InvalidArgumentException`, comme les vrais clients. `FakeEntreprises::rechercher()` rejette aussi un `perPage` hors de 1..25, comme `SearchQuery`. Corrigez les tests qui passaient de telles valeurs.

`FakeGeo::epcisDuDepartement()` lève `NotFoundException` si le département n'est pas lui aussi stocké : ajoutez `Factories::departement()` dans `with()`.

## 7. Symfony 7.4 ou 8

Symfony 7.2 et 7.3 ne sont plus pris en charge. Passez à la 7.4 (LTS) ou à la 8.

## 8. Les messages Symfony utilisent la phrase anglaise comme clé

Les contraintes suivent la convention de Symfony : le message par défaut est la phrase anglaise, et le fichier français utilise cette phrase comme clé. Si vous aviez remplacé `api_gouv.siren` ou une autre clé `api_gouv.*` dans vos `translations/validators.*`, utilisez la phrase comme clé de votre traduction :

```yaml
# translations/validators.fr.yaml
'This value must be a valid SIREN number (9 digits).': 'Ce numéro SIREN est invalide.'
```

`symfony/translation` n'est plus nécessaire que pour le français.

## 9. Marques `@internal`

Les constructeurs des objets de données et les classes de `Support` sont `@internal`. Ils marchent toujours, mais ils sont hors de la promesse de compatibilité. Deux d'entre eux ont aussi changé de forme : `new Adresse(...)` prend maintenant `coordonnees` en troisième argument, et `new Etablissement(...)` prend un seul `coordonnees` au lieu de `latitude` et `longitude`. Un appel positionnel écrit pour la 0.3 casse : utilisez `Factories` ou des arguments nommés. Construisez les objets avec `Factories` dans vos tests.

## Rien à faire si

Vous n'avez rien à faire si toutes ces affirmations sont vraies :

- votre code n'importe pas `Coordonnees` ;
- il ne lit pas `latitude` ou `longitude` sur un établissement, ni les deux compteurs d'une entreprise sans `?? 0` ;
- il ne construit pas `ApiGouvClient` ni un objet de données avec `new` (il utilise les passerelles et `Factories`) ;
- il ne compte pas sur une liste vide de `epcisDuDepartement` pour un département inconnu ;
- ses tests ne donnent pas d'entrées invalides aux faux, et stockent un département avant de lister ses EPCI ;
- il tourne sous Symfony 7.4 ou 8, et ne remplace pas les clés de traduction `api_gouv.*`.

## Et la 1.0 ?

La 1.0.0 sera publiée sur ce code sans changement. Voir [Compatibilité](compatibilite.md) pour ce qu'elle garantit.
