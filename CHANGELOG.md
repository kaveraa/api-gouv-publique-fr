# Changelog

All notable changes to this project are written in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/). The project follows [Semantic Versioning](https://semver.org/).

## [Unreleased]

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

[Unreleased]: https://github.com/kaveraa/api-gouv-publique-fr/compare/v0.2.0...HEAD
[0.2.0]: https://github.com/kaveraa/api-gouv-publique-fr/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/kaveraa/api-gouv-publique-fr/releases/tag/v0.1.0
