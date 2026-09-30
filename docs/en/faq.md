# FAQ

## Is this official?

No. This is an unofficial package. It is not made by the French State and it is not affiliated with it. It only calls public APIs that anyone can use.

## Which APIs are included?

Version 1 has two: "Recherche d'entreprises" (company search) and the address API (BAN). The Geo API and the INSEE SIRENE API are not included.

## Why French method names?

The names match the domain and the APIs: `parSiren`, `parSiret`, `rechercher`, `autocompleter`, `geocoderInverse`. SIREN, SIRET and "commune" have no exact English words. French names avoid wrong translations.

## The BAN address API moved. What now?

The BAN is now served by the Geoplateforme at `https://data.geopf.fr/geocodage`. This package uses that URL by default. You can change it with the config key `adresse.base_url`, or with the base URL you give to `Requester`.

## Can I use it without Laravel?

Yes. The core package needs only a PSR-18 client and a PSR-17 factory. See [Plain PHP](plain-php.md).

## Does it need an API key?

No. Both APIs are open. You do not need an account.

## Why does the `Siren` rule not call the API?

It only checks the format and the check digit. This is fast and works offline. Use `EntrepriseExiste` if you want to check that the company exists. It is opt-in because it calls the API.

## What happens to `EntrepriseExiste` when the API is down?

It fails closed. The value is refused with a special message, so you know the company could not be verified.

## Why does a rule not stop an empty value?

Laravel rule objects are skipped on empty values. Add `required` to the field if it is mandatory.

## Is the cache on?

No. It is off by default. Turn it on in the config (`cache.enabled`) or pass a `ResponseCache` in plain PHP.

## What are the size limits?

A company search returns 1 to 25 results per page. An address search returns 1 to 50 results.
