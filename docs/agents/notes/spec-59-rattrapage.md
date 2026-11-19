# Notes d'implémentation — spec #59, rattrapage et révélation

Notes des implémenteurs des tickets de la spec #59, sur la branche d'intégration `rattrapage`.

## Ticket #73 — branchement au routeur, file d'événements, D3

- `RattrapageService(PDO, JoueurDepot)::rattraper(Joueur, $maintenant): Evenement[]` : une transaction ; verrou du joueur seul, puis des deux lignes du duo (même ordre que `AppairageService::dissoudre`). Décision prise sur la ligne fraîche, jamais sur l'objet reçu (il peut être périmé). Un `TODO(simplif)` y marque les opérations à ajouter : expiration (#61), révélation, annulation à 12 h, dans l'ordre du doc technique § 3.2, toutes dans la même transaction.
- Le service rend les événements ; c'est le routeur (étape 3) qui les range dans `$_SESSION['evenements']`, en valeurs de l'énumération `Evenement` (chaînes), à la suite de la file. Le modèle ne touche jamais la session.
- Ordre de la file : duo dissous, expiration, passage à zéro. Un nouvel événement = un cas de `Evenement` + une branche du `match` d'`EvenementControleur::afficher` (titre, texte, écran de l'action, libellé).
- `EvenementControleur::afficher` (GET seul) dépile un événement par affichage ; file vide : redirection vers l'écran de l'étape d'accès. L'action est un lien GET : si la file n'est pas vide, le routeur ramène à `evenement`.
- Vues : `ecrans/d-evenement.php` inclut `composants/bloc-evenement.php` (titre, texte, action ; sans illustration, `TODO(simplif)`). Textes d'attente `[TEXTE À RÉDIGER : …]`. Pas de CSS dédié.
- Entrée `evenement` en accès Connecté : D3 vise un joueur sans duo.
- `JoueurDepot::effacerDissolutionASignaler` remet le marqueur à 0 dans la transaction.

## Pièges

- Le rattrapage tourne à chaque requête d'un joueur connecté, écrans publics compris : il appelle `Routeur::joueurConnecte`, qui renvoie vers la connexion si le compte a disparu.
- Après le POST de connexion, la session n'était pas encore connectée au moment de l'étape 3 : le premier rattrapage a lieu à la requête suivante (redirection vers l'accueil), qui part alors vers `evenement`. Vérifié à la main avec `php -S` (Tom marqué : accueil → evenement → D3 → accueil).
- Tests : `RattrapageServiceTest` crée le duo par `AppairageService` puis le dissout ; il passe un objet `Joueur` périmé (marqueur encore vrai) au second rattrapage pour vérifier la relecture sous verrou.
