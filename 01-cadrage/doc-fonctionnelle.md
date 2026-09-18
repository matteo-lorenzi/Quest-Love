# Projet _Quest & Love_

> **Statut : trame.** Ce document structure la documentation fonctionnelle et liste l'ensemble des éléments à couvrir. Chaque entrée marquée `[à rédiger]` sera développée par la personne chargée de la rédaction.
> Référence : cadrage unifié V2.9 (18/09/2026) · inventaire des écrans v2.2. En cas d'écart, le cadrage fait foi.

## Description synthétique

Auteurs : Sheyrel, Matteo

Pitch :

> [à rédiger — reprendre et adapter le pitch du cadrage § 1]
> Application web qui transforme le quotidien d'un couple en jeu : défi du jour tiré au sort, attribué au partenaire, points de vie, gages.

Application : `[URL Azrael à compléter]`

Code source : `[URL debug.php à compléter]`

## Principales caractéristiques

### _Minimum viable product_{lang=en} (MVP)

- Compte et duo
  - Inscription par pseudo et mot de passe, sans adresse électronique
  - Appairage par code d'invitation (60 minutes, usage unique, régénérable)
- Journée de jeu
  - Participation quotidienne (« On joue aujourd'hui ? »)
  - Tirage de 3 défis en éventail, choix et attribution au partenaire
  - Révélation, défi aléatoire à 12 h
  - Déclaration « défi réalisé »
- Conséquences
  - Expiration à minuit, perte d'1 PV
  - 0 PV : retour à 5 PV (lot 1)
- Gestion du compte
  - Profil, dissolution du duo, suppression de compte
  - Pages légales

### Ce que l'application n'est pas

- Pas une messagerie `[à rédiger]`
- Pas une application de développement personnel `[à rédiger]`
- Pas un tracker d'habitudes `[à rédiger]`
- Pas une application intime `[à rédiger]`
- Pas un réseau social `[à rédiger]`

### Profils utilisateurs

- **Joueur initiateur** : crée le compte et partage le code (persona Sylvianne) `[à rédiger]`
- **Joueur invité** : rejoint via le code, profil plus sceptique (persona Joul) `[à rédiger]`
- Rôles symétriques une fois le duo formé : chaque joueur est à la fois **émetteur** et **destinataire**

### Scénario d'utilisation

1. Le joueur **initiateur** découvre l'application et crée un **compte**
2. Il obtient un **code d'invitation** et le partage à son partenaire
3. Le **partenaire** crée un compte (ou se connecte) et saisit le code : le **duo** est formé
4. Les deux joueurs découvrent les **règles** (onboarding)
5. Chaque jour, entre 00 h et 12 h :
    - chacun répond à « On joue aujourd'hui ? »
    - si les deux répondent oui, chacun **pioche** parmi 3 défis et en **attribue** un à l'autre
6. Le **défi** est révélé à son destinataire (les deux ont choisi, ou 12 h)
7. Le destinataire réalise le défi et le **déclare réalisé** avant minuit
8. À minuit, un défi non réalisé **expire** et coûte **1 PV**
9. (lot 2) L'émetteur peut **contester** ; à 0 PV, le partenaire rédige un **gage**
10. Un joueur peut à tout moment **dissoudre** le duo ou **supprimer** son compte

### Guide utilisateur : règles du jeu (trame)

- Le duo `[à rédiger]`
  - Formation par code, un seul duo par joueur
  - Duo formé après 12 h : premier jour de jeu le lendemain
- La participation quotidienne `[à rédiger]`
  - Question posée entre 00 h et 12 h, réponse définitive
  - Journée blanche : « non », absence de réponse, partenaire ayant déjà dit non
- Le tirage et le choix `[à rédiger]`
  - 3 défis, un seul attribué, choix définitif avant 12 h
  - Défi aléatoire : joueur passif privé de son choix
- La révélation `[à rédiger]`
- La réalisation du défi `[à rédiger]`
  - Déclaration sur l'honneur, sans preuve, sans annulation
  - Échéance : minuit, heure de Paris
- Les points de vie `[à rédiger]`
- Les gages _(lot 2)_ `[à rédiger]`
- La contestation _(lot 2)_ `[à rédiger]`
- Quitter le duo, supprimer son compte `[à rédiger]`
- Mot de passe oublié = compte perdu `[à rédiger]`

## Documentation détaillée des règles

### Modèle temporel

- Bornes de la journée (00 h, 12 h, minuit, fuseau `Europe/Paris`) `[à rédiger]`
- Tableau des cas de participation et de choix (cadrage § 4.2) `[à reprendre]`
- Choix assumé de la bascule à 12 h `[à rédiger]`
- Calculs paresseux au chargement de page (absence de cron) `[à rédiger, renvoi doc technique]`

### Points de vie

| Événement | Effet | Lot |
|---|---|---|
| Départ | 5 PV | 1 |
| Défi non réalisé avant minuit | −1 PV | 1 |
| Défi non réalisé, gage en attente | −2 PV | 2 |
| Défi contesté | −1 PV (−2 si gage en attente) | 2 |
| Journée blanche | Aucun effet | 1 |
| 0 PV | Lot 1 : retour à 5 · lot 2 : gage | 1 / 2 |

- Explications et exemples `[à rédiger]`

### Gages _(lot 2)_

- Déclenchement et rédaction `[à rédiger]`
- Statut « en attente » et pénalité doublée (plafond 2) `[à rédiger]`
- Réalisation, contestation unique `[à rédiger]`
- Absence d'échéance `[à rédiger]`

### Bibliothèque et tirage

- Volume et catégories (≈ 50 défis, 4 à 5 catégories) `[à rédiger]`
- Règles de tirage : jamais reçu, sans doublon, 3 jours de repos, remise à zéro `[à rédiger]`
- Tirage persistant (pas de retirage par rechargement) `[à rédiger]`
- Contrainte de point de vue et registre des défis `[à rédiger]`

### Cycle de vie d'une mission

- Statuts : `attribue_non_revele`, `en_cours`, `valide`, `acquis`, `expire`, `conteste` `[à rédiger]`
- Diagramme des transitions `[à reprendre du cadrage § 7]`
- Origine : `choix` / `aleatoire` `[à rédiger]`

### Code d'invitation

- Validité 60 minutes, usage unique, régénération `[à rédiger]`
- Cas d'erreur : propre code, expiré, régénéré, trop de tentatives `[à rédiger]`
- Lien d'invitation reçu (connecté / non connecté / déjà en duo) `[à rédiger]`

### Sécurité et limites fonctionnelles visibles

- Limite de 5 tentatives (connexion, code) `[à rédiger]`
- Sessions multiples, double envoi `[à rédiger]`

## Description fonctionnelle

### Principes d'interface

- Mobile d'abord, desktop en adaptation `[à rédiger]`
- Une action principale par écran, cibles de 44 × 44 px `[à rédiger]`
- Confirmations en panneau bas ; exceptions F2 et F3 `[à rédiger]`
- Navigation : barre basse (Accueil, Gages, Historique, Profil), masquée sur sous-écrans et événements `[à rédiger]`
- Voix de maître du jeu, tutoiement `[à rédiger, renvoi guide de style]`
- Accessibilité : PV sans couleur seule, `prefers-reduced-motion`, échéance en texte statique `[à rédiger]`

### Carte de navigation

- Schéma des enchaînements entre écrans `[à produire]`

### Maquettes et écrans

> Pour chaque écran : maquette, éléments affichés, actions possibles, destinations, cas d'erreur. Nomenclature identique à l'inventaire et au fichier Figma.
> Une variante notée en sous-point n'a pas de cadre dédié dans le Figma : elle est documentée en note sous le cadre concerné (§ 8.5 du cadrage). Son texte reste à rédiger comme celui d'un écran.

#### Bande 01 — Arrivée et appairage

- **A1 · Accueil public** `[maquette]` `[à rédiger]`
  - Variante : invitation reçue (créer un compte / j'ai déjà un compte) — note sous le cadre A1, pas de cadre dédié
- **A2 · Inscription** `[maquette]` `[à rédiger]`
  - Avertissement mot de passe, mention CGU, code prérempli, erreurs
  - Destinations : B2 (code valide), B1 (sans code ou code invalide), retour A1
- **A3 · Connexion** `[maquette]` `[à rédiger]`
  - Erreurs, délai après 5 échecs, code conservé
  - Destinations : C1 (en duo), B2 (code valide), B1
- **A4 · Pages légales** (gabarit unique) `[maquette]` `[à rédiger]`
  - Mentions légales · Politique de confidentialité · Conditions d'utilisation
- **B1 · Salle d'attente** `[maquette]` `[à rédiger]`
  - Code à partager, lien « nouveau code », saisie, erreurs, flèche ← vers F1
  - Cas : déjà en duo (rappel du duo, retour C1, quitter mon duo → F2)
- **B2 · Duo formé et onboarding** `[maquette]` `[à rédiger]`
  - Une règle par écran, bouton « Passer »
- **B3 · Règles du jeu** `[maquette]` `[à rédiger]`

#### Bande 02 — Journée de jeu

- **C1 · Accueil face-à-face** : structure commune `[maquette]` `[à rédiger]`
  - Colonnes des deux joueurs (avatar, pseudo, jauge PV, état du jour)
  - Élément commun, zone d'action pleine largeur
- **C1 · États de l'accueil** `[maquettes]` `[à rédiger]`
  - État 1 : on joue aujourd'hui ?
    - Variante : confirmation « pas aujourd'hui » (panneau bas, erreur hors délai)
  - État 2 : attente du partenaire
  - État 3 : à toi de piocher
  - État 4 : défi envoyé, en attente de révélation
  - État 5 : révélation
  - État 6 : défi reçu en cours
    - Variante : défi réalisé, statut du défi envoyé (ex-état 7, même cadre que l'état 6)
  - Cas : aucun jeu aujourd'hui
    - Journée blanche (3 variantes de texte)
    - Variante : premier jour de jeu demain
- **C2 · Tirage** `[maquette]` `[à rédiger]`
  - Éventail de 3 cartes
  - Carte piochée, remplacement par une autre carte, « Attribuer »
- **C3 · Défi reçu** `[maquette]` `[à rédiger]`
  - En cours · confirmation « réalisé » · réalisé (un seul cadre, les deux derniers états en variantes ; confirmation par le panneau bas partagé)
  - Sans navigation, sans mention de contestabilité
- **D2 · Événement : le hasard a choisi pour toi** `[maquette]` `[à rédiger]`

#### Bande 03 — Fin de défi et événements

- **D1 · Défi expiré, −1 PV** `[maquette]` `[à rédiger]`
  - Variante « jamais découvert »
- **D5 · 0 PV, retour à 5** `[maquette]` `[à rédiger]`
- **D3 · Duo dissous** (vu par le partenaire) `[maquette]` `[à rédiger]`
- Ordre d'enchaînement des écrans d'événement `[à rédiger]`

#### Bande 04 — Profil, duo et système

- **F1 · Profil** `[maquette]` `[à rédiger]`
  - Cas : sans duo (accès depuis B1)
- **F2 · Dissolution du duo** `[maquette]` `[à rédiger]`
  - Avertissement d'irréversibilité, données supprimées
- **F3 · Suppression de compte** `[maquette]` `[à rédiger]`
- **G1 · Écrans d'erreur — page introuvable** `[maquette]` `[à rédiger]`
- **G2 · Erreur serveur / action impossible** `[à rédiger]` _(G2 est une variante de G1, même gabarit : note sous le cadre G1, pas de cadre dédié ; le texte reste à rédiger séparément)_

#### Bande 05 — Gamification _(lot 2, à zoner)_

- **C3+** · Défi envoyé réalisé : contester `[à zoner]`
- **D1+** · Défi expiré, −2 PV `[à zoner]`
- **D4** · Défi contesté, perte de PV `[à zoner]`
- **D5+** · 0 PV : gage déclenché `[à zoner]`
- **D6** · Partenaire : rédige le gage `[à zoner]`
- **D7** · Gage contesté, retour en attente `[à zoner]`
- **E1** · Rédaction d'un gage `[à zoner]`
- **E2** · Liste des gages en attente `[à zoner]`
- **E3** · Détail d'un gage `[à zoner]`
- **E4** · Historique `[à zoner]`

### Textes d'interface

- Renvoi vers le guide de style (rédaction définitive S6) `[à compléter]`
- Liste des messages par écran `[à produire]`

### Données personnelles côté utilisateur

- Données collectées : pseudo, empreinte de mot de passe `[à rédiger]`
- Contenus libres (gages) non modérés `[à rédiger]`
- Effets de la dissolution et de la suppression `[à rédiger]`

### Lots

#### Lot 1 : duo et défi du jour

- Inscription, connexion, appairage par code
- Participation quotidienne, tirage en éventail, choix et attribution
- Révélation, défi aléatoire, validation
- Expiration automatique, compteur de PV, perte d'1 PV
- Accueil face-à-face
- Dissolution du duo, suppression de compte
- Pages légales

#### Lot 2 : gamification

- Contestation
- Gages : déclenchement, rédaction, liste, contestation
- Pénalité doublée
- Historique

#### Lot 3 : confort

- Catégories et filtres
- Statistiques du couple
- Changement de mot de passe et de pseudo
- Avatars graphiques, finition graphique
- Notification par courriel (si faisable sur Azrael)

#### Lot 4 : perspectives (non développé)

- Défis personnalisés (humain ou IA)
- Preuve photo / vidéo
- Web push (PWA)
- Récupération de compte
- Punition alternative du joueur passif

## Idées pour la suite

- Notifications : du rappel à la connexion au web push `[à rédiger]`
- Risques acceptés à observer : esquive du gage par le « non », gage sans échéance, couples du soir `[à rédiger]`
- Hypothèses à valider avec le couple témoin `[à rédiger]`
  - 3 défis : choix sans paralysie
  - Perte de PV et gage perçus comme ludiques, non punitifs
- Défis personnalisés : qui les écrit ? `[à rédiger]`
