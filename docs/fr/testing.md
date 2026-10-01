# Tests

Le paquet fournit des faux pour que vos tests n'appellent pas les vraies API.

## ApiGouv::fake() avec Factories (Laravel)

`ApiGouv::fake()` remplace les clients pour le reste du test. Il remplace aussi les interfaces injectées `EntreprisesApi`, `AdresseApi` et `GeoApi`. Il renvoie un `FakeApiGouv`.

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

`Factories` a dix méthodes : `Factories::entreprise()`, `Factories::etablissement()`, `Factories::dirigeant()`, `Factories::searchResult()`, `Factories::adresse()`, `Factories::coordonnees()`, `Factories::commune()`, `Factories::departement()`, `Factories::region()` et `Factories::epci()`. Chacune prend un tableau des champs à changer. Les noms sont ceux du constructeur de l'objet. Construisez les objets avec `Factories`, pas avec `new` : les constructeurs sont internes.

```php
$fake->adresse()->with(Factories::adresse(['label' => '8 Boulevard du Port 80000 Amiens']));
```

### Faux Geo

```php
$fake = ApiGouv::fake();
$fake->geo()->with(Factories::commune());

echo ApiGouv::geo()->commune('80021')->nom;   // Amiens
```

`with()` prend un ou plusieurs objets : une `Commune`, un `Departement`, une `Region` ou un `Epci`. Ces fabriques ont les valeurs par défaut suivantes :

| Fabrique | Valeurs par défaut |
| --- | --- |
| `Factories::commune()` | Amiens, code `80021`, codes postaux `80000`, `80080` et `80090`, département `80`, région `32`, EPCI `248000531`. |
| `Factories::departement()` | Somme, code `80`, région `32`. |
| `Factories::region()` | Hauts-de-France, code `32`. |
| `Factories::epci()` | CA Amiens Métropole, code `248000531`, département `80`, région `32`. |
| `Factories::coordonnees()` | Paris, latitude `48.86`, longitude `2.34`. |
| `Factories::dirigeant()` | BRUNO HIDIER, "Président", "personne physique". |
| `Factories::searchResult()` | Une entreprise par défaut, page 1 sur 1. |

Comment `FakeGeo` compare :

- `commune()`, `departement()`, `region()` et `epci()` comparent le code. Sinon ils lancent `NotFoundException`.
- `communesParCodePostal()` compare les codes postaux des communes stockées.
- `rechercherCommunes()` trouve les communes dont le nom contient le texte (sans tenir compte des majuscules). `limit` ne fait que couper la liste.
- `communeParCoordonnees()` renvoie la commune stockée dont le `centre` est le plus proche, ou `null` s'il n'y en a pas.
- `communesDuDepartement()`, `departementsDeLaRegion()` et `epcisDuDepartement()` demandent que le département ou la région soit aussi stocké. Sinon ils lancent `NotFoundException`.
- `epcisDuDepartement()` compare ensuite `codesDepartements`.
- Les codes et les limites sont vérifiés comme dans le vrai client.

Comportement des faux :

- `FakeEntreprises::parSiren()` et `parSiret()` renvoient une entreprise ou un établissement connu. Sinon ils lancent `NotFoundException`.
- `FakeEntreprises::rechercher()` trouve les entreprises dont `nomComplet` contient le texte (sans tenir compte des majuscules).
- `FakeAdresse::rechercher()` et `autocompleter()` trouvent les adresses dont `label` contient le texte.
- `FakeAdresse::geocoderInverse()` renvoie l'adresse connue la plus proche, ou `null` s'il n'y en a pas.
- Les trois faux gardent leurs appels dans `$calls`, sous forme de paires nom de méthode et argument.
- Les faux ignorent les filtres et la pagination de la recherche. Seul le texte est comparé.
- `Factories::entreprise()` construit le siège par défaut à partir du `siren` donné, donc `parSiret()` trouve la bonne entreprise. Donnez `siege` pour utiliser le vôtre.
- `parSiren()` et `parSiret()` refusent un numéro mal formé avec `InvalidArgumentException`, comme le vrai client.

Les faux vérifient leurs entrées comme les vrais clients : un texte vide ou une limite hors de l'intervalle lance `InvalidArgumentException`.

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

## Avec Symfony

Activez le mode factice du bundle pour l'environnement de test, dans `config/packages/api_gouv.yaml` :

```yaml
when@test:
    api_gouv:
        fake: true
```

Les clients sont alors les faux de cette page. Lisez `FakeApiGouv` dans le conteneur de test et remplissez-le avec `Factories`. Voir la section Tests de [Symfony](symfony.md#tests).

## Les faux en PHP simple

`FakeEntreprises`, `FakeAdresse` et `FakeGeo` n'ont pas besoin de Laravel. Ils implémentent `EntreprisesApi`, `AdresseApi` et `GeoApi`. Donnez-les au code que vous testez.

```php
use Kaveraa\ApiGouv\Testing\Factories;
use Kaveraa\ApiGouv\Testing\FakeAdresse;
use Kaveraa\ApiGouv\Testing\FakeEntreprises;

$entreprises = (new FakeEntreprises)->with(Factories::entreprise(['nomComplet' => 'ACME']));
$adresse = (new FakeAdresse)->with(Factories::adresse());

echo $entreprises->rechercher('acme')->results[0]->nomComplet;   // ACME
echo $adresse->rechercher('Rue de Test')[0]->label;              // 1 Rue de Test 75001 Paris
```
