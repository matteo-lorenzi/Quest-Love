# Une redirection ou une erreur HTTP sort du routeur par exception

`Routeur::rediriger` et `Routeur::erreur` terminaient le processus avec `exit`. Aucun test ne pouvait donc appeler un contrôleur qui redirige : le test s'arrêtait avec lui, et l'ordre « faire X, puis rediriger » restait invérifiable. Cas qui l'a révélé : A1 garde le code d'invitation avant de renvoyer un joueur connecté (cadrage § 6.3) ; inverser les deux lignes laissait tous les tests verts et faisait perdre le code prérempli en B1 (revue de la PR #116).

`rediriger()` lève maintenant `Redirection`, qui porte l'écran cible ; `erreur()` lève `ErreurHttp`, qui porte le code HTTP. `app/public/index.php` attrape les deux, envoie l'en-tête `Location` ou affiche G1, puis termine : c'est le seul `exit` du cycle d'une requête. Les appels de `rediriger` dans le routeur et les contrôleurs, et le rôle de chaque couche, ne changent pas ([ADR-0001](0001-controleur-frontal-mvc.md)) : la décision déplace seulement l'endroit où le processus s'arrête, du routeur au contrôleur frontal.

## Considered Options

- **`exit` conservé, contrôleurs testés par un processus PHP séparé** : rejetée. Un test par lancement de processus est lent, ne rend pas la session lisible au test et sort de l'outil du projet (PHPUnit sur la couche appelée directement).
- **`rediriger()` rend une valeur que le contrôleur renvoie (`return Routeur::rediriger(...)`)** : rejetée. Chaque appel (29 aujourd'hui) devrait être suivi d'un `return`, et un oubli laisserait la requête continuer sans que rien ne le signale. `never` garantit l'inverse.
- **Exception (`Redirection`, `ErreurHttp`) attrapée dans `index.php`** : retenue. Les appels ne changent pas, `never` reste vrai, et un test attend l'exception comme n'importe quel résultat.

## Consequences

- Risque : une exception **avalée**. Un `catch (Throwable)` ou `catch (Exception)` placé autour d'un appel qui redirige l'arrêterait, et la requête continuerait après « rediriger ». `scripts/verifier.sh` refuse ces deux `catch` dans `app/controleurs/` et `app/noyau/`. Les `catch (Throwable)` des services (annulation de transaction) restent permis : un service ne redirige pas. `app/noyau/amorce.php` est exclu du contrôle : son `catch (Throwable)` est le dernier recours du gestionnaire d'exceptions, qui n'appelle ni `rediriger` ni `erreur`.
- Le contrôle ne lit que la ligne du `catch` (types multiples compris) : un `catch (PDOException)` ou un `finally` ne porte pas ce risque, mais un `catch` dont les types sont écrits sur plusieurs lignes lui échappe.
- Une exception levée dans un service, sous un `catch (Throwable)` de transaction, serait avalée : un service ne doit donc jamais appeler `rediriger`.
- Un contrôleur se teste en l'appelant avec `$_SESSION` et `$_GET` posés : le test attend `Redirection` (ou `ErreurHttp`) et lit ensuite l'état laissé avant la sortie (premier cas : `BienvenueControleurTest`).
- Toute autre exception (bogue, base injoignable) n'est pas attrapée par `index.php` : elle va au gestionnaire d'`amorce.php`, qui journalise et affiche G1 en 500, comme avant.
- Le comportement vu du navigateur ne change pas : en-tête `Location` pour une redirection, G1 en 404, 403 ou 405 pour une erreur.
