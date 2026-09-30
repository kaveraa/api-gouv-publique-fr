# Testing

The package has fakes so your tests do not call the real APIs.

## ApiGouv::fake() with Factories (Laravel)

`ApiGouv::fake()` replaces the clients for the rest of the test. It also replaces the injected `EntreprisesApi`, `AdresseApi` and `GeoApi`. It returns a `FakeApiGouv`.

```php
use Kaveraa\ApiGouv\Laravel\ApiGouv;
use Kaveraa\ApiGouv\Testing\Factories;

it('shows the company name', function () {
    $fake = ApiGouv::fake();
    $fake->entreprises()->with(Factories::entreprise([
        'siren' => '812487973',
        'nomComplet' => 'ACME',
    ]));

    expect(ApiGouv::entreprises()->parSiren('812487973')->nomComplet)->toBe('ACME');
    expect($fake->entreprises()->calls)->toBe([['parSiren', '812487973']]);
});
```

`Factories` has seven methods: `Factories::entreprise()`, `Factories::etablissement()`, `Factories::adresse()`, `Factories::commune()`, `Factories::departement()`, `Factories::region()` and `Factories::epci()`. Each takes an array of the fields you want to change. The names are the constructor names of the object.

```php
$fake->adresse()->with(Factories::adresse(['label' => '8 Boulevard du Port 80000 Amiens']));
```

### Geo fake

```php
$fake = ApiGouv::fake();
$fake->geo()->with(Factories::commune());

echo ApiGouv::geo()->commune('80021')->nom;   // Amiens
```

`with()` takes one or more objects: a `Commune`, a `Departement`, a `Region` or an `Epci`. The four factories have these defaults:

| Factory | Defaults |
| --- | --- |
| `Factories::commune()` | Amiens, code `80021`, postal codes `80000`, `80080` and `80090`, departement `80`, region `32`, EPCI `248000531`. |
| `Factories::departement()` | Somme, code `80`, region `32`. |
| `Factories::region()` | Hauts-de-France, code `32`. |
| `Factories::epci()` | CA Amiens Métropole, code `248000531`, departement `80`, region `32`. |

How `FakeGeo` matches:

- `commune()`, `departement()`, `region()` and `epci()` match by code. Otherwise they throw `NotFoundException`.
- `communesParCodePostal()` matches the postal codes of the stored communes.
- `rechercherCommunes()` matches the commune names that contain the text (not case sensitive). The `limit` only cuts the list.
- `communeParCoordonnees()` returns the stored commune with the nearest `centre`, or `null` when there is none.
- `communesDuDepartement()` and `departementsDeLaRegion()` need the departement or the region to be stored too. Otherwise they throw `NotFoundException`.
- `epcisDuDepartement()` matches `codesDepartements`.
- Codes and limits are checked like in the real client.

How the fakes behave:

- `FakeEntreprises::parSiren()` and `parSiret()` return a known company or establishment. Otherwise they throw `NotFoundException`.
- `FakeEntreprises::rechercher()` finds the companies whose `nomComplet` contains the text (not case sensitive).
- `FakeAdresse::rechercher()` and `autocompleter()` find the addresses whose `label` contains the text.
- `FakeAdresse::geocoderInverse()` returns the nearest known address, or `null` when there is none.
- Both fakes record their calls in `$calls`, as pairs of method name and argument.
- The fakes ignore search filters and paging. Only the text is matched.
- `Factories::entreprise()` builds the default head office from the `siren` you give, so `parSiret()` finds the right company. Pass `siege` to use your own.
- `parSiren()` and `parSiret()` reject a malformed number with `InvalidArgumentException`, like the real client.

## Http::fake() (Laravel)

To test at the HTTP level, use the normal Laravel fake. The package uses the Laravel HTTP client, so it works.

```php
use Illuminate\Support\Facades\Http;
use Kaveraa\ApiGouv\Laravel\ApiGouv;

Http::fake([
    'recherche-entreprises.api.gouv.fr/*' => Http::response([
        'results' => [[
            'siren' => '812487973',
            'nom_complet' => 'OCTO',
            'siege' => ['siret' => '81248797300040', 'libelle_commune' => 'BORDEAUX'],
        ]],
        'total_results' => 1,
        'page' => 1,
        'per_page' => 1,
        'total_pages' => 1,
    ]),
]);

expect(ApiGouv::entreprises()->parSiren('812487973')->nomComplet)->toBe('OCTO');
```

A good source of real answers is the folder `tests/fixtures` of this repository.

## Fakes in plain PHP

`FakeEntreprises`, `FakeAdresse` and `FakeGeo` do not need Laravel. They implement `EntreprisesApi`, `AdresseApi` and `GeoApi`. Give them to the code you test.

```php
use Kaveraa\ApiGouv\Testing\Factories;
use Kaveraa\ApiGouv\Testing\FakeAdresse;
use Kaveraa\ApiGouv\Testing\FakeEntreprises;

$entreprises = (new FakeEntreprises)->with(Factories::entreprise(['nomComplet' => 'ACME']));
$adresse = (new FakeAdresse)->with(Factories::adresse());

echo $entreprises->rechercher('acme')->results[0]->nomComplet;   // ACME
echo $adresse->rechercher('Rue de Test')[0]->label;              // 1 Rue de Test 75001 Paris
```
