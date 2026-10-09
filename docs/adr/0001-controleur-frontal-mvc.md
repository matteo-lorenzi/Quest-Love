# Contrôleur frontal MVC plutôt qu'une page PHP par écran

La description technique V1.0 prévoyait une page PHP par écran à la racine, avec des modules de fonctions dans `src/`. Après le retour de l'enseignant (octobre 2026) demandant une architecture MVC « pensée entité et vue », l'application passe à un contrôleur frontal : `app/public/index.php` est le seul fichier joignable par une URL, il lit `?ecran=`, et une table de routage associe chaque écran à son contrôleur et à son code d'inventaire (A1, C2…). Contrôleurs, modèles (entités, dépôts, services), vues et configuration vivent hors de `public/`.

## Considered Options

- **Une page par écran (V1.0)** : pas de routeur à écrire, URL lisibles. Rejetée : l'amorce, le rattrapage, le CSRF et les contrôles d'accès se répètent en tête de chaque page, et chaque dossier interne doit être protégé par un `.htaccess`.
- **Contrôleur frontal** : retenu. Un seul point d'entrée qui applique une fois pour toutes l'amorce, le rattrapage paresseux, le CSRF et les préconditions d'accès ; forme du MVC immédiatement reconnaissable par le jury.

## Consequences

- URL de la forme `index.php?ecran=accueil` : pas de réécriture d'URL au lot 1, la disponibilité de `mod_rewrite` sur Azrael n'étant pas connue.
- Si Azrael ne permet pas de faire de `app/public/` la racine du site, un `.htaccess` à la racine de `app/` redirige vers `public/`.
- Le dossier `modeles/` change de sens : il contenait les gabarits (désormais `vues/`), il contient maintenant les entités, les dépôts et les services.
