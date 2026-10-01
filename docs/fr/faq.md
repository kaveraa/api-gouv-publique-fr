# FAQ

## Est-ce officiel ?

Non. C'est un paquet non officiel. Il n'est pas créé par l'État français et il n'est pas affilié à lui. Il appelle seulement des API publiques que tout le monde peut utiliser.

## Quelles API sont incluses ?

Trois : "Recherche d'entreprises", l'API d'adresses (BAN) et l'API Geo (communes, départements, régions, EPCI). L'API Geo est incluse depuis la version 0.2.0. L'API INSEE SIRENE n'est pas incluse.

## Pourquoi des noms de méthodes en français ?

Les noms suivent le domaine et les API : `parSiren`, `parSiret`, `rechercher`, `autocompleter`, `geocoderInverse`. SIREN, SIRET et "commune" n'ont pas de mot anglais exact. Les noms français évitent les mauvaises traductions.

## L'API d'adresses BAN a déménagé. Et maintenant ?

La BAN est maintenant servie par la Géoplateforme à l'adresse `https://data.geopf.fr/geocodage`. Ce paquet utilise cette URL par défaut. Vous pouvez la changer avec la clé de config `adresse.base_url`, ou avec l'URL de base donnée à `Requester`.

## Puis-je l'utiliser sans Laravel ?

Oui. Le paquet de base a seulement besoin d'un client PSR-18 et d'une fabrique PSR-17. Voir [PHP simple](plain-php.md).

## Est-ce que cela marche avec Symfony ?

Oui, depuis la version 0.3.0, avec le bundle `ApiGouvBundle`. Il donne des clients en autowiring, un cache, des contraintes de validation et un mode factice pour les tests. Voir [Symfony](symfony.md).

## Faut-il une clé d'API ?

Non. Toutes les API sont ouvertes. Vous n'avez pas besoin de compte.

## Pourquoi la règle `Siren` n'appelle-t-elle pas l'API ?

Elle vérifie seulement le format et la clé de contrôle. C'est rapide et cela marche hors ligne. Utilisez `EntrepriseExiste` si vous voulez vérifier que l'entreprise existe. Elle est optionnelle car elle appelle l'API.

## Que fait `EntrepriseExiste` quand l'API est en panne ?

Elle échoue en mode fermé. La valeur est refusée avec un message spécial, pour que vous sachiez que l'entreprise n'a pas pu être vérifiée.

## Pourquoi une règle n'arrête-t-elle pas une valeur vide ?

Les objets de règle Laravel sont ignorés quand la valeur est vide. Ajoutez `required` au champ s'il est obligatoire. Les contraintes Symfony acceptent aussi une valeur vide : ajoutez `NotBlank`.

## Que garantit la 1.0 ?

Le versionnage sémantique sur la surface publique : appeler les classes et lire les objets de données continue de marcher dans toute la 1.x. Implémenter les interfaces des API et utiliser du code `@internal` n'est pas couvert. Voir [Compatibilité](compatibilite.md) et [Migrer de 0.3 vers 0.4](migration-0.4.md).

## Le cache est-il actif ?

Non. Il est désactivé par défaut. Activez-le dans la config (`cache.enabled`) ou donnez un `ResponseCache` en PHP simple.

## Quelles sont les limites de taille ?

Une recherche d'entreprises renvoie de 1 à 25 résultats par page. Une recherche d'adresses renvoie de 1 à 50 résultats.
