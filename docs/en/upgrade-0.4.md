# Upgrade from 0.3 to 0.4

Version 0.4.0 makes the changes needed before 1.0. Each one is listed with what to do. Version 1.0.0 is the same code, so this is the only migration before 1.0.

## 1. `Coordonnees` moved to the root namespace

```php
// Before
use Kaveraa\ApiGouv\Adresse\Coordonnees;
// After
use Kaveraa\ApiGouv\Coordonnees;
```

## 2. `Etablissement` has `coordonnees` instead of `latitude` and `longitude`

```php
// Before
$lat = $etablissement->latitude;
// After
$lat = $etablissement->coordonnees?->latitude;

// In tests, before
Factories::etablissement(['latitude' => 48.86, 'longitude' => 2.34]);
// After
Factories::etablissement(['coordonnees' => new Coordonnees(48.86, 2.34)]);
```

## 3. Company counters can be `null`

`Entreprise::$nombreEtablissements` and `$nombreEtablissementsOuverts` are `?int`. An unknown count is `null`, not `0`. Write `$entreprise->nombreEtablissements ?? 0` when you need a number.

## 4. `ApiGouvClient` needs the Geo client

Only plain PHP code that built `ApiGouvClient` by hand without Geo is affected. Pass the third client:

```php
$api = new ApiGouvClient(
    new EntreprisesClient(new Requester($transport, 'https://recherche-entreprises.api.gouv.fr')),
    new AdresseClient(new Requester($transport, 'https://data.geopf.fr/geocodage')),
    new GeoClient(new Requester($transport, 'https://geo.api.gouv.fr')),
);
```

`geo()` no longer throws `LogicException`.

## 5. `epcisDuDepartement` throws for an unknown departement

Like `communesDuDepartement`, it throws `NotFoundException` when the departement code does not exist. A known departement without EPCI still gives an empty list. See the "Unknown or empty" rule in [Backward compatibility](backward-compatibility.md).

## 6. The fakes check their input

`FakeEntreprises::rechercher('')`, `FakeAdresse::rechercher(' ')`, a limit outside 1..50 or coordinates out of range throw `InvalidArgumentException`, like the real clients. `FakeEntreprises::rechercher()` also rejects a `perPage` outside 1..25, like `SearchQuery`. Fix the tests that passed such values.

`FakeGeo::epcisDuDepartement()` throws `NotFoundException` unless the departement is stored too: add `Factories::departement()` to `with()`.

## 7. Symfony 7.4 or 8

Symfony 7.2 and 7.3 are no longer supported. Upgrade to 7.4 (LTS) or 8.

## 8. Symfony messages use the English sentence as key

The constraints follow the Symfony convention: the default message is the English sentence, and the French file is keyed on it. If you overrode `api_gouv.siren` or another `api_gouv.*` key in your `translations/validators.*`, key your override on the sentence:

```yaml
# translations/validators.fr.yaml
'This value must be a valid SIREN number (9 digits).': 'Ce numéro SIREN est invalide.'
```

`symfony/translation` is only needed for French now.

## 9. `@internal` marks

The constructors of the data objects and the `Support` classes are `@internal`. They still work, but they are outside the compatibility promise. Two of them changed shape as well: `new Adresse(...)` now takes `coordonnees` as its third argument, and `new Etablissement(...)` takes one `coordonnees` instead of `latitude` and `longitude`. A positional call written for 0.3 breaks: use `Factories` or named arguments. Build objects with `Factories` in your tests.

## Nothing to do if

You have nothing to do if all of these are true:

- your code does not import `Coordonnees`;
- it does not read `latitude` or `longitude` on an establishment, nor the two company counters without a `?? 0`;
- it does not build `ApiGouvClient` or a data object with `new` (it uses the bridges and `Factories`);
- it does not rely on an empty list from `epcisDuDepartement` for an unknown departement;
- its tests do not feed the fakes with invalid input, and store a departement before listing its EPCI;
- it runs Symfony 7.4 or 8, and does not override the `api_gouv.*` translation keys.

## And 1.0?

1.0.0 will be published on this code without change. See [Backward compatibility](backward-compatibility.md) for what it guarantees.
