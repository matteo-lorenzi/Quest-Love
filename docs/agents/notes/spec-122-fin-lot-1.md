# Notes d'implémentation — spec #122, fin du lot 1

Une section par ticket (#123 à #126, puis #129). Chaque ticket y écrit sa ligne « Livré : … » ; le manifeste (doc technique § 5) est recoché par #129.

## Ticket #123 : G1, page d'erreur seule et vrai code HTTP

Livré : G1 (`app/controleurs/ErreurControleur.php`, `app/vues/ecrans/g1-erreur.php`), tampon de sortie de `Vue::rendre` et gestionnaire d'exceptions de `app/noyau/amorce.php`.

- `Vue::rendre` ouvre un tampon (`ob_start`) avant l'en-tête ; le gestionnaire d'exceptions de l'amorce journalise, ferme tous les tampons ouverts (`ob_end_clean` jusqu'au niveau 0, celui de php.ini compris), puis rend G1 en 500. Le tampon se vide tout seul à la fin du script : aucun `ob_end_flush` à écrire.
- Une 405 ne s'obtient pas par GET sur `deconnexion` sans session : le contrôle d'accès (étape 5) renvoie d'abord vers A3. Pour la voir en visiteur : POST avec un jeton valide (lu sur `?ecran=connexion`) sur `bienvenue`. Une 403 : POST avec un faux jeton.
- Exception en plein rendu, pour la vérification : une ligne `throw` ajoutée en fin de `a4-legal.php`, retirée ensuite. Sans le tampon, la page montrait deux `h1`, deux `main` et deux `<!DOCTYPE`.
- Le journal de `php -S` part sur la sortie d'erreur, que `scripts/servir.sh` jette : pour lire le journal, lancer `php -d error_log=<fichier> -S localhost:<port> -t app/public`.
- Vu dans le navigateur (Playwright, visiteur) : 403, 404, 405 et 500 avec leur code et un seul `h1` ; 500 sans détail technique, trace complète au journal ; jeton CSRF de la session inchangé après une 500. Non vu : « toujours connecté après une 500 » avec un compte, la connexion était bloquée dans ce worktree (`CLE_SECRETE_TENTATIVES` absente du `config.php` du dossier principal, ajoutée depuis).

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
- Un `config.php` sans `CLE_SECRETE_TENTATIVES` arrête `tester.sh` avant qu'il donne sa base au worktree : la copie vise alors la base principale. Ne lancer ni PHPUnit ni `reinitialiser.php` dans cet état ; supprimer la copie après avoir complété le `config.php` principal.
