# Pièges des vues — à lire avant de modifier app/vues/

Rappels courts. La règle fait foi dans `04-technique/doc-technique.md` (§ 2.4, 4.1 à 4.5) et le cadrage § 9 pour l'accessibilité ; `bash scripts/verifier.sh` attrape les cas marqués (V).

## Structure

- `Vue::rendre($vue, $titre, $donnees)` enchaîne `gabarits/entete.php`, l'écran, `gabarits/pied.php`. Une vue d'écran ne rouvre ni ne ferme le document.
- Les données arrivent en variables (`extract`, sans écraser `$vue`, `$titre`, `$jeton`). Le haut de chaque vue liste ce qu'elle reçoit de son contrôleur ; le tenir à jour.
- `$jeton` est déjà là pour les formulaires : une vue n'appelle pas `Securite`.
- Un composant (`carte-defi`, `socle-c1`, `panneau-confirmation`, `bloc-evenement`) se modifie à sa source, jamais en copie dans un écran (§ 4.2).
- `navigation.php` : seulement les écrans qui la portent, jamais un sous-écran focalisé ni un événement.

## Sortie

- Tout texte libre (pseudos, gages) passe par `e()` à la sortie. Une valeur écrite sans elle est une faille.
- Aucun SQL ni règle du jeu dans une vue, et aucune lecture de l'horloge (V pour ce dernier point). L'échéance affichée est du texte statique calculé par le serveur ; `echeance.js` ne fait que décompter.
- Une valeur de PV dans un texte vient de sa constante (`PointsDeVieService::PENALITE`, `Joueur::PV_DEPART`), jamais écrite en dur. (V)
- Textes d'interface : `[TEXTE À RÉDIGER : …]` tant qu'ils ne sont pas rédigés en S6 ; ne pas les improviser.

## Formulaires et JavaScript

- Tout formulaire `POST` porte le champ caché `jeton`. Un `GET` ne fait jamais d'écriture : un lien suffit pour lire.
- L'écran doit fonctionner sans JavaScript : cartes en liens, confirmation en formulaire. Le script n'ajoute que l'animation, et rien sous `prefers-reduced-motion` (§ 4.3).

## HTML et accessibilité

- HTML sémantique avant `div` ; un seul `main` par page ; titres sans saut de niveau ; classes BEM français (`carte-defi__titre`, `carte-defi--realise`).
- Cible tactile d'au moins 44 px, unités relatives, erreur de formulaire en texte à côté du champ (§ 4.4).
- Style : un composant va dans `composants.css` ; `ecrans.css` doit rester le plus petit fichier, s'il grossit un composant manque (§ 4.5).
