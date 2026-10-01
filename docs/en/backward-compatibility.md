# Backward compatibility

From 1.0.0 the package follows semantic versioning. A 1.x release never breaks the surface listed below. A break waits for 2.0 and is announced in advance.

## What 1.x guarantees

You can upgrade from any 1.x to any later 1.x without changing your code, as long as you only use the covered surface.

## Covered

- Calling every public class, interface and method that is not marked `@internal`, and reading the public properties of the data objects (`Entreprise`, `Etablissement`, `Dirigeant`, `SearchResult`, `Adresse`, `Coordonnees`, `Commune`, `Departement`, `Region`, `Epci`).
- The public constructors of `Coordonnees` and `SearchQuery`.
- Constructing `ApiGouvClient` with its three clients. A new API may add an optional nullable parameter at the end in a minor release; existing calls keep working.
- `fromArray()` and `fromFeature()` accepting the documented API payloads. A field the API adds is ignored, a field it removes becomes `null` or an empty list, except the key fields (`siren`, `siret`, `code`, `nom`, and the geometry of an address), whose absence throws `InvalidResponseException`.
- The Laravel bridge: config keys of `config/api-gouv.php`, the `ApiGouv` facade methods, the rule classes `Siren`, `Siret` and `EntrepriseExiste`, the translation keys `api-gouv::validation.*`.
- The Symfony bundle: configuration keys, container parameters, service ids and aliases, the constraint classes and their arguments, and the English sentences used as message keys.
- The public classes of the bridges (`LaravelHttpTransport`, `ApiGouvServiceProvider`, the Symfony constraint validators) as the frameworks use them. Constructing or extending them yourself is not covered.
- The exception classes and their HTTP codes.
- The cache key prefix `api-gouv.`.
- The `Testing` fakes and `Factories`: method names, the `calls` arrays and the attribute names the factories accept.

## Not covered

- Implementing `EntreprisesApi`, `AdresseApi` or `GeoApi` yourself. They may gain methods in a minor release. Use the fakes of the package in your tests.
- Extending `ApiGouvClient` or `ApiGouvBundle`.
- Anything marked `@internal`: the constructors of the data objects (build them with `Factories`), the `Support` namespace, the protected hooks of the bundle.
- The exact text of exception messages, and the number or order of HTTP requests behind a method.
- The test kernel and helpers under `tests/`.

## Transport

`Transport` is a small contract you may implement: `get(string $url, array $query = []): Response`. It never gains a method in 1.x. A new capability comes in a separate interface.

## Unknown or empty

- A detail method (`parSiren`, `parSiret`, `commune`, `departement`, `region`, `epci`) throws `NotFoundException` when the item does not exist.
- A sub-list of a parent (`communesDuDepartement`, `departementsDeLaRegion`, `epcisDuDepartement`) throws `NotFoundException` when the parent does not exist, and returns an empty list when the parent has no items.
- A search (`rechercher`, `autocompleter`, `communesParCodePostal`, `rechercherCommunes`) returns an empty list or an empty `SearchResult`.
- A point lookup (`geocoderInverse`, `communeParCoordonnees`) returns `null` outside any address or commune.

## Deprecations

A feature removed in 2.0 is marked `@deprecated` for at least one minor release, with the replacement named in the docblock and in the changelog.
