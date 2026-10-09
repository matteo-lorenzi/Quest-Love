# Quest & Love — Description technique

> **Statut : spécification.** Décrit ce qui doit être construit, avant écriture du code. Sert de brief d'implémentation pour les lots 1 et 2, et de support à la validation du schéma de données en S8.
> Référence : cadrage unifié V2.9 (18/09/2026) · inventaire des écrans v2.2. **En cas d'écart, le cadrage fait foi.**
> Les règles du jeu ne sont pas recopiées ici : ce document y renvoie (« cadrage § 4.2 »). Une règle recopiée est une règle qui divergera.
> Modèle de données : annexe [`modele-donnees.md`](modele-donnees.md).
>
> **Version 1.1 — 9 octobre 2026.** Passage à un MVC à contrôleur frontal après le retour de l'enseignant ([ADR-0001](../docs/adr/0001-controleur-frontal-mvc.md)) ; noms du code alignés sur le glossaire (`joueur`, `duo`, `tirage`). Version 1.0 du 18 septembre 2026.

## Sommaire

1. [Description synthétique](#1-description-synthétique)
2. [Architecture](#2-architecture)
3. [Mécanismes](#3-mécanismes)
4. [Interface](#4-interface)
5. [Lots](#5-lots)
6. [Exploitation](#6-exploitation)
7. [Idées pour la suite](#7-idées-pour-la-suite)

---

## 1. Description synthétique

Auteurs : Sheyrel, Matteo

Pitch :

> **Quest & Love** transforme le quotidien d'un couple en jeu.
>
> Chaque matin, trois défis tirés au sort. Chacun en choisit un — pas pour soi, pour l'autre. Dès que les deux ont choisi, ou à midi au plus tard, chacun découvre le défi qu'il a reçu. Il reste jusqu'à minuit pour le relever.
>
> Un défi non relevé coûte un point de vie. À court de points, c'est le partenaire qui rédige le gage.
>
> Drôle, quotidien, jamais intime. Un duo fermé, pas de conversation, pas de score : juste ce qu'il faut de hasard pour n'avoir rien à négocier.

Application : `https://azrael.sha.univ-poitiers.fr/~mlorenzi/`

Code source : `https://azrael.sha.univ-poitiers.fr/~mlorenzi/debug.php`

### 1.1 Contraintes d'hébergement

| Contrainte | Conséquence technique |
|---|---|
| Serveur Azrael, HTTPS obligatoire | Sessions PHP natives, cookie `Secure` et `HttpOnly` |
| PHP 8.2 | `password_hash()`, PDO, types de retour, énumérations |
| MariaDB 10, moteur InnoDB | Transactions et verrous disponibles ; schéma validé avant implémentation (S8) |
| Aucune tâche planifiée | Expiration, révélation et défi aléatoire calculés au chargement de page (§ 3.2) |
| Pas de gestionnaire de dépendances | Aucune bibliothèque tierce, ni côté serveur ni côté client |
| `debug.php` déposé à la racine | Consultation du code source par le jury, dès S2 |

Détail des contraintes : cadrage § 3.

### 1.2 Choix d'ensemble

- **MVC à contrôleur frontal, rendu côté serveur** — un point d'entrée unique, un contrôleur et une vue par écran de l'inventaire ; le JavaScript n'ajoute que du confort (§ 4.3).
- **Aucune étape de construction** — le dossier `app/` est déployable par simple copie.
- **Aucune API** — pas d'échange JSON en lot 1 ; les formulaires postent puis redirigent (motif POST / redirection / GET).
- **Français dans le code** — fichiers, fonctions, tables et colonnes en français sans accent, conformément à la convention du `README.md`.
- **Une transaction par écriture métier** — toute écriture touchant les points de vie ou une mission s'exécute dans une transaction avec verrou (§ 3.2).

---

## 2. Architecture

MVC à contrôleur frontal : un seul fichier public reçoit toutes les requêtes et les distribue aux contrôleurs. Décision et alternatives : [ADR-0001](../docs/adr/0001-controleur-frontal-mvc.md).

### 2.1 Arborescence

Le code vit dans `app/`, seul dossier copié sur Azrael (`~mlorenzi/quest-love/`). Seul `app/public/` est joignable par une URL.

- **`app/public/`** — racine web
  - `index.php` — contrôleur frontal : amorce, routage, rendu
  - `styles/` — `base.css`, `composants.css`, `ecrans.css`
  - `scripts/` — `eventail.js`, `echeance.js`
  - `medias/` — pictogrammes et avatars générés
- **`app/noyau/`** — mécanique commune, sans règle du jeu
  - `amorce.php` — autoloader, fuseau, session, jeton CSRF, connexion PDO
  - `Routeur.php` — table de routage, préconditions d'accès, aiguillage vers le contrôleur
  - `Bdd.php` — connexion PDO, mode exception, jeu de caractères `utf8mb4`
  - `Securite.php` — jeton CSRF, limitation des tentatives
  - `Vue.php` — rendu d'une vue dans le gabarit, fonction d'échappement unique
- **`app/controleurs/`** — un contrôleur par écran de l'inventaire (table § 2.3)
- **`app/modeles/`** — le M du MVC, en trois rôles
  - `entites/` — les objets du domaine et leurs états : `Joueur`, `Duo`, `Participation`, `Defi`, `Tirage`, `Mission` ; énumérations `StatutMission`, `OrigineMission`, `Reponse`
  - `depots/` — le seul endroit où s'écrit du SQL : `JoueurDepot`, `DuoDepot`, `ParticipationDepot`, `DefiDepot`, `TirageDepot`, `MissionDepot`, `TentativeDepot`
  - `services/` — les règles du jeu et les transactions : `CompteService`, `AppairageService`, `JourneeService`, `TirageService`, `MissionService`, `PointsDeVieService`, `RattrapageService`
- **`app/vues/`** — le V du MVC
  - `gabarits/` — `entete.php`, `pied.php`, `navigation.php`
  - `composants/` — `socle-c1.php`, `carte-defi.php`, `panneau-confirmation.php`, `bloc-evenement.php`
  - `ecrans/` — une vue par écran, préfixée de son code : `c1-accueil.php`, `c2-tirage.php`…
- **`app/config/`** — `config.exemple.php` versionné ; `config.php` non versionné (identifiants de base)

Hors de `app/`, jamais déployés : `sql/` (`schema.sql`, `defis.sql`, `demonstration.sql`) et `tests/`.

Arborescence du lot 1. Les lots suivants n'ajoutent pas de dossier : leurs contrôleurs, entités, services et vues se rangent dans les dossiers existants. Liste complète : § 5.

### 2.2 Cycle d'une requête

1. `public/index.php` inclut `noyau/amorce.php` : autoloader, fuseau `Europe/Paris`, session, connexion PDO, jeton CSRF. Il lit l'heure du serveur une seule fois, dans `$maintenant`.
2. Le routeur lit `?ecran=` ; un écran absent de la table renvoie vers G1 (page introuvable).
3. Si un joueur est connecté, le routeur appelle `RattrapageService`, qui rejoue les échéances manquées (§ 3.2). Aucune sortie n'a encore été émise : une redirection reste possible.
4. Si le rattrapage a produit des événements à montrer, le routeur redirige vers l'écran `evenement`.
5. Le routeur vérifie les préconditions d'accès de l'écran (public, connecté, en duo) et redirige le cas échéant (§ 3.6).
6. En `POST`, le routeur valide le jeton CSRF, puis le contrôleur appelle un service et redirige ; en `GET`, le contrôleur lit les données et rend sa vue.

### 2.3 Table de routage

Une ligne par écran du lot 1. Le code renvoie à l'inventaire des écrans et aux parcours ; il préfixe le nom de la vue.

| `?ecran=` | Code | Contrôleur | Accès |
|---|---|---|---|
| `bienvenue` (défaut) | A1 | `BienvenueControleur` | public |
| `inscription` | A2 | `InscriptionControleur` | public |
| `connexion` | A3 | `ConnexionControleur` | public |
| `deconnexion` | — | `ConnexionControleur` | connecté |
| `legal` | A4 | `LegalControleur` | public |
| `salle-attente` | B1 | `SalleAttenteControleur` | connecté |
| `onboarding` | B2 | `OnboardingControleur` | en duo |
| `regles` | B3 | `ReglesControleur` | connecté |
| `accueil` | C1 | `AccueilControleur` | en duo |
| `tirage` | C2 | `TirageControleur` | en duo |
| `defi-recu` | C3 | `DefiRecuControleur` | en duo |
| `evenement` | D1, D2, D3, D5 | `EvenementControleur` | connecté |
| `profil` | F1 | `ProfilControleur` | connecté |
| `quitter-duo` | F2 | `QuitterDuoControleur` | en duo |
| `supprimer-compte` | F3 | `SupprimerCompteControleur` | connecté |
| `erreur` | G1 | `ErreurControleur` | public |

### 2.4 Conventions de code

- **Temps serveur uniquement** — `date_default_timezone_set` sur `Europe/Paris` dans `amorce.php` ; aucune date issue du client n'est acceptée. Cadrage § 6.3.
- **L'heure en paramètre** — tout service qui dépend de l'heure reçoit `DateTimeImmutable $maintenant` ; seul `index.php` lit l'horloge. C'est ce qui rend minuit, 12 h et le changement d'heure testables sans attendre.
- **Bornes de journée par le calendrier** — `DateTimeImmutable` sur `today` et `tomorrow`, décalages par `modify` ; jamais d'addition de 86 400 secondes.
- **Contrôleurs fins** — un contrôleur lit la requête, appelle un service, puis redirige ou rend sa vue. Aucune règle du jeu ni aucun SQL dans un contrôleur ou une vue.
- **SQL dans les dépôts seulement**, en requêtes préparées sans exception, y compris pour les entiers issus de l'URL.
- **Échappement à la sortie** — une seule fonction d'échappement, appelée dans les vues ; aucun texte libre n'est écrit sans elle.
- **Redirection après écriture** — tout `POST` se termine par un en-tête `Location` puis `exit`.
- **Un écran, un contrôleur, une vue** — un écran de l'inventaire ne se rend jamais depuis deux vues différentes.
- **États en énumérations PHP** — statuts de mission, origines de défi, réponses de participation ; jamais de chaînes libres.
- **Français sans accent partout** — classes, méthodes, cas d'énumération, tables et colonnes : `MissionDepot::trouverEnCoursParDestinataire()`, `StatutMission::EnCours`. Suffixes de rôle en français : `Controleur`, `Depot`, `Service`.
- **Vocabulaire du glossaire** — les noms du code suivent `GLOSSARY.md` : `Joueur`, `Duo`, `Tirage`, `Mission`, jamais « utilisateur », « couple » ou « pioche ».
- **HTML sémantique** — `header`, `main`, `nav`, `section`, `article`, `form`, `button` avant tout `div` ; classes CSS en BEM français : `carte-defi`, `carte-defi__titre`, `carte-defi--aleatoire`.

---

## 3. Mécanismes

Sept entrées : ce que fait le mécanisme, quand il se déclenche, ce qu'il écrit. Les règles du jeu restent au cadrage, en renvoi de fin d'entrée.

### 3.1 Journée de jeu et participation

- **Service** : `JourneeService`.
- **Identifiant de journée** : une date `AAAA-MM-JJ` calculée en `Europe/Paris`. C'est la clé de `participation` et de `tirage`.
- **Trois bornes** : ouverture à 00 h, bascule à 12 h, expiration à minuit.
- **Fonctions attendues** : journée courante, bascule du jour, minuit suivant, test « avant la bascule ».
- **Écriture de la participation** : une ligne par joueur et par jour. L'unicité en base est ce qui rend la réponse définitive et rend le double clic inoffensif.
- **Refus hors délai** : une réponse reçue après la bascule est rejetée par comparaison à l'horodatage serveur, avec un message en texte ; l'accueil est rechargé dans son état réel.
- **Changement d'heure** : les bornes étant calculées par le calendrier, un jour de 23 h ou 25 h ne demande aucun traitement.
- **Règle** : cadrage § 4.1, § 4.2, § 6.3.

### 3.2 Rattrapage paresseux, concurrence et événements

Le mécanisme central : il remplace la tâche planifiée absente et rejoue tout ce qui aurait dû se produire depuis la dernière visite.

- **Service** : `RattrapageService`, appelé par le routeur pour tout joueur connecté, une fois par requête, avant toute sortie.
- **Opérations, dans cet ordre, à l'intérieur d'une seule transaction** :
  1. expiration des missions échues au statut « en cours », avec perte de points de vie ;
  2. évaluation de la participation du jour et détermination de la journée blanche ;
  3. révélation des missions dont la condition est remplie ;
  4. après la bascule de 12 h, attribution des défis aléatoires manquants.
- **Verrou** : les lignes `joueur` des deux joueurs sont verrouillées en écriture pour la durée de la transaction. C'est la protection contre la double pénalité quand deux appareils chargent une page en même temps.
- **Trois protections, toutes posées en base** — unicité sur `participation`, unicité sur `tirage`, vérification du statut avant transition sur `mission`. Détail : annexe § 4.
- **File d'événements** : le rattrapage dépose les événements produits dans une file en session ; tant qu'elle n'est pas vide, le routeur redirige vers l'écran `evenement`, qui en dépile un par affichage. Ordre : duo dissous, expiration, passage à zéro, défi aléatoire.
- **Limite connue** : deux onglets peuvent afficher deux états différents du même écran ; la dernière écriture reste cohérente, l'affichage se corrige au rechargement.
- **Coût** : quatre requêtes indexées par chargement de page. À surveiller si l'accueil devient lent.
- **Règle** : cadrage § 6.1, § 6.7, § 8.3.

### 3.3 Tirage persistant et défi aléatoire

- **Service** : `TirageService`.
- **Génération** : à la première demande du joueur dans la journée, jamais avant. Les trois défis sont écrits immédiatement dans `tirage_proposition` ; toute lecture ultérieure relit ces lignes. Sans cette persistance, un rechargement permettrait de retirer jusqu'à obtenir un défi facile.
- **Réservoir** : défis jamais reçus par le destinataire, sans doublon entre les trois, en excluant ceux proposés et non choisis depuis moins de trois jours. Sous trois défis disponibles, le cycle du destinataire repart à zéro.
- **Choix** : marque la proposition retenue, renseigne `date_choix` et crée la mission. Accepté seulement avant la bascule.
- **Défi aléatoire** : tiré parmi les propositions **déjà persistées** du joueur passif — donc aucun nouveau tirage. Marqué d'une origine `aleatoire`, qui déclenche l'écran D2.
- **Idempotence** : l'unicité `(date_jour, emetteur_id)` empêche le double jeu de propositions et la double attribution aléatoire.
- **Règle** : cadrage § 4.2, § 4.6, § 6.2.

### 3.4 Cycle de vie d'une mission

- **Service** : `MissionService` ; statuts dans l'énumération `StatutMission`. Transitions : annexe § 3.
- **Révélation** : calculée au rattrapage, jamais à l'affichage.
- **Découverte** : horodatée à la première consultation par le destinataire. Sa nullité à l'expiration déclenche la variante « jamais découvert » de D1.
- **Validation** : vérifier que la mission n'est pas déjà validée avant d'écrire — cas de la double validation depuis deux appareils.
- **Expiration** : à minuit, par le rattrapage, avec perte de points de vie.
- **Règle** : cadrage § 4.1, § 4.3.

### 3.5 Points de vie

- **Service** : `PointsDeVieService`, seul autorisé à faire écrire la colonne `pv` (via `JoueurDepot`).
- **Barème** : tableau du cadrage § 4.4. Une seule constante de pénalité vit dans le module, tenue à jour depuis ce tableau.
- **Appel** : depuis l'expiration d'une mission, jamais ailleurs. La journée blanche n'appelle rien.
- **Passage à zéro** : au lot 1, le compteur remonte immédiatement à 5 et l'événement D5 est empilé ; au lot 2, le gage se déclenche en plus.
- **Écriture** : dans la transaction déjà ouverte par le rattrapage, sur une ligne verrouillée (§ 3.2).
- **Règle** : cadrage § 4.4, § 4.5.

### 3.6 Compte, session et appairage

- **Services** : `CompteService`, `AppairageService`.
- **Mot de passe** : `password_hash` au stockage, `password_verify` à la vérification.
- **Session** : identifiant régénéré à la connexion ; la session ne porte que l'identifiant du joueur et la file d'événements, jamais l'état de jeu.
- **Code d'invitation** : à usage unique, 60 minutes, régénérable — la régénération écrase l'ancien code, ce qui l'invalide.
- **Code conservé** : un code reçu avant authentification est gardé en session et rejoué après inscription ou connexion.
- **Aiguillage après authentification** : en duo vers C1 ; sans duo avec code valide vers B2 ; sinon vers B1, avec message d'erreur si le code était invalide.
- **Vérification au chargement** : B1 contrôle à chaque chargement si le duo s'est formé entre-temps.
- **Cas d'erreur à couvrir** : propre code, code expiré, code régénéré, code d'un joueur déjà en duo, trop de tentatives.
- **Dissolution et suppression** : effets sur les données en annexe § 6 ; règles en cadrage § 10.

### 3.7 Sécurité

- **Classe** : `noyau/Securite.php`.
- **CSRF** : un jeton par session, vérifié sur tout formulaire d'écriture ; échec vers G1.
- **Injection SQL** : requêtes préparées exclusivement.
- **Injection HTML** : échappement systématique des textes libres à la sortie — pseudos et gages.
- **Limitation des tentatives** : au-delà de cinq échecs sur la connexion ou la saisie d'un code, un délai s'applique. Comptage en base, par pseudo et par session.
- **Session** : cookie `Secure`, `HttpOnly`, `SameSite=Lax`.
- **Hors périmètre, à écrire dans le dossier** : pas de double authentification, pas de journalisation des accès, pas de politique de sécurité de contenu.
- **Règle** : cadrage § 6.6.

---

## 4. Interface

### 4.1 Gabarits et fragments

- **Une vue d'écran = un gabarit d'en-tête, un contenu, un gabarit de pied.** Aucun HTML dupliqué entre deux écrans.
- **`entete.php`** — ouverture du document, `lang="fr"`, méta `viewport`, feuilles de style, titre passé en variable.
- **`navigation.php`** — barre basse à quatre onglets ; incluse seulement par les écrans qui la portent, jamais sur les sous-écrans focalisés ni sur les événements.
- **`pied.php`** — fermeture du document, scripts en fin de corps.
- Les textes d'interface sont rédigés en S6 et remplacent les textes d'attente ; ils ne sont pas improvisés dans le code. Cadrage § 8.3.

### 4.2 Composants partagés

Les quatre composants isolés au zonage Figma ont chacun une vue unique dans `vues/composants/`. Modifier le composant, jamais une copie.

- **`socle-c1.php`** — deux colonnes symétriques, élément commun au centre, zone d'action pleine largeur. Reçoit l'état en paramètre : les six états de l'accueil et le cas « aucun jeu » sont des variantes du même socle.
- **`carte-defi.php`** — titre, description, catégorie, statut, mention « choisi par le hasard » si l'origine est aléatoire. Utilisé par C1, C2 et C3.
- **`panneau-confirmation.php`** — panneau bas générique : question, confirmation, annulation. Porte « pas aujourd'hui », « défi réalisé », et au lot 2 la contestation. Exceptions F2 et F3, écrans dédiés exigés par le cadrage § 10.
- **`bloc-evenement.php`** — gabarit de D1, D2, D3, D5 : illustration, titre, texte, action unique.

### 4.3 JavaScript du lot 1

Deux fichiers, sans dépendance. L'application reste utilisable sans eux.

- **`eventail.js`** — anime les trois cartes face cachée. Toucher une carte la sort du lot et la révèle ; toucher une autre carte remplace la première.
  - **Sans JavaScript** : les trois cartes sont trois boutons de formulaire empilés ; le choix passe par un `POST`.
  - **Mouvement réduit** : la carte s'affiche directement, sans animation.
- **`echeance.js`** — affiche le temps restant avant minuit.
  - L'échéance est **toujours écrite en texte statique** dans le HTML ; le script ne fait que l'enrichir.
  - La zone n'est pas une région dynamique : pas d'annonce répétée aux technologies d'assistance.

### 4.4 Accessibilité

Exigences et niveau visé : cadrage § 9. Traductions techniques qui n'en découlent pas d'elles-mêmes :

- Un seul `main` par page, `nav` pour la barre basse, titres sans saut de niveau.
- Erreurs de formulaire rendues en texte à côté du champ concerné.
- Cartes de l'éventail exposées comme boutons dans l'ordre du document, partie visible d'au moins 44 px.
- Unités relatives partout, pour rester utilisable à 200 %.

### 4.5 Feuilles de style

- **`base.css`** — réinitialisation, variables de couleur et de typographie, échelle d'espacement.
- **`composants.css`** — les quatre composants partagés, navigation, boutons, champs.
- **`ecrans.css`** — ce qui ne sert qu'à un écran. Doit rester le plus petit des trois ; s'il grossit, c'est qu'un composant manque.
- Mobile d'abord : les requêtes de média n'ajoutent que l'adaptation grand écran.

---

## 5. Lots

Manifeste des fichiers à produire. Chemins relatifs à `app/` sauf `sql/` et `tests/`. Périmètre fonctionnel : cadrage § 11. Répartition A / B : cadrage § 2. Chaque élément devient un ou plusieurs tickets GitHub, qui citent le code d'écran et le parcours couverts.

### 5.1 Lot 1 — duo et défi du jour

**Socle** _(commun)_

- [ ] `sql/schema.sql` — schéma complet, conforme à l'annexe (validé en S8)
- [ ] `sql/defis.sql` — bibliothèque des 50 défis et leurs catégories
- [ ] `public/index.php` — contrôleur frontal
- [ ] `noyau/amorce.php`, `noyau/Routeur.php` — autoloader, session, fuseau, table de routage, préconditions, appel du rattrapage
- [ ] `noyau/Bdd.php`, `config/config.exemple.php` — connexion PDO et configuration
- [ ] `noyau/Securite.php` — CSRF, limitation des tentatives
- [ ] `noyau/Vue.php` — rendu et échappement de sortie
- [ ] `vues/gabarits/entete.php`, `pied.php`, `navigation.php`
- [ ] `public/styles/base.css`, `composants.css`, `ecrans.css`
- [ ] `tests/` — lanceur de tests et base de test (outil selon la réponse de l'enseignant)

**Compte et duo** _(A)_

- [ ] Entités `Joueur`, `Duo` · dépôts `JoueurDepot`, `DuoDepot`, `TentativeDepot`
- [ ] `CompteService` — inscription, connexion, suppression
- [ ] `AppairageService` — code d'invitation, formation, dissolution
- [ ] A1 `BienvenueControleur` + `vues/ecrans/a1-bienvenue.php` — et variante invitation
- [ ] A2 `InscriptionControleur` · A3 `ConnexionControleur` (et déconnexion)
- [ ] A4 `LegalControleur` — vue unique, page en `&page=mentions|confidentialite|conditions`
- [ ] B1 `SalleAttenteControleur` — et cas « déjà en duo »
- [ ] B2 `OnboardingControleur` — une règle par écran, étape en `&etape=` · B3 `ReglesControleur`
- [ ] F1 `ProfilControleur` — et cas « sans duo »
- [ ] F2 `QuitterDuoControleur` · F3 `SupprimerCompteControleur`

**Journée de jeu** _(B)_

- [ ] Entités `Participation`, `Defi`, `Tirage`, `Mission` · énumérations `StatutMission`, `OrigineMission`, `Reponse`
- [ ] Dépôts `ParticipationDepot`, `DefiDepot`, `TirageDepot`, `MissionDepot`
- [ ] `JourneeService` — bornes, participation
- [ ] `TirageService` — tirage persistant, choix, défi aléatoire
- [ ] `MissionService` — révélation, découverte, validation, expiration
- [ ] `PointsDeVieService` — pertes et plancher
- [ ] `RattrapageService` — calculs paresseux et file d'événements
- [ ] `vues/composants/socle-c1.php` — colonnes _(A)_, zone d'action _(B)_
- [ ] `vues/composants/carte-defi.php`, `panneau-confirmation.php`
- [ ] C1 `AccueilControleur` — six états et cas « aucun jeu aujourd'hui »
- [ ] C2 `TirageControleur` · C3 `DefiRecuControleur`
- [ ] `public/scripts/eventail.js`, `echeance.js`

**Événements et erreurs** _(B, sauf D3)_

- [ ] `vues/composants/bloc-evenement.php`
- [ ] D1, D2, D3, D5 `EvenementControleur`
- [ ] G1 `ErreurControleur` — page introuvable et erreur serveur

**Exploitation** _(commun)_

- [ ] `sql/demonstration.sql` — jeu de données reproductible (§ 6.3)
- [ ] `debug.php` déposé à la racine du dossier Azrael

### 5.2 Lot 2 — gamification

- [ ] Entité `Gage`, `GageDepot`, `GageService` — déclenchement, rédaction, réalisation, contestation
- [ ] `ContestationService` — fenêtre de 24 h, effets sur les points de vie
- [ ] E1, E2, E3 `GagesControleur` · E4 `HistoriqueControleur`
- [ ] Extension d'`EvenementControleur` — D1+, D4, D5+, D6, D7
- [ ] Extension de `vues/composants/carte-defi.php` — lien discret de contestation, visible du seul émetteur
- [ ] Extension de `PointsDeVieService` — pénalité doublée, plafonnée à 2

### 5.3 Lot 3 — confort

- [ ] `CourrielService` — notification au choix, si l'envoi est possible sur Azrael
- [ ] F4 `StatistiquesControleur` · F5 `ParametresControleur`
- [ ] Filtres par catégorie sur l'historique
- [ ] Avatars graphiques dans `public/medias/`

### 5.4 Lot 4

Non développé. Pistes en § 7, à reprendre en recul réflexif.

---

## 6. Exploitation

### 6.1 Installation

1. Copier le dossier `app/` dans `~mlorenzi/quest-love/` sur Azrael ; aucune installation de dépendances. Par un script `rsync`/`scp` si le compte a un accès shell, par SFTP sinon (à vérifier).
2. Faire de `app/public/` la racine servie ; à défaut, un `.htaccess` à la racine de `app/` redirige vers `public/` (ADR-0001).
3. Créer la base, puis charger `sql/schema.sql` et `sql/defis.sql`.
4. Copier `config/config.exemple.php` en `config/config.php` et y renseigner les identifiants de base. Ce fichier n'est jamais versionné.
5. Déposer `debug.php` à la racine du dossier.
6. Vérifier que l'heure du serveur et le fuseau `Europe/Paris` concordent : tout le modèle temporel en dépend.

### 6.2 Organisation Git

- Le travail est découpé en tickets GitHub Issues ; mode d'emploi : [`docs/guide-github-issues.md`](../docs/guide-github-issues.md).
- Une branche par ticket (`ticket-14-tirage`), puis une pull request vers `main` contenant `Closes #14`, relue par l'autre membre du binôme avant fusion.
- Répartition par écran, pas par couche : voir le manifeste § 5. Deux personnes ne modifient pas le même fichier en même temps.
- Fichiers communs (`noyau/`, `config/config.exemple.php`, styles, schéma) : modifiés d'un commun accord, dans un ticket dédié.
- Point de synchronisation hebdomadaire maintenu.
- Jamais de configuration ni de vidage de base dans le dépôt.

### 6.3 Jeu de données de démonstration

`sql/demonstration.sql`, rechargeable en une commande. C'est la seule façon de démontrer l'expiration sans attendre minuit. Doit contenir :

- un duo formé, avec quelques jours d'historique ;
- une mission expirée, `date_decouverte` nulle, pour la variante « jamais découvert » ;
- une mission d'origine aléatoire ;
- une journée blanche ;
- un joueur à 1 point de vie, pour atteindre D5 en une action ;
- _(lot 2)_ un gage en attente.

Cadrage § 6.4.

### 6.4 Recette technique du lot 1

Complète les critères de succès du cadrage § 14, qui restent la référence.

- [ ] Deux sessions du même compte ne produisent ni double pénalité ni double attribution.
- [ ] Une réponse envoyée après la bascule est refusée avec un message en texte.
- [ ] Un chargement de page après minuit applique l'expiration et affiche D1.
- [ ] La dissolution supprime les données de jeu et affiche D3 au partenaire.
- [ ] Le parcours complet se fait au clavier, avec focus visible.
- [ ] L'application reste utilisable avec JavaScript désactivé.
- [ ] Avant le gel (S12) : audit d'accessibilité, captures sur appareil réel, vidéo du parcours, export du code et sauvegarde de la base.

---

## 7. Idées pour la suite

Pistes non engageantes, à documenter en recul réflexif plutôt qu'à développer.

- **Notification par courriel** _(lot 3)_ — l'événement déclencheur est déjà synchrone : quand un joueur choisit, une requête PHP est en cours. L'obstacle n'est pas l'ordonnancement mais l'infrastructure d'envoi. Faisabilité à tester sur Azrael en S10.
- **Notification web push** _(lot 4)_ — suppose un service worker, un abonnement VAPID et un serveur d'envoi. Hors de portée du semestre ; à décrire pour montrer que la limite est comprise.
- **Tâche planifiée** — si un cron devenait disponible, le rattrapage deviendrait un script exécuté à 12 h et à minuit, sans changer la logique : `RattrapageService` est déjà isolé pour ça.
- **Récupération de compte** _(lot 4)_ — par adresse électronique, ou par validation du partenaire. La seconde piste est plus cohérente avec la minimisation des données.
- **Défis personnalisés** _(lot 4)_ — écrits par les joueurs ou générés. Question ouverte du cadrage : qui les écrit, et qui en répond.
- **Historique et statistiques** — les tables portent déjà les horodatages nécessaires ; aucune migration ne sera requise pour les ajouter.
