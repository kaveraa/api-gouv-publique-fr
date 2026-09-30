# Testing

The package has fakes so your tests do not call the real APIs.

## ApiGouv::fake() with Factories (Laravel)

`ApiGouv::fake()` replaces the clients for the rest of the test. It also replaces the injected `EntreprisesApi` and `AdresseApi`. It returns a `FakeApiGouv`.

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

`Factories` has three methods: `Factories::entreprise()`, `Factories::etablissement()` and `Factories::adresse()`. Each takes an array of the fields you want to change. The names are the constructor names of the object.

```php
$fake->adresse()->with(Factories::adresse(['label' => '8 Boulevard du Port 80000 Amiens']));
```

How the fakes behave:

- `FakeEntreprises::parSiren()` and `parSiret()` return a known company or establishment. Otherwise they throw `NotFoundException`.
- `FakeEntreprises::rechercher()` finds the companies whose `nomComplet` contains the text (not case sensitive).
- `FakeAdresse::rechercher()` and `autocompleter()` find the addresses whose `label` contains the text.
- `FakeAdresse::geocoderInverse()` returns the nearest known address, or `null` when there is none.
- Both fakes record their calls in `$calls`, as pairs of method name and argument.

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

`FakeEntreprises` and `FakeAdresse` do not need Laravel. They implement `EntreprisesApi` and `AdresseApi`. Give them to the code you test.

```php
use Kaveraa\ApiGouv\Testing\Factories;
use Kaveraa\ApiGouv\Testing\FakeAdresse;
use Kaveraa\ApiGouv\Testing\FakeEntreprises;

$entreprises = (new FakeEntreprises)->with(Factories::entreprise(['nomComplet' => 'ACME']));
$adresse = (new FakeAdresse)->with(Factories::adresse());

echo $entreprises->rechercher('acme')->results[0]->nomComplet;   // ACME
echo $adresse->rechercher('Rue de Test')[0]->label;              // 1 Rue de Test 75001 Paris
```
