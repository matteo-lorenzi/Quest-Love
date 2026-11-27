# Notes d'implémentation — spec #122, fin du lot 1

Une section par ticket (#123 à #126, puis #129). Chaque ticket y écrit sa ligne « Livré : … » ; le manifeste (doc technique § 5) est recoché par #129.

## Ticket #123 : G1, page d'erreur seule et vrai code HTTP

Livré : G1 (`app/controleurs/ErreurControleur.php`, `app/vues/ecrans/g1-erreur.php`), tampon de sortie de `Vue::rendre` et gestionnaire d'exceptions de `app/noyau/amorce.php`.

- `Vue::rendre` ouvre un tampon (`ob_start`) avant l'en-tête ; le gestionnaire d'exceptions de l'amorce journalise, ferme tous les tampons ouverts (`ob_end_clean` jusqu'au niveau 0, celui de php.ini compris), puis rend G1 en 500. Le tampon se vide tout seul à la fin du script : aucun `ob_end_flush` à écrire.
- Une 405 ne s'obtient pas par GET sur `deconnexion` sans session : le contrôle d'accès (étape 5) renvoie d'abord vers A3. Pour la voir en visiteur : POST avec un jeton valide (lu sur `?ecran=connexion`) sur `bienvenue`. Une 403 : POST avec un faux jeton.
- Exception en plein rendu, pour la vérification : une ligne `throw` ajoutée en fin de `a4-legal.php`, retirée ensuite. Sans le tampon, la page montrait deux `h1`, deux `main` et deux `<!DOCTYPE`.
- Le journal de `php -S` part sur la sortie d'erreur, que `scripts/servir.sh` jette : pour lire le journal, lancer `php -d error_log=<fichier> -S localhost:<port> -t app/public`.
- Vu dans le navigateur (Playwright, visiteur) : 403, 404, 405 et 500 avec leur code et un seul `h1` ; 500 sans détail technique, trace complète au journal ; jeton CSRF de la session inchangé après une 500. Non vu : « toujours connecté après une 500 » avec un compte, la connexion étant bloquée dans ce worktree (`CLE_SECRETE_TENTATIVES` absente du `config.php` du dossier principal, à ajouter par le binôme).
