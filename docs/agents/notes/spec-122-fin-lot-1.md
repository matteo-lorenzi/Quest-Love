# Notes d'implémentation — spec #122, fin du lot 1

Une section par ticket (#123 à #126, puis #129). Chaque ticket y écrit sa ligne « Livré : … » ; le manifeste (doc technique § 5) est recoché par #129.

## Ticket #123 : G1, page d'erreur seule et vrai code HTTP

Livré : G1 (`app/controleurs/ErreurControleur.php`, `app/vues/ecrans/g1-erreur.php`), tampon de sortie de `Vue::rendre` et gestionnaire d'exceptions de `app/noyau/amorce.php`.

- `Vue::rendre` ouvre un tampon (`ob_start`) avant l'en-tête ; le gestionnaire d'exceptions de l'amorce journalise, ferme tous les tampons ouverts (`Vue::viderTampons` : `ob_end_clean` jusqu'au niveau 0, celui de php.ini compris), puis rend G1 en 500. Le catch d'`ErreurHttp` de `public/index.php` les vide aussi (revue de la branche). Le tampon se vide tout seul à la fin du script : aucun `ob_end_flush` à écrire.
- Une 405 ne s'obtient pas par GET sur `deconnexion` sans session : le contrôle d'accès (étape 5) renvoie d'abord vers A3. Pour la voir en visiteur : POST avec un jeton valide (lu sur `?ecran=connexion`) sur `bienvenue`. Une 403 : POST avec un faux jeton.
- Exception en plein rendu, pour la vérification : une ligne `throw` ajoutée en fin de `a4-legal.php`, retirée ensuite. Sans le tampon, la page montrait deux `h1`, deux `main` et deux `<!DOCTYPE`.
- Le journal de `php -S` part sur la sortie d'erreur, que `scripts/servir.sh` jette : pour lire le journal, lancer `php -d error_log=<fichier> -S localhost:<port> -t app/public`.
- Vu dans le navigateur (Playwright, visiteur) : 403, 404, 405 et 500 avec leur code et un seul `h1` ; 500 sans détail technique, trace complète au journal ; jeton CSRF de la session inchangé après une 500. « Toujours connecté après une 500 » avec un compte : vu à la revue de la branche (section ci-dessous).

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

## Ticket #125 : bibliothèque d'environ cinquante défis

- `sql/defis.sql` : cinquante défis, Attention 13, Complicité 12, Action 13, Expression 12. Les quinze premiers gardent leurs numéros 1 à 15 (le jeu de démonstration y renvoie) ; les numéros 16 à 50 sont un premier jet de l'agent, à valider, corriger ou remplacer par le binôme dans la relecture de la PR. Un défi ajouté plus tard se place en fin de liste, pour ne décaler aucun numéro.
- `tests/SchemaTest.php` : le test du rechargement attend désormais 45 à 55 défis et 10 à 14 par catégorie (il attendait 12 à 18 défis). Pas d'autre test de la couche modèle (spec, Testing Decisions).
- Manifeste (doc technique § 5, ligne de `sql/defis.sql`) laissé tel quel pour #129 : sa mention « en cours : jeu provisoire » est devenue fausse.
- Livré : `sql/defis.sql` (bibliothèque d'environ cinquante défis en quatre catégories).

## Ticket #126 : démonstration (attente de B2, D3 à la connexion, historique de Léa et Tom)

- `sql/demonstration.sql` : duo 11 (Chloé `chloe` onboarding fini, Enzo `enzo` non, `date_activation` nulle) ; Marc `marc`, sans duo, `dissolution_a_signaler = 1` ; trois jours d'historique du duo 1 (tirages 9 à 14, défis 1 à 15 seulement).
- Léa et Tom ont désormais des numéros fixés (3 et 4) et 4 PV : un défi expiré chacun dans l'historique. Rien n'est écrit pour aujourd'hui : C1 reste à l'état 1 (avant 12 h).
- Missions d'historique en statut terminal (`valide`, `expire`) : le rattrapage n'y touche pas, aucun D1 à leur connexion.
- D3 de Marc ne se montre qu'une fois : recharger la base pour le revoir.
- Vérifié dans le navigateur (`php -S 127.0.0.3`, 375 px, un contexte neuf par compte, après 18 h) : Chloé arrive sur l'attente de B2 (« En attente de Enzo ») et ouvre F1 ; Enzo sur l'onboarding (1 / 3) ; Marc sur D3 puis B1. Léa, après 12 h, lit la journée blanche « n'avait pas répondu à 12 h » : l'état 1 ne se voit qu'avant 12 h, comme pour tout duo sans réponse.
- Prochain numéro libre : duo 12, joueur 121, tirage 15.
- Livré : `sql/demonstration.sql` (duo 11, Marc, historique de Léa et Tom), doc technique § 6.5.

## Ticket #129 : clôture du lot 1, manifeste recoché et recette technique

- Manifeste (doc technique § 5.1) recoché d'après les lignes « Livré : » : `sql/defis.sql` (#125), A4 (#120, note de la spec #120), `echeance.js` (#124), G1 (#123), `sql/demonstration.sql` (#126). Restent non cochés, avec leur raison : feuilles de style (`ecrans.css` absent, CSS hors de la spec), `debug.php` sur Azrael (binôme, #128).
- Recette (§ 6.6) : les quatre cases _(test)_ cochées avec le nom du test ; dissolution et JavaScript désactivé cochées ; démonstration sur deux téléphones, parcours au clavier et préparation du gel laissés, raison écrite sur la case.
- Parcours JavaScript désactivé (contexte Playwright neuf, `javaScriptEnabled: false`, 375 px, `php -S 127.0.0.1`, `HORLOGE_DECALAGE` à `+14 hours` puis `+30 hours` par `auto_prepend_file`) : A1, A2, B1 (code), second compte qui saisit le code, B2 trois écrans puis attente (« recharge cette page »), C1 état 1, oui des deux, C2 par liens `&carte=` et formulaire « Attribuer », C1 états 4 et 5, C3 avec temps restant en texte, panneau de confirmation par lien `&confirmer=realise`, C3 réalisé, C1 état 6 ; après minuit D1 puis C1 état 1 ; F2 puis D3 chez le partenaire et B1 ; F1, A4 (mentions, confidentialité) et une 404 en G1.
- Sans JavaScript, B1 et l'attente de B2 ne se mettent pas à jour seules : le texte demande de recharger, et un rechargement suffit.
- Livré : doc technique § 5.1 (manifeste du lot 1) et § 6.6 (recette).

## Revue de la branche

- `sql/demonstration.sql` : commentaires de l'historique de Léa et Tom alignés sur les données, qui ne changent pas (4 PV chacun) : J-3 les deux validés ; J-2 Tom valide, Léa laisse expirer ; J-1 Léa valide, Tom laisse expirer.
- `public/index.php` : le catch d'`ErreurHttp` vide les tampons avant G1 ; sans cela, une `ErreurHttp` levée en plein rendu mêlait la page interrompue et G1. Les trois boucles (`index.php`, deux dans `amorce.php`) passent par `Vue::viderTampons()`. Doc technique § 2.2, étape 7, mise à jour.
- Vérifié dans le navigateur (`php -S 127.0.0.1`, contextes Playwright neufs avec le cookie de session de Léa, `throw` temporaire en fin de `a4-legal.php`, retiré ensuite) : `ErreurHttp(404)` en plein rendu, 404 avec un seul `h1`, `main` et `<!DOCTYPE` ; `RuntimeException`, 500 « Un souci de notre côté », un seul de chaque, aucun détail technique ; juste après, `accueil` répond 200 sur C1 : Léa est toujours connectée (critère du #123).
- Pour garder une session d'un appel Playwright à l'autre (`browser_run_code_unsafe` ne garde aucune variable) : lire `PHPSESSID` par `ctx.storageState()` à la connexion, puis `ctx.addCookies` dans chaque contexte neuf.
