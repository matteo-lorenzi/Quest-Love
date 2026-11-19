# Vérifier un écran dans le navigateur

Les tests ne couvrent que la couche modèle (doc technique § 6.4) : un écran livré se regarde dans le navigateur avant de rendre la main.

1. `bash scripts/tester.sh` : il laisse la base rechargée avec le jeu de démonstration. Sinon, `php sql/reinitialiser.php`.
2. Servir l'application. Dossier principal : `http://quest-love.local/` (WAMP). Worktree : `quest-love.local` sert le dossier principal, lancer `php -S localhost:8765 -t app/public` dans le worktree.
3. Choisir le compte : chaque groupe de `sql/demonstration.sql` dit en commentaire quel écran et quelle variante il montre. Mot de passe commun : `Quest-Love-demo-2026!`.
4. Ouvrir l'écran avec les outils du MCP `playwright` (navigation, lecture de la page). Le jeton CSRF se lit sur `?ecran=connexion` ; les pages sans formulaire n'en ont pas.
5. Rendre compte de ce qui a été vu (écran, compte, variante). Une connexion consomme l'état de démonstration (un événement ne s'affiche qu'une fois) : recharger la base pour rejouer.

Pièges :
- Après une modification de `config.php`, attendre 2 s (`opcache.revalidate_freq`).
- Le premier rattrapage a lieu à la requête qui suit le POST de connexion (redirection vers l'accueil, puis `evenement`).
