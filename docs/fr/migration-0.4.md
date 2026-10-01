# Migrer de 0.3 vers 0.4

La version 0.4.0 fait les changements nécessaires avant la 1.0. Chacun est listé avec ce qu'il faut faire. La version 1.0.0 est le même code, c'est donc la seule migration avant la 1.0.

## 1. `Coordonnees` passe dans l'espace de noms racine

Avant : `use Kaveraa\ApiGouv\Adresse\Coordonnees;`
Après : `use Kaveraa\ApiGouv\Coordonnees;`

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

Comme `communesDuDepartement`, elle lance `NotFoundException` quand le code de département n'existe pas. Un département connu sans EPCI donne toujours une liste vide. Voir la règle inconnu ou vide dans [Compatibilité](compatibilite.md).

## 6. Les faux vérifient leurs entrées

`FakeEntreprises::rechercher('')`, `FakeAdresse::rechercher(' ')`, une limite hors de 1..50 ou des coordonnées hors limites lancent `InvalidArgumentException`, comme les vrais clients. Corrigez les tests qui passaient de telles valeurs.

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

Les constructeurs des objets de données et les classes de `Support` sont `@internal`. Ils marchent toujours, mais ils sont hors de la promesse de compatibilité. Construisez les objets avec `Factories` dans vos tests.

## Rien à faire si

Vous appelez seulement les clients via Laravel ou Symfony, vous lisez les objets de données, et vous construisez les données de test avec `Factories` sans `latitude`/`longitude`. Alors la 0.4.0 ne change rien pour vous.

## Et la 1.0 ?

La 1.0.0 sera publiée sur ce code sans changement. Voir [Compatibilité](compatibilite.md) pour ce qu'elle garantit.
