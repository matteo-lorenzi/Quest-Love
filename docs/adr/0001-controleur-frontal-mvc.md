# Contrôleur frontal MVC plutôt qu'une page PHP par écran

La description technique V1.0 prévoyait une page PHP par écran à la racine, avec des modules de fonctions dans `src/`. Après le retour de l'enseignant (octobre 2026) demandant une architecture MVC « pensée entité et vue », l'application passe à un contrôleur frontal : `app/public/index.php` est le seul fichier joignable par une URL, il lit `?ecran=`, et une table de routage associe chaque écran à son contrôleur et à son code d'inventaire (A1, C2…). Contrôleurs, modèles (entités, dépôts, services), vues et configuration vivent hors de `public/`.

## Considered Options

- **Une page par écran (V1.0)** : pas de routeur à écrire, URL lisibles. Rejetée : l'amorce, le rattrapage, le CSRF et les contrôles d'accès se répètent en tête de chaque page, et chaque dossier interne doit être protégé par un `.htaccess`.
- **Contrôleur frontal** : retenu. Un seul point d'entrée qui applique une fois pour toutes l'amorce, le rattrapage paresseux, le CSRF et les préconditions d'accès ; forme du MVC immédiatement reconnaissable par le jury.
- **Framework MVC (Laravel, Symfony…)** : rejeté. Exclu par les contraintes du cours (aucune bibliothèque, aucun gestionnaire de dépendances) ; c'est aussi la réserve exprimée par l'enseignant le 12/10/2026.
- **Active record ou ORM maison (`$mission->valider()` qui écrit en base)** : rejeté. C'est un ORM déguisé, et il disperserait les transactions et les verrous qui doivent rester groupés dans `RattrapageService`.

## Un MVC sans framework (précision du 14/10/2026)

En réponse à la réserve de l'enseignant (courriel du 12/10/2026 : les MVC s'accompagnent souvent d'un framework, d'un ORM ou d'un moteur de gabarits qui éloignent des bonnes pratiques du web), le MVC de Quest & Love est **une convention de rangement**, pas un outil :

- **aucun framework, aucun ORM, aucun moteur de gabarits** : PHP natif sert de langage de gabarit, et le SQL est écrit à la main, en requêtes préparées, dans les dépôts ;
- **entités en lecture seule** : elles transportent des données entre dépôts, services et vues, et ne lisent ni n'écrivent jamais en base ;
- **une classe par écran, sans classe mère**, pas de conteneur d'injection : les dépendances (connexion PDO, dépôts, services, `$maintenant`) sont créées et passées explicitement, par `index.php` et le routeur ;
- **garde-fou** : chaque fichier doit pouvoir être expliqué ligne par ligne par l'un ou l'autre membre du binôme ; ce qui ne passe pas ce test est simplifié (description technique § 2.4).

## Consequences

- URL de la forme `index.php?ecran=accueil` : pas de réécriture d'URL au lot 1. Les `.htaccess` sont pris en compte sur Azrael (courriel du 12/10), ce qui laisse la réécriture possible plus tard sans toucher la table de routage.
- Si Azrael ne permet pas de faire de `app/public/` la racine du site, un `.htaccess` à la racine de `app/` redirige vers `public/`.
- Le dossier `modeles/` change de sens : il contenait les gabarits (désormais `vues/`), il contient maintenant les entités, les dépôts et les services.
- Coût d'apprentissage pour qui découvre le MVC : le contrôleur frontal, le routeur et l'écran de référence A3 sont écrits en premier et annotés étape par étape, puis réexpliqués par chacun des deux membres avant d'écrire le deuxième écran.
