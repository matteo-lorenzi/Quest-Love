# Notes d'implémentation — spec #113, A1 écran d'entrée et entrée par invitation

## Ticket #114 : écran d'entrée du visiteur (vue sans code reçu)

- `BienvenueControleur::afficher` (route `bienvenue`, GET seulement, accès public). Ordre : `CodeRecu::conserver()` d'abord, puis redirection d'un joueur connecté vers `Routeur::ecranDeLEtape(etapeAcces(...))`, enfin la vue. Le code est gardé avant la redirection pour qu'un joueur connecté sans duo le retrouve prérempli en B1 (cadrage § 6.3). Un visiteur ne déclenche aucune lecture en base.
- Vue `a1-bienvenue.php` : reçoit `$codeRecu` (null ou chaîne). La variante « entrée par invitation » (titre et accroche) s'affiche déjà dès qu'il est non nul ; le code n'est jamais rendu. Le texte vient du pitch du cadrage § 1 : le cadre Figma A1 est un zonage sans texte (blocs gris et étiquettes), sa note de variante n'en donne pas non plus.
- `TODO(simplif)` levés : celui de `Routeur::ECRAN_PAR_DEFAUT` (404 sans paramètre) et celui de `CodeRecu::conserver` (entrée du lien sur A2 et A3). Reste connu : un lien ouvert directement sur B1 sans session perd son code (le routeur renvoie à A3).
- `TODO(simplif)` posé : le pied de page pointe vers `?ecran=legal`, en 404 jusqu'au ticket A4.
- Tests : `BienvenueVueTest` (seam 1, vue avec et sans code) et `CodeRecuTest` (seam 2, garde du code, sans base). Le routeur et l'ordre « garder puis rediriger » ne sont pas testés automatiquement (spec « Testing Decisions »).
- Vérifié en navigateur (`php -S localhost:8765`) : racine sans paramètre en 200 ; `?code=` : variante invitation ; `?code=` vide ignoré ; code gardé en session puis prérempli en B1 après connexion de `sylvianne` (sans duo) ; la racine renvoie ce joueur vers B1.
- Style : classes `bienvenue*` ajoutées à `composants.css` (il n'existe pas d'`ecrans.css`).

## À compléter par le ticket #115 (garde-code et variante invitation)

- Le ticket #114 a déjà posé la variante dans la vue (elle suit `$codeRecu`) et appelle `CodeRecu::conserver()` dans le contrôleur, faute de pouvoir rendre A1 sans le code sans les deux ; #115 n'a plus qu'à les vérifier et les finir (textes, cas limites).

- Vérifier dans le navigateur le renvoi d'un joueur connecté déjà en duo (B2 ou C1) quand il ouvre un lien `?code=` (histoire 13) : seul le cas sans duo a été vu au ticket #114.
- Vérifier que le code reste gardé après un retour A2 ou A3 vers A1 (histoire 10) et affiner les textes de la variante avec leur rédaction définitive (S6).
- Mettre à jour `CodeRecuTest` si la garde du code change de signature.
