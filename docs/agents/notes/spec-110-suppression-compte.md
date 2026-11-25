# Notes d'implémentation — #110, F3 Suppression de compte

## Ce qui est livré

- Route `supprimer-compte` : `SupprimerCompteControleur`, `afficher` en GET, `supprimer` en POST, accès `Connecte` (hors verrou du jeu, comme le profil). `RouteurTest` vérifie l'entrée et l'accès dans les quatre étapes.
- Vue `f3-supprimer-compte` : reçoit `$partenaire` (pseudo ou null). Retour ← et « Annuler » fixes vers `profil`, sans paramètre d'origine (F3 ne s'ouvre que depuis F1). Construite comme F2, sans barre basse. Testée par `SupprimerCompteVueTest`.
- `AppairageService::supprimerCompte(Joueur, $idSession, $maintenant): bool` : appelle `dissoudre`, puis une seconde transaction avec `JoueurDepot::supprimerSansDuo` et `LimiteTentativesService::oublier`. Elle vit dans `AppairageService` et non dans `CompteService` parce qu'elle réutilise `dissoudre` et a besoin de la connexion pour sa transaction.
- `supprimerSansDuo` ne supprime la ligne que si `duo_id IS NULL`. Si un duo a été reformé depuis un autre appareil entre les deux transactions, rien n'est supprimé : le service rend faux et le contrôleur réaffiche F3, sans message. Sans ce garde-fou, le partenaire resterait seul dans un duo.
- `oublier` supprime chaque clé avec son type : identifiant et session pour `connexion`, numéro de joueur et session pour `code`. Un identifiant « 42 » n'efface donc pas les essais de code du joueur n° 42. Les clés IP et `inscription` restent (décision du ticket).
- Après la suppression : session vidée, identifiant régénéré, redirection vers `bienvenue` (G1 404, `TODO(simplif)` jusqu'à A1).
- Vérifié dans le navigateur (`php -S localhost:8772`, 375 px) : visiteur renvoyé vers la connexion ; `lea` voit « Ton duo avec Tom sera aussi dissous », « Annuler » mène au profil, la confirmation mène à `bienvenue` et ferme la session ; en base, Tom est sans duo, à 5 PV, signalé ; à sa connexion il voit D3 ; F3 de Tom, désormais sans duo, n'a pas la phrase sur le duo.

## À savoir

- L'empreinte de session effacée est celle de la session en cours. La connexion régénère l'identifiant de session : les essais de connexion faits avant le login portent une ancienne empreinte et restent jusqu'à la purge (au plus 30 minutes après la prochaine tentative ou suppression de compte). C'est conforme au ticket, pas un oubli.
- Le test de la course (duo reformé) neutralise `dissoudre` par une classe anonyme qui hérite de `AppairageService` : `dissoudre` ne doit donc pas devenir `final`.

## Améliorations futures

- Sessions stockées en base : fermer toutes les sessions d'un joueur à la suppression de son compte. Aujourd'hui, une session ouverte sur un autre appareil reste jusqu'à sa prochaine requête (qui renvoie vers la connexion) ou au nettoyage de PHP.
- Purge par tâche planifiée, pour garantir les 30 minutes même sans nouvelle tentative ni suppression.

## Rappel pour A4

- La politique de confidentialité doit citer les journaux du serveur et les sauvegardes de l'hébergeur, avec leurs durées de conservation : la suppression de compte n'y touche pas.
