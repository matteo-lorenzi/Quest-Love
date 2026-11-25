# Notes d'implémentation — spec #104, F1 Profil

## #105 — variante « duo activé »

- Route `profil` : `ProfilControleur`, `afficher` en GET, aucune méthode en POST (405), accès `Connecte`. `Routeur::ECRANS` est devenue publique pour que `RouteurTest` lise l'entrée d'un écran sans exécuter `traiter`.
- `ProfilControleur::elementsAffiches(EtapeAcces): array` renvoie `['carteDuo' => bool, 'barre' => bool, 'retour' => ?string]`. Seul le bras `AccesAuJeu` existe (`true, true, null`) : le `match` n'est pas exhaustif, chaque ticket ajoute ses bras et ses cas dans `ProfilTest`.
- `afficher` redirige toute autre étape vers `Routeur::ecranDeLEtape` (`TODO(simplif)` à retirer quand #106 et #107 auront leurs bras). En attendant, la flèche de B1 renvoie à B1 (son `TODO(simplif)` cite #106).
- `retour` est un nom d'écran (`ecran=…`). Pour #106 et #107, `elementsAffiches` peut appeler `Routeur::ecranDeLEtape($etape)` ; `ProfilTest` devra alors faire `require_once` de `app/noyau/Routeur.php` (sans dépendance au chargement, comme dans `RouteurTest`).
- La vue `f1-profil` reçoit `$pseudo`, `$identifiant`, `$carteDuo`, `$partenaire`, `$barre`, `$retour`. `$carteDuo` faux masque la ligne du duo et « Quitter mon duo » ; « Supprimer mon compte » reste. `$retour` non nul affiche la flèche ←.
- `ProfilVueTest` rend la vue sans gabarit ; il pose `$vue = 'f1-profil'` car `navigation.php` choisit l'onglet actif d'après `$vue` (posée par `Vue::rendre`). Pour #106 : ajouter un rendu avec `$carteDuo = false`, `$barre = false`, et vérifier l'absence de « Quitter mon duo ».
- Barre basse : onglets Accueil (`c1-accueil`) et Profil (`f1-profil`) ; un nouvel onglet est une ligne du tableau `$onglets`.
- Styles ajoutés à `composants.css` : `.texte-coupe` (« … » par la feuille de style, texte entier dans la page), `.liste-liens` (lignes de 44 px), `.zone-sensible`.
- Liens vers A4 (`legal`), F3 (`supprimer-compte`) et déconnexion vers A1 (`bienvenue`) : G1 (404), chacun avec un `TODO(simplif)` dans la vue.
- Vérifié dans le navigateur (`php -S localhost:8765`, compte `lea`, 375 px) : contenu, onglet Profil actif, coupe « … » sur un pseudo long, retour de B3 et annulation de F2 vers le profil, POST avec jeton → 405, déconnexion → G1 puis profil → connexion.
