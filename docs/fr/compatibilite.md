# Compatibilité

À partir de la 1.0.0, le paquet suit le versionnage sémantique. Une version 1.x ne casse jamais la surface listée ci-dessous. Une rupture attend la 2.0 et elle est annoncée à l'avance.

## Ce que la 1.x garantit

Vous pouvez passer de toute 1.x à toute 1.x plus récente sans changer votre code, tant que vous n'utilisez que la surface couverte.

## Ce qui est couvert

- Appeler chaque classe, interface et méthode publique qui n'est pas marquée `@internal`, et lire les propriétés publiques des objets de données (`Entreprise`, `Etablissement`, `Dirigeant`, `SearchResult`, `Adresse`, `Coordonnees`, `Commune`, `Departement`, `Region`, `Epci`).
- Les constructeurs publics de `Coordonnees` et `SearchQuery`.
- Construire `ApiGouvClient` avec ses trois clients. Une nouvelle API pourra ajouter un paramètre optionnel nullable en fin de liste dans une version mineure ; les appels existants continuent de fonctionner.
- `fromArray()` et `fromFeature()` acceptent les réponses documentées des API. Un champ ajouté par l'API est ignoré, un champ retiré devient `null` ou une liste vide, sauf les champs clés (`siren`, `siret`, `code`, `nom`, et la géométrie d'une adresse), dont l'absence lance `InvalidResponseException`.
- Le pont Laravel : les clés de configuration de `config/api-gouv.php`, les méthodes de la façade `ApiGouv`, les classes de règles `Siren`, `Siret` et `EntrepriseExiste`, les clés de traduction `api-gouv::validation.*`.
- Le bundle Symfony : les clés de configuration, les paramètres du conteneur, les identifiants et alias de services, les classes de contraintes et leurs arguments, et les phrases anglaises utilisées comme clés des messages.
- Les classes publiques des ponts (`LaravelHttpTransport`, `ApiGouvServiceProvider`, les validateurs de contraintes Symfony) telles que les frameworks les utilisent. Les construire ou les étendre vous-même n'est pas couvert.
- Les classes d'exception et leurs codes HTTP.
- Le préfixe des clés de cache `api-gouv.`.
- Les faux de `Testing` et `Factories` : les noms des méthodes, les tableaux `calls` et les noms d'attributs acceptés par les fabriques.

## Ce qui n'est pas couvert

- Implémenter vous-même `EntreprisesApi`, `AdresseApi` ou `GeoApi`. Ces interfaces peuvent gagner des méthodes dans une version mineure. Utilisez les faux du paquet dans vos tests.
- Étendre `ApiGouvClient` ou `ApiGouvBundle`.
- Tout ce qui est marqué `@internal` : les constructeurs des objets de données (construisez-les avec `Factories`), l'espace de noms `Support`, les points d'extension protégés du bundle.
- Le texte exact des messages d'exception, et le nombre ou l'ordre des requêtes HTTP derrière une méthode.
- Le noyau de test et les outils sous `tests/`.

## Transport

`Transport` est un petit contrat que vous pouvez implémenter : `get(string $url, array $query = []): Response`. Il ne gagne jamais de méthode en 1.x. Une nouvelle capacité arrive dans une interface séparée.

## Inconnu ou vide

- Une méthode de détail (`parSiren`, `parSiret`, `commune`, `departement`, `region`, `epci`) lance `NotFoundException` quand l'élément n'existe pas.
- Une sous-liste d'un parent (`communesDuDepartement`, `departementsDeLaRegion`, `epcisDuDepartement`) lance `NotFoundException` quand le parent n'existe pas, et renvoie une liste vide quand le parent n'a pas d'éléments.
- Une recherche (`rechercher`, `autocompleter`, `communesParCodePostal`, `rechercherCommunes`) renvoie une liste vide ou un `SearchResult` vide.
- Une recherche par point (`geocoderInverse`, `communeParCoordonnees`) renvoie `null` hors de toute adresse ou commune.

## Dépréciations

Une fonctionnalité retirée en 2.0 est marquée `@deprecated` pendant au moins une version mineure, avec le remplacement nommé dans le docblock et dans le changelog.
