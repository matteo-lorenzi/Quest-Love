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

## Ticket #74 — révélation dès le second choix, C1 état 5

- `RattrapageService::construire(PDO)` (comme `AppairageService`) : le routeur et les tests l'appellent. Dépendances ajoutées : `MissionDepot`, `JourneeService`.
- Sortie rapide avant la transaction (récit 9) : ni marqueur de dissolution, ni mission `attribue_non_revele` destinée au joueur (index `ix_mission_rattrapage`) → `[]` sans verrou. Expiration (#61) et annulation à 12 h devront élargir cette condition (missions `en_cours` échues, missions non révélées émises par le joueur).
- Révélation (méthode privée `reveler`) : sous verrou, missions non révélées des deux joueurs, appariées par journée (`JourneeService::journee(dateAttribution)`). `date_revelation` = `$maintenant` ; `date_limite` = `minuitSuivant(dateAttribution)`, pas de `$maintenant` : une révélation rattrapée le lendemain ne prolonge pas le défi (l'expiration de #61 la traitera).
- `MissionDepot::reveler(id, maintenant, dateLimite)` réécrit aussi `date_limite`.
- `EtatAccueil::DefiRevele` (état 5) : `JourneeService::etatAccueil` le rend dès qu'une mission du jour destinée au joueur a `date_revelation`, avant le test de l'état 4. Lecture seule : `date_decouverte` reste à poser par C3 (#60). Le `TODO(simplif)` de la journée blanche « l'un de vous n'a pas choisi » est toujours là (ticket de l'annulation).
- Socle C1 : variante état 5 sans lien (C3 n'existe pas encore) ; à brancher sur l'écran du défi reçu avec #60.
- Démonstration : duo 7, Lina (110) et Sam (111), deux missions non révélées du jour (tirages 2 et 3) ; le premier chargement les révèle.
- Tests : `RattrapageServiceTest` crée son propre défi (`setUpBeforeClass`, catégorie « Catégorie du test de rattrapage ») et écrit tirage et mission par les dépôts ; la date limite de départ y est volontairement fausse pour prouver que la révélation la fixe.

## Ticket #75 — annulation à 12 h, journée blanche « l'un de vous n'a pas choisi »

- Annulation (méthode privée `annuler`) : sous verrou, après `reveler`, toute mission `attribue_non_revele` du duo dont `$maintenant >= bascule(dateAttribution)` passe `annule` (`changerStatut`). Révélation d'abord : deux choix rattrapés après 12 h sont révélés, jamais annulés.
- `MissionDepot::trouverParDuoEtStatut(duoId, statut)` (index de `fk_mission_duo`) : sert à la sortie rapide et à l'annulation, dans les deux sens. La sortie rapide teste désormais « marqueur D3, ou mission non révélée dans le duo » : l'émetteur seul voit aussi l'annulation. #61 devra encore l'élargir aux missions `en_cours` échues (le `TODO(simplif)` restant le dit).
- `EtatAccueil::JourneeBlancheSansChoix` : branche « deux oui » de `etatAccueil`, après le test de l'état 5 : passé 12 h sans révélation, ou mission du jour `annule`, pour les deux joueurs. Le `TODO(simplif)` de #74 est levé ; `ATirer` n'existe plus après 12 h.
- C2 : `TirageControleur` montre `MIDI_PASSE` sur `JourneeBlancheSansChoix` (plus sur `ATirer`). Limite : celui qui avait choisi et rouvre C2 après 12 h lit ce message au lieu d'être renvoyé à l'accueil.
- Socle C1 : variante sans action, texte `[TEXTE À RÉDIGER : …]`.
- Démonstration : duo 8, Jade (112) et Owen (113), mission de Jade déjà `annule` (lisible à toute heure). Ana et Noé (duo 6) basculent aussi dans la variante au premier chargement après 12 h.
- Aucun événement empilé pour l'annulation (doc technique § 3.2) : seul C1 l'affiche.

## Pièges

- Le rattrapage tourne à chaque requête d'un joueur connecté, écrans publics compris : il appelle `Routeur::joueurConnecte`, qui renvoie vers la connexion si le compte a disparu.
- Après le POST de connexion, la session n'était pas encore connectée au moment de l'étape 3 : le premier rattrapage a lieu à la requête suivante (redirection vers l'accueil), qui part alors vers `evenement`. Vérifié à la main avec `php -S` (Tom marqué : accueil → evenement → D3 → accueil).
- Tests : `RattrapageServiceTest` crée le duo par `AppairageService` puis le dissout ; il passe un objet `Joueur` périmé (marqueur encore vrai) au second rattrapage pour vérifier la relecture sous verrou.
