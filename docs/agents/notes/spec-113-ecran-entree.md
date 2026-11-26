# Notes d'implémentation — spec #113, A1 écran d'entrée et entrée par invitation

## Ticket #114 : écran d'entrée du visiteur (vue sans code reçu)

- `BienvenueControleur::afficher` (route `bienvenue`, GET seulement, accès public). Ordre : `CodeRecu::conserver()` d'abord, puis redirection d'un joueur connecté vers `Routeur::ecranDeLEtape(etapeAcces(...))`, enfin la vue. Le code est gardé avant la redirection pour qu'un joueur connecté sans duo le retrouve prérempli en B1 (cadrage § 6.3). Un visiteur ne déclenche aucune lecture en base.
- Vue `a1-bienvenue.php` : reçoit `$codeRecu` (null ou chaîne). La variante « entrée par invitation » (titre et accroche) s'affiche déjà dès qu'il est non nul ; le code n'est jamais rendu. Le texte vient du pitch du cadrage § 1 : le cadre Figma A1 est un zonage sans texte (blocs gris et étiquettes), sa note de variante n'en donne pas non plus.
- `TODO(simplif)` levés : celui de `Routeur::ECRAN_PAR_DEFAUT` (404 sans paramètre) et celui de `CodeRecu::conserver` (entrée du lien sur A2 et A3). Reste connu : un lien ouvert directement sur B1 sans session perd son code (le routeur renvoie à A3).
- `TODO(simplif)` posé : le pied de page pointe vers `?ecran=legal`, en 404 jusqu'au ticket A4.
- Tests : `BienvenueVueTest` (seam 1, vue avec et sans code) et `CodeRecuTest` (seam 2, garde du code, sans base). Le routeur et l'ordre « garder puis rediriger » ne sont pas testés automatiquement (spec « Testing Decisions »).
- Vérifié en navigateur (`php -S localhost:8765`) : racine sans paramètre en 200 ; `?code=` : variante invitation ; `?code=` vide ignoré ; code gardé en session puis prérempli en B1 après connexion de `sylvianne` (sans duo) ; la racine renvoie ce joueur vers B1.
- Style : classes `bienvenue*` ajoutées à `composants.css` (il n'existe pas d'`ecrans.css`).

## Ticket #115 : entrée par invitation (garde du code et variante)

- Rien à changer dans le code : #114 avait déjà livré la garde (`CodeRecu::conserver` dans `BienvenueControleur`) et la variante de la vue ; #115 les a confirmées critère par critère et a ajouté le test de `CodeRecu::reprendre` (code rendu une fois, puis effacé). Les autres cas (code gardé et tronqué, code vide ou illisible ignoré, code vide qui n'écrase pas un code gardé, code rendu à l'appel suivant) et la vue avec code reçu étaient déjà testés (`CodeRecuTest`, `BienvenueVueTest`).
- `TODO(simplif)` : celui de `CodeRecu::conserver` est levé (A1 appelle la garde ; A2 et A3 gardent leur appel, pour un retour ou une arrivée directe). Reste posé : le lien légal de A1 (`?ecran=legal`) répond 404 jusqu'au ticket A4.
- Ordre « garder puis rediriger » : le contrôleur garde le code avant de renvoyer un joueur connecté. Sans cet ordre, un joueur connecté sans duo qui ouvre le lien arriverait en B1 sans son code (cadrage § 6.3). Un joueur déjà en duo garde le code en session sans qu'il serve : B1 est le seul à le reprendre (`reprendre`), il reste donc en session jusqu'à une visite de B1.
- Limite connue : un lien d'invitation qui pointe directement sur B1 (`?ecran=salle-attente&code=…`) sans session est renvoyé vers A3 par le routeur et perd son code ; seul le lien vers A1 (`?ecran=bienvenue&code=…`) le conserve.
- Vérifié (`php -S`, comptes de démonstration ; `localhost` et `127.0.0.1` ne partagent pas leurs cookies, donc deux sessions distinctes dans le navigateur) :
  - joueur connecté sans duo (`sylvianne`), navigateur : A1 avec `?code=` renvoie en B1, champ prérempli ;
  - joueur connecté en duo, requêtes HTTP : renvoyé vers C1 (`ines`, onboarding fini par les deux) ou B2 (`lea` dont le partenaire n'a pas fini l'onboarding) ; il ne voit jamais A1 ;
  - visiteur, navigateur : A1 en variante invitation ; A2 puis ← et A3 puis ← : variante conservée (l'adresse du retour est `?ecran=bienvenue`, sans code, la session le garde) ;
  - visiteur qui se connecte (`joul`), navigateur : B1 avec le champ prérempli, sans validation ; l'inscription d'un nouveau compte donne le même résultat (requêtes HTTP) ; une seconde ouverture de B1 n'a plus le code.
- Piège de vérification : un `php -S` d'un autre worktree peut déjà écouter sur le même port, et deux serveurs répondent alors à tour de rôle, avec le code de l'autre. Contrôler `netstat -ano | grep <port>` avant de conclure, ou prendre un port libre.
- Textes de la variante : ceux de #114, à reprendre avec leur rédaction définitive (S6).
