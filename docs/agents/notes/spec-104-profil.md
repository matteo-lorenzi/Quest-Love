# Notes d'implémentation — spec #104, F1 Profil

## #105 — variante « duo activé »

- Route `profil` : `ProfilControleur`, `afficher` en GET, aucune méthode en POST (405), accès `Connecte`. `Routeur::ECRANS` est devenue publique pour que `RouteurTest` lise l'entrée d'un écran sans exécuter `traiter`.
- `ProfilControleur::elementsAffiches(EtapeAcces): array` renvoie `['carteDuo' => bool, 'barre' => bool, 'retour' => ?string]`. Le `match` couvre les quatre étapes (#105 `AccesAuJeu`, #106 `SansDuo`, #107 `OnboardingAFaire` et `Attente`), chacune testée dans `ProfilTest`.
- `afficher` redirigeait toute autre étape vers `Routeur::ecranDeLEtape` en attendant #106 et #107 ; les quatre étapes ont maintenant leur bras, le `match` est exhaustif et la redirection a disparu. La flèche de B1 mène au profil.
- `retour` est un nom d'écran (`ecran=…`). Pour #106 et #107, `elementsAffiches` peut appeler `Routeur::ecranDeLEtape($etape)` ; `ProfilTest` devra alors faire `require_once` de `app/noyau/Routeur.php` (sans dépendance au chargement, comme dans `RouteurTest`).
- La vue `f1-profil` reçoit `$pseudo`, `$identifiant`, `$carteDuo`, `$partenaire`, `$barre`, `$retour`. `$carteDuo` faux masque la ligne du duo et « Quitter mon duo » ; « Supprimer mon compte » reste. `$retour` non nul affiche la flèche ←.
- `ProfilVueTest` rend la vue sans gabarit ; il pose `$vue = 'f1-profil'` car `navigation.php` choisit l'onglet actif d'après `$vue` (posée par `Vue::rendre`). Pour #106 : ajouter un rendu avec `$carteDuo = false`, `$barre = false`, et vérifier l'absence de « Quitter mon duo ».
- Barre basse : onglets Accueil (`c1-accueil`) et Profil (`f1-profil`) ; un nouvel onglet est une ligne du tableau `$onglets`.
- Styles ajoutés à `composants.css` : `.texte-coupe` (« … » par la feuille de style, texte entier dans la page), `.liste-liens` (lignes de 44 px), `.zone-sensible`.
- Liens vers A4 (`legal`), F3 (`supprimer-compte`) et déconnexion vers A1 (`bienvenue`) : G1 (404), chacun avec un `TODO(simplif)` dans la vue.
- Vérifié dans le navigateur (`php -S localhost:8765`, compte `lea`, 375 px) : contenu, onglet Profil actif, coupe « … » sur un pseudo long, retour de B3 et annulation de F2 vers le profil, POST avec jeton → 405, déconnexion → G1 puis profil → connexion.

## #106 — variante « sans duo »

- Bras `SansDuo` de `elementsAffiches` : `['carteDuo' => false, 'barre' => false, 'retour' => Routeur::ecranDeLEtape($etape)]`, soit `salle-attente`. `ProfilTest` fait désormais `require_once` de `app/noyau/Routeur.php`.
- `afficher` ne redirige plus que `OnboardingAFaire` et `Attente` (`TODO(simplif)` réduit à #107). La flèche de B1 mène au profil, son `TODO(simplif)` est levé.
- La vue n'a pas changé : `$carteDuo = false` masquait déjà la ligne du duo et « Quitter mon duo ». `ProfilVueTest::rendre(sansDuo: true)` couvre cette variante (pas de duo, pas de barre, « Supprimer mon compte », flèche vers `salle-attente`).
- Vérifié dans le navigateur (`php -S localhost:8771`, compte `sylvianne`, 375 px) : flèche de B1 → profil sans ligne du duo, sans « Quitter mon duo », sans barre basse, avec « Supprimer mon compte » ; flèche ← → B1.

## #107 — variante « duo formé, pas encore activé »

- Bras `OnboardingAFaire, Attente` de `elementsAffiches` : `['carteDuo' => true, 'barre' => false, 'retour' => Routeur::ecranDeLEtape($etape)]`, soit `onboarding` pour les deux ; B2 choisit lui-même sa variante d'attente. `ProfilTest` fait désormais `require_once` de `app/noyau/Routeur.php`.
- Aucune retouche de la vue : `$barre` faux masque déjà la barre, `$retour` affiche la flèche.
- Le jeu de démonstration n'a aucun duo formé pas encore activé. Pour vérifier : `UPDATE joueur SET onboarding_vu = 0 WHERE identifiant = 'lea'` après `tester.sh`, puis Léa montre « onboarding à faire » et Tom « attente » ; recharger la base ensuite.
- Vérifié dans le navigateur (`php -S localhost:8767`, 375 px) : Léa et Tom voient « Tu es en duo avec … », « Quitter mon duo », aucune barre, la flèche ← mène à B2 (règles pour Léa, variante d'attente pour Tom).
