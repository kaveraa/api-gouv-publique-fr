# Changelog

All notable changes to this project are written in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/). The project follows [Semantic Versioning](https://semver.org/).

## [Unreleased]

## [0.4.0] - 2026-10-01

### Added

- `Factories::dirigeant()`, `Factories::searchResult()` and `Factories::coordonnees()`.
- A backward compatibility promise (`docs/en/backward-compatibility.md`) and the upgrade guide from 0.3 (`docs/en/upgrade-0.4.md`), in English and French.
- A snapshot test of the public API (`tests/fixtures/public-api.json`).
- `GeoCodes::text()` (internal), shared by the address client and the fakes.

### Changed

- `Etablissement` carries `?Coordonnees $coordonnees` instead of `latitude` and `longitude`.
- `Entreprise::$nombreEtablissements` and `$nombreEtablissementsOuverts` are `?int`; unknown is `null`.
- `ApiGouvClient` requires the Geo client; `geo()` no longer throws.
- `epcisDuDepartement` throws `NotFoundException` for an unknown departement, like `communesDuDepartement`.
- The fakes check their input like the real clients (`FakeEntreprises::rechercher`, `FakeAdresse`, `FakeGeo::epcisDuDepartement`).
- Symfony constraint messages are English sentences, used as translation keys (Symfony convention); the French file is keyed on them.
- The data object constructors and the `Support` classes are `@internal`; optional constructor fields have defaults.
- Supported matrix: PHP 8.3+, Laravel 12 and 13, Symfony 7.4 and 8.

### Removed

- `Kaveraa\ApiGouv\Adresse\Coordonnees` (now `Kaveraa\ApiGouv\Coordonnees`).
- Symfony 7.2 and 7.3 support.
- `src/Symfony/translations/validators.en.php` (English is built in).

1.0.0 will be published on this code without change.

## [0.3.0] - 2026-10-01

### Added

- Symfony bundle `ApiGouvBundle`: `api_gouv` configuration (base URL and cache time per API, response cache on a PSR-6 pool), autowired `EntreprisesApi`, `AdresseApi`, `GeoApi` and `ApiGouvClient`, built on the application `http_client`.
- Validator constraints `Siren`, `Siret` and `EntrepriseExiste`, with French and English messages.
- Fake mode for Symfony tests (`api_gouv.fake: true`): the clients are replaced by `FakeEntreprises`, `FakeAdresse` and `FakeGeo`, reachable through `FakeApiGouv`.
- Symfony guide in English and French.

### Changed

- The plain PHP guide points Symfony projects to the bundle. No change for existing code.

## [0.2.0] - 2026-09-30

### Added

- Geo API client (`geo.api.gouv.fr`): communes by INSEE code, postal code, name or coordinates; departements; regions; EPCI. Typed objects `Commune`, `Departement`, `Region` and `Epci`.
- `ApiGouv::geo()` in Laravel, with the `geo` config block (`base_url`, `cache_ttl`).
- `FakeGeo` and the factories `Factories::commune()`, `departement()`, `region()` and `epci()`.
- Geo guide in English and French.

### Changed

- `ApiGouvClient` accepts an optional third argument, the Geo client. Without it, `geo()` throws a `LogicException`. No change for existing code.

## [0.1.0] - 2026-09-30

First release.

### Added

- Company search client (Recherche d'entreprises): `rechercher`, `parSiren` and `parSiret`, with typed objects `Entreprise`, `Etablissement`, `Dirigeant` and `SearchResult`.
- Address client (BAN, served by the Geoplateforme): `rechercher`, `autocompleter` and `geocoderInverse`, with typed objects `Adresse` and `Coordonnees`.
- Plain PHP transport for any PSR-18 client, and an optional response cache for any PSR-16 cache.
- Exception classes: `ApiException`, `NotFoundException`, `RateLimitException` and `InvalidResponseException`.
- Laravel bridge: service provider, facade, config file, translations, and a transport with retry on HTTP 429.
- Laravel validation rules: `Siren`, `Siret` and `EntrepriseExiste`.
- Test fakes and factories: `FakeApiGouv`, `FakeEntreprises`, `FakeAdresse` and `Factories`.
- Documentation in English and French.

[Unreleased]: https://github.com/kaveraa/api-gouv-publique-fr/compare/v0.4.0...HEAD
[0.4.0]: https://github.com/kaveraa/api-gouv-publique-fr/compare/v0.3.0...v0.4.0
[0.3.0]: https://github.com/kaveraa/api-gouv-publique-fr/compare/v0.2.0...v0.3.0
[0.2.0]: https://github.com/kaveraa/api-gouv-publique-fr/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/kaveraa/api-gouv-publique-fr/releases/tag/v0.1.0
