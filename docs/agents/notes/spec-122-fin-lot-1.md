# Notes d'implémentation — spec #122, fin du lot 1

Une section par ticket (#123 à #126, puis #129). Chaque ticket y écrit sa ligne « Livré : … » ; le manifeste (doc technique § 5) est recoché par #129.

## Ticket #124 : temps restant avant minuit sur C3

Livré : C3 « en cours » (`ecrans/c3-defi-recu.php`, `DefiRecuControleur`), `app/public/scripts/echeance.js`, doc technique § 4.3.

- `DefiRecuControleur::afficher` passe `dateLimite` (entité `Mission`) et `secondesRestantes` (`dateLimite - $maintenant`, plancher 0 ; null si réalisé). Rien dans le modèle.
- Vue : `<p class="echeance">` avec `<time>` (heure limite) et `<span class="echeance__reste" data-secondes data-fin>`. Le texte figé de minuit est dans `data-fin` : les textes restent dans la vue. Durée arrondie à la minute supérieure (« X h YY min »), même calcul en PHP et en JS. Le `<script>` n'est inclus qu'en cours.
- `echeance.js` décompte avec `performance.now()` (temps écoulé depuis le chargement), jamais `Date` ; `setTimeout` calé sur le changement de minute affichée. `TODO(simplif)` : un téléphone en veille peut suspendre `performance.now`.
- Vérifié dans le navigateur (Iris, duo 9, en cours ; Yann, réalisé) : texte sans JavaScript, décompte à la minute (horloge Playwright `clock.install` + `runFor`), aucune `aria-live`, rien ne bouge sous `reducedMotion: 'reduce'`, arrêt sur le texte figé avec `HORLOGE_DECALAGE` à 23 h 58 min 30 s, sans rechargement ; C3 réalisé sans temps restant ni script.

### Pièges

- Pour vérifier le décompte sans attendre : contexte Playwright neuf (`browser.newContext`, cookies isolés des autres sessions), `p.clock.install()` avant la navigation, puis `p.clock.runFor(ms)` ; l'horloge simulée couvre `performance.now` et `setTimeout`.
- `HORLOGE_DECALAGE` vit dans `config.php`, que l'agent ne touche pas : surcharge possible par un `auto_prepend_file` (dans un `.ini` désigné par `PHP_INI_SCAN_DIR`) qui définit la constante avant `config.php` ; celui-ci n'émet qu'un avertissement « already defined ». Attendre 2 s après chaque changement (opcache).
- `scripts/verifier.sh`, contrôle 3 (horloge SQL) : limité aux fichiers `.php` et `.sql`, sinon `performance.now()` d'`echeance.js` passait pour un `NOW()`.
- Le `config.php` du dossier principal n'a pas `CLE_SECRETE_TENTATIVES` (27/11/2026) : `tester.sh` échoue avant d'avoir donné sa base au worktree, dont le `config.php` copié vise alors la base principale. Ne lancer ni PHPUnit ni `reinitialiser.php` dans cet état sans surcharger `BDD_NOM` de la même façon.
