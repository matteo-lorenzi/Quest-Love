# Pièges de la couche modèle (entités, dépôts, services) — à lire avant de modifier app/modeles/

Rappels courts. La règle fait foi dans `04-technique/doc-technique.md` (§ 2.4, § 3.2, § 3.5, § 3.8) et `docs/adr/0001-controleur-frontal-mvc.md` ; `bash scripts/verifier.sh` attrape les cas marqués (V).

## Frontières de la couche

- Le modèle ne connaît ni l'écran, ni l'adresse, ni la session, ni `header()`. Il rend une valeur (`Evenement[]`, `?Mission`, `bool`) ; le routeur ou le contrôleur la range et redirige. (V : `ecran=`)
- SQL uniquement dans `depots/`, en requêtes préparées, même pour un entier de l'URL. Un service n'écrit jamais de SQL.
- Les entités ne lisent ni n'écrivent en base. Pas de classe mère, pas de conteneur : un service s'obtient par `Service::construire(PDO)`.
- Un refus ne lève pas d'écriture : le service rend `false` ou `null`, le contrôleur redirige, le GET suivant explique.

## Temps

- L'heure arrive en paramètre `DateTimeImmutable $maintenant`. Jamais `new DateTimeImmutable(`, `time()`, `date(`, ni l'horloge SQL pour un horodatage ; passer par `$maintenant`, `JourneeService::enHeureDeParis` ou `createFromFormat`. (V)
- Les bornes de journée se calculent au calendrier (`modify`), jamais par 86 400 secondes.

## Concurrence

- Verrouiller un duo = `JoueurDepot::verrouillerAvecPartenaire`, jamais un joueur seul puis l'autre (interblocage MySQL 1213). Il rend `null` si un `duo_id` a changé : abandonner. (V)
- Décider sur la ligne relue sous verrou, jamais sur l'objet `Joueur` ou `Mission` reçu (il peut être périmé).
- Les garanties sont en base : unicité sur `participation` et `tirage`, statut vérifié avant transition sur `mission`. Un `INSERT` en doublon rend `null` (erreur 1062) au lieu de planter.
- Ordre du rattrapage (§ 3.2) : D3, révélation, annulation, expiration, passage à zéro. Ne pas le réordonner sans mettre à jour le § 3.2 et `docs/agents/notes/`.

## Points de vie

- Seul `PointsDeVieService` fait écrire `pv`. Les valeurs viennent de `PointsDeVieService::PENALITE` et `Joueur::PV_DEPART`, jamais d'un « 5 PV » écrit en dur dans un texte. (V)

## Nommage

- Français sans accent, suffixes `Depot`, `Service`, `Controleur`, vocabulaire de `GLOSSARY.md` (`Joueur`, `Duo`, `Tirage`, `Mission`).
- États en énumérations (`StatutMission`, `EtatAccueil`, `Evenement`) ; jamais de chaîne libre. Un nouvel événement = un cas de `Evenement` + une branche du `match` d'`EvenementControleur`.

## Tests

- Chaque test crée ses propres défis (`setUpBeforeClass`) : `SchemaTest` vide `defi` et `categorie`.
- Une course se teste avec un dépôt anonyme dont la première lecture rend `null`.
