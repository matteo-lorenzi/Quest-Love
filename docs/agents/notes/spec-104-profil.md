# Notes d'implémentation — spec #104, F1 Profil

## #105 — variante « duo activé »

- Route `profil` : `ProfilControleur`, `afficher` en GET, aucune méthode en POST (405), accès `Connecte`. `Routeur::ECRANS` est devenue publique pour que `RouteurTest` lise l'entrée d'un écran sans exécuter `traiter`.
- `ProfilControleur::elementsAffiches(EtapeAcces): array` renvoie `['carteDuo' => bool, 'barre' => bool, 'retour' => ?string]`. Le `match` couvre les quatre étapes (#105 `AccesAuJeu`, #106 `SansDuo`, #107 `OnboardingAFaire` et `Attente`), chacune testée dans `ProfilTest`.
- `afficher` ne redirige aucune étape : les quatre ont leur bras dans `elementsAffiches` (le `match` est exhaustif).
- `retour` est un nom d'écran (`ecran=…`), donné par `Routeur::ecranDeLEtape($etape)` pour #106 et #107 ; `ProfilTest` fait donc `require_once` de `app/noyau/Routeur.php` (sans dépendance au chargement, comme dans `RouteurTest`).
- La vue `f1-profil` reçoit `$pseudo`, `$identifiant`, `$carteDuo`, `$partenaire`, `$barre`, `$retour`. `$carteDuo` faux masque la ligne du duo et « Quitter mon duo » ; « Supprimer mon compte » reste. `$retour` non nul affiche la flèche ←.
- `ProfilVueTest` rend la vue sans gabarit ; il pose `$vue = 'f1-profil'` car `navigation.php` choisit l'onglet actif d'après `$vue` (posée par `Vue::rendre`).
- Barre basse : onglets Accueil (`c1-accueil`) et Profil (`f1-profil`) ; un nouvel onglet est une ligne du tableau `$onglets`.
- Styles ajoutés à `composants.css` : `.texte-coupe` (« … » par la feuille de style, texte entier dans la page), `.liste-liens` (lignes de 44 px), `.zone-sensible` (ses liens `zone-sensible__lien`, même règle que `liste-liens__lien`), `.profil__libelle` (libellé de l'identifiant, même règle que `liste-liens__precision`), `.entete-ecran__retour` (flèche ← de 44 × 44 px, vaut pour tous les écrans qui la portent).
- `afficher` passe les éléments de `elementsAffiches` à la vue par `...$elements` : une clé ajoutée au `match` arrive dans la vue sans retouche du contrôleur.
- Le 405 sur `POST profil` n'a pas de test qui exécute `Routeur::traiter` : celui-ci émet ses en-têtes et termine le script (`exit`), ce qui arrêterait PHPUnit. `RouteurTest` vérifie donc l'entrée de la table (aucune méthode POST, donc G1 405 à l'étape 6) et l'accès par `redirectionAcces` ; le 405 est vérifié dans le navigateur.
- Liens vers A4 (`legal`), F3 (`supprimer-compte`) et déconnexion vers A1 (`bienvenue`) : G1 (404), chacun avec un `TODO(simplif)` dans la vue.
- Vérifié dans le navigateur (`php -S localhost:8765`, compte `lea`, 375 px) : contenu, onglet Profil actif, coupe « … » sur un pseudo long, retour de B3 et annulation de F2 vers le profil, POST avec jeton → 405, déconnexion → G1 puis profil → connexion.

## #106 — variante « sans duo »

- Bras `SansDuo` de `elementsAffiches` : `['carteDuo' => false, 'barre' => false, 'retour' => Routeur::ecranDeLEtape($etape)]`, soit `salle-attente`.
- La flèche de B1 mène au profil, son `TODO(simplif)` est levé.
- La vue n'a pas changé : `$carteDuo = false` masquait déjà la ligne du duo et « Quitter mon duo ». `ProfilVueTest::rendre(sansDuo: true)` couvre cette variante (pas de duo, pas de barre, « Supprimer mon compte », flèche vers `salle-attente`).
- Vérifié dans le navigateur (`php -S localhost:8771`, compte `sylvianne`, 375 px) : flèche de B1 → profil sans ligne du duo, sans « Quitter mon duo », sans barre basse, avec « Supprimer mon compte » ; flèche ← → B1.

## #107 — variante « duo formé, pas encore activé »

- Bras `OnboardingAFaire, Attente` de `elementsAffiches` : `['carteDuo' => true, 'barre' => false, 'retour' => Routeur::ecranDeLEtape($etape)]`, soit `onboarding` pour les deux ; B2 choisit lui-même sa variante d'attente.
- Aucune retouche de la vue : `$barre` faux masque déjà la barre, `$retour` affiche la flèche.
- Le jeu de démonstration n'a aucun duo formé pas encore activé. Pour vérifier : `UPDATE joueur SET onboarding_vu = 0 WHERE identifiant = 'lea'` après `tester.sh`, puis Léa montre « onboarding à faire » et Tom « attente » ; recharger la base ensuite.
- Vérifié dans le navigateur (`php -S localhost:8767`, 375 px) : Léa et Tom voient « Tu es en duo avec … », « Quitter mon duo », aucune barre, la flèche ← mène à B2 (règles pour Léa, variante d'attente pour Tom).

## #108 — documents de référence

- Alignés sur F1 livré : doc fonctionnelle (contenu de F1, trois variantes), inventaire des écrans (trois cadres F1, dont « duo sans accès au jeu », nœud Figma `146:146`), doc technique (§ 3.6 barre masquée sur F1 hors accès au jeu, § 4.1 et manifeste : onglets Accueil et Profil, case F1 cochée), README (F1 fait, 14 écrans sur 17).
- Le cadre « duo sans accès au jeu » porte le zonage à 28 cadres : le compte est repris dans l'inventaire, la doc fonctionnelle et le README. Les numéros de version des documents n'ont pas été changés.
- Restent en `TODO(simplif)` dans le code après F1 : les liens vers A4 (`legal`), F3 (`supprimer-compte`) et la redirection de la déconnexion vers A1 (`bienvenue`), qui mènent à G1 (404) jusqu'à leurs lots ; Gages et Historique dans `navigation.php` (lot 2).
