# Tests

Le paquet fournit des faux pour que vos tests n'appellent pas les vraies API.

## ApiGouv::fake() avec Factories (Laravel)

`ApiGouv::fake()` remplace les clients pour le reste du test. Il remplace aussi les interfaces injectées `EntreprisesApi` et `AdresseApi`. Il renvoie un `FakeApiGouv`.

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

`Factories` a trois méthodes : `Factories::entreprise()`, `Factories::etablissement()` et `Factories::adresse()`. Chacune prend un tableau des champs à changer. Les noms sont ceux du constructeur de l'objet.

```php
$fake->adresse()->with(Factories::adresse(['label' => '8 Boulevard du Port 80000 Amiens']));
```

Comportement des faux :

- `FakeEntreprises::parSiren()` et `parSiret()` renvoient une entreprise ou un établissement connu. Sinon ils lancent `NotFoundException`.
- `FakeEntreprises::rechercher()` trouve les entreprises dont `nomComplet` contient le texte (sans tenir compte des majuscules).
- `FakeAdresse::rechercher()` et `autocompleter()` trouvent les adresses dont `label` contient le texte.
- `FakeAdresse::geocoderInverse()` renvoie l'adresse connue la plus proche, ou `null` s'il n'y en a pas.
- Les deux faux gardent leurs appels dans `$calls`, sous forme de paires nom de méthode et argument.
- Les faux ignorent les filtres et la pagination de la recherche. Seul le texte est comparé.
- `Factories::entreprise()` construit le siège par défaut à partir du `siren` donné, donc `parSiret()` trouve la bonne entreprise. Donnez `siege` pour utiliser le vôtre.
- `parSiren()` et `parSiret()` refusent un numéro mal formé avec `InvalidArgumentException`, comme le vrai client.

## Http::fake() (Laravel)

Pour tester au niveau HTTP, utilisez le faux normal de Laravel. Le paquet utilise le client HTTP de Laravel, donc cela fonctionne.

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

Une bonne source de vraies réponses est le dossier `tests/fixtures` de ce dépôt.

## Les faux en PHP simple

`FakeEntreprises` et `FakeAdresse` n'ont pas besoin de Laravel. Ils implémentent `EntreprisesApi` et `AdresseApi`. Donnez-les au code que vous testez.

```php
use Kaveraa\ApiGouv\Testing\Factories;
use Kaveraa\ApiGouv\Testing\FakeAdresse;
use Kaveraa\ApiGouv\Testing\FakeEntreprises;

$entreprises = (new FakeEntreprises)->with(Factories::entreprise(['nomComplet' => 'ACME']));
$adresse = (new FakeAdresse)->with(Factories::adresse());

echo $entreprises->rechercher('acme')->results[0]->nomComplet;   // ACME
echo $adresse->rechercher('Rue de Test')[0]->label;              // 1 Rue de Test 75001 Paris
```
