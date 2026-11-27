# Notes d'implémentation — #120, A4 Pages légales

## Ce qui est livré

- Route `legal` : `LegalControleur::afficher`, GET seulement (un POST reçoit G1 405), accès public, sans lecture de la base.
- `&page=` validé contre `LegalControleur::PAGES` (clé => titre, liste fermée dans le contrôleur, pas d'énumération dans le modèle : ce n'est pas une notion du jeu). Absent, inconnu ou tableau : `mentions`.
- Vue `a4-legal` : reçoit `$page` et `$pages` ; titre de la page en `h1` (le `$titre` de `Vue::rendre`), navigation entre les trois pages (`aria-current` sur la page courante), une `section` par rubrique du cadrage § 10. Les rubriques sont dans la vue, chacune en `[TEXTE À RÉDIGER : …]` qui dit ce qu'elle doit contenir (livrable éditorial S6). La confidentialité nomme les journaux du serveur et les sauvegardes de l'hébergeur (rappel de la note #110).
- Retour ← vers `bienvenue` : un visiteur revient à A1, un joueur connecté est renvoyé par A1 vers l'écran de son étape d'accès (pas vers F1 s'il venait du profil).
- A2 : mention des conditions d'utilisation, lien vers `&page=conditions`, sans case (cadrage § 10). Phrase en `[TEXTE À RÉDIGER]` : le cadrage fixe le mécanisme, pas le texte (rétro).
- Contrôle 12 de `verifier.sh` (rétro) : un `TODO(simplif): écran \`cle\` absent` échoue dès que `cle` est dans `Routeur::ECRANS`. Les anciens TODO d'écran manquant n'avaient pas de forme fixe ; il n'en reste aucun.
- `TODO(simplif)` levés : lien légal de A1, lien légal de F1, et ceux, périmés, de la déconnexion de F1 et de la redirection de F3 (A1 existe depuis #114).
- Tests : `LegalTest` (validation de la page, entrée du routeur, rendu des trois pages).
- Vérifié dans le navigateur (`bash scripts/servir.sh`, 375 px, visiteur) : les trois pages, `&page=x` qui affiche les mentions, le lien de A2.
- Pas de style : classes `legal` et `legal__pages` sans règle CSS, à reprendre avec le visuel.

## À savoir

- Rédaction des textes : directeur de publication, adresse de l'hébergeur et durées de conservation des journaux et sauvegardes d'Azrael sont à demander (université) ; rien ne les donne dans le dépôt.

- Livré : A4 `LegalControleur` et `a4-legal.php`.
