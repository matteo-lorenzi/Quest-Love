# Notes d'implémentation — spec #58, C2 Tirage

Notes des implémenteurs des tickets #70, #71, #72 et de la revue, remises à jour après la fusion (PR #83, puis #84 et #85). Spec suivante concernée : #59 (révélation et rattrapage).

## Fichiers clés

- `TirageService::tirer(Joueur, $maintenant): Tirage|EtatAccueil` : le tirage du jour, créé à la première demande puis relu. Sans éventail à montrer, rend l'état de l'accueil (hors état 3, ou état 3 passé 12 h). Création sous transaction ; `TirageDepot::creer` rend null sur la clé unique (erreur 1062), puis relecture.
- `TirageService::proposer` (privée) : bibliothèque, moins le cycle rejoué sur `MissionDepot::recusPar`, moins `TirageDepot::nonRetenusApres` (journées après J-3). Sous trois défis, le cycle repart à zéro ; encore sous trois, toute la bibliothèque. Propositions mélangées avant écriture.
- `TirageService::choisir(Joueur, int $defiId, $maintenant): bool` : faux = refus sans écriture (défi hors du tirage, ou 12 h passée sans choix antérieur). Pas de relecture avant l'INSERT : `MissionDepot::ajouter` rend null sur `uk_mission_tirage`, le premier choix reste.
- `JourneeService(participations, duos, missions)` : `etatAccueil` rend `ATirer` (état 3) puis `DefiEnvoye` (état 4, une mission du jour a ce joueur pour émetteur). `enHeureDeParis` est une méthode d'instance publique.
- `TirageControleur` : GET `afficher` (`?carte=1..3` retourne une carte, sans écriture) ; POST `choisir` reçoit le rang de la carte, jamais un numéro de défi. Tout refus redirige vers `tirage`, dont le GET explique.
- Vues : `c2-tirage` (`$defis`, `$retournee`, `$message`), composant `carte-defi`, `app/public/scripts/eventail.js` (animation seule, rien sous `prefers-reduced-motion`).
- Démonstration (CURDATE(), avant 12 h) : duo 5, Eva (106) et Léo (107) en état 3 sans tirage ; duo 6, Ana (108) en état 4 (tirage 1, mission sur le défi 2) et Noé (109) en état 3.

## Schéma (`sql/schema.sql`)

- `tirage(id, duo_id CASCADE, emetteur_id, destinataire_id, date_jour)`, unique `(emetteur_id, date_jour)` ; émetteur et destinataire sans cascade.
- `tirage_proposition(id, tirage_id CASCADE, defi_id)`, unique `(tirage_id, defi_id)`.
- `mission(..., tirage_id UNIQUE, defi_id)`, clé composite `(tirage_id, defi_id)` vers `tirage_proposition` ; statuts `attribue_non_revele`, `en_cours`, `valide`, `expire`, `annule`.

## Pour #59

- `JourneeService::etatAccueil` porte un `TODO(simplif)` : passé 12 h sans choix, l'état 3 reste au lieu de la journée blanche « l'un de vous n'a pas choisi » (texte à rédiger).
- L'état 4 dure jusqu'à la révélation : à remplacer là.
- `MissionDepot::reveler` doit poser `date_revelation` : sans elle, aucun défi ne compte comme reçu (cadrage § 4.6).

## Pièges

- `SchemaTest` vide `defi` et `categorie` : un test qui tire fournit ses défis (`TirageServiceTest::bibliotheque(n)`, `setUpBeforeClass`). `categorie.libelle` est unique : la classe nettoie en `tearDownAfterClass`, après `viderLaBase`.
- Une course se teste avec un dépôt anonyme dont la première lecture rend null (`TirageServiceTest`, comme `AppairageServiceTest`).
- Le test de course voit ses propres propositions comme « non retenues récentes » : d'où le repli sur toute la bibliothèque.
- `scripts/verifier.sh` refuse `new DateTimeImmutable(` hors de `index.php` : passer par `$maintenant`, `enHeureDeParis` ou `createFromFormat`.
- Essai HTTP d'un worktree : `quest-love.local` sert le dossier principal. Lancer `php -S localhost:8765 -t app/public` dans le worktree, après `php sql/reinitialiser.php`. Le jeton CSRF se lit sur `?ecran=connexion` (les pages sans formulaire n'en ont pas) ; après une modification de `config.php`, attendre 2 s (`opcache.revalidate_freq`).
