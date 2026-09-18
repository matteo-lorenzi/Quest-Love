# Quest & Love — Document de cadrage unifié

> Source unique de vérité du projet. Tient lieu de cahier des charges : il est daté, versionné et figurera en annexe du dossier.
> Toute modification de règle est reportée ici **avant** d'être codée. Toute évolution après la validation du schéma (S8) part en lot 4.
>
> **Version 2.9 — 18 septembre 2026.** Intègre la reprise du zonage Figma suite au retour du professeur sur la lisibilité des maquettes (annotations, regroupement, variantes). La V2.8 intégrait les décisions de la séance de conception (étape 1, inventaire, parcours, zonage) ; la V2.0 fusionnait le document de référence V1 et le complément V1.1.

---

## ⚠️ Arbitrages

| # | Sujet | Statut | Détail |
|---|---|---|---|
| A1 | Révélation simultanée | Tranché le 15/09 | § 4.2 |
| A2 | Heure limite de choix (12 h) | Tranché le 15/09 | § 4.2 |
| A3 | Notification à l'attribution | **Ouvert, échéance S3** | § 6.5 |
| A4 | Adresse à l'utilisateur | Tranché le 15/09 : tutoiement | § 8.1 |

---

## Sommaire

1. Identité et positionnement
2. Gouvernance et organisation
3. Contraintes structurantes
4. Règles du jeu
5. Cibles et contexte d'usage
6. Points techniques
7. Modèle de données
8. Ligne éditoriale
9. Accessibilité
10. Données personnelles et cadre légal
11. Périmètre par lots
12. Rétroplanning
13. Risques
14. Critères de succès
15. Après-projet et portfolio
16. Décisions ouvertes
17. Note de fusion

---

## 1. Identité et positionnement

**Intitulé** : Quest & Love. Nom figé. Le mélange anglais-français est assumé et justifié dans le dossier par l'usage courant du lexique du jeu vidéo en anglais dans un contexte francophone.

**Pitch** : application web qui transforme le quotidien d'un couple en jeu. Chaque jour, chacun choisit un défi parmi trois propositions tirées au sort et l'attribue à l'autre. Le défi doit être réalisé dans la journée, sous peine de perdre un point de vie. À court de points, le joueur écope d'un gage rédigé par son partenaire.

**Cadre** : projet universitaire noté, M2 web éditorial, semestre 1.

**Positionnement éditorial** : ton drôle et original. Les défis de la V1 ne comportent aucun contenu intime. Choix assumé, à justifier dans le dossier : il rend l'application démontrable devant un jury et testable par des tiers.

**Ressort d'usage** : l'envie, pas le besoin. L'application ne résout pas un problème, elle ajoute quelque chose. À écrire tel quel dans le dossier pour ne pas surpromettre.

### Ce que l'application n'est pas

À reprendre dans le dossier et en ouverture de soutenance. C'est aussi un outil de décision : toute demande de fonctionnalité qui entre dans l'une de ces cases est refusée sans discussion.

- **Pas une messagerie** : aucune conversation, aucune négociation dans l'outil.
- **Pas une application de développement personnel** : ni objectifs, ni progression, ni bien-être.
- **Pas un tracker d'habitudes** : le défi est ponctuel, non répété, non mesuré dans la durée.
- **Pas une application intime** : registre exclu de la V1.
- **Pas un réseau social** : aucun partage, aucun contenu public, un duo fermé.

---

## 2. Gouvernance et organisation

### Commanditaire et validation

**Commanditaire fictif.** Projet porté en interne par le binôme, évalué par un jury d'enseignants.

**Pas de cahier des charges externe.** C'est le point le plus fragile : rien ne protège d'une dérive de périmètre, rien ne permet au jury de vérifier que le livré correspond à l'annoncé. Ce document tient donc lieu de cahier des charges. Tout écart avec le livré est justifié dans le recul réflexif : un écart assumé vaut mieux qu'un écart silencieux.

**Une seule validation formelle : la soutenance.** Deux contre-mesures :

1. **Journal de bord daté.** À chaque jalon, trois lignes : ce qui est livré, ce qui ne l'est pas, ce qui est décalé. Matière directe pour la section « conduite de projet » du dossier.
2. **Validations orales opportunistes.** Faire valider le périmètre en S4 et le schéma BDD en S8 par un enseignant, même officieusement. Cinq minutes qui suppriment le principal risque du projet.

### Équipe et répartition

Deux personnes aux compétences équivalentes. Répartition **par écran** plutôt que par couche technique, pour éviter deux personnes dans les mêmes fichiers.

| Personne | Périmètre |
|---|---|
| A | Authentification, appairage, profil, écran de statut du couple |
| B | Tirage, écran du défi du jour, validation, historique |
| Commun | Modèle de données, charte graphique, guide de style, dossier, soutenance |

**Accueil face-à-face (15/09)** : A porte le composant face-à-face (colonnes des deux joueurs), B la zone d'action située dessous.

Un point de synchronisation hebdomadaire fixe, branches Git séparées, fusion en fin de semaine.

### Règle d'arbitrage en binôme

Pas de chef de projet, mais un mécanisme de départage :

1. ce document fait foi ;
2. s'il est muet, **l'option au périmètre le plus réduit l'emporte** ;
3. si le désaccord persiste, il est posé à l'enseignant au cours suivant.

Règle auto-appliquante, qui pousse structurellement vers la livraison.

---

## 3. Contraintes structurantes

| Contrainte | Conséquence |
|---|---|
| Hébergement Azrael, HTTPS obligatoire | Application réellement en ligne, sessions PHP, RGPD applicable |
| PHP 8.2 | Rendu côté serveur, `password_hash()`, PDO en requêtes préparées |
| MariaDB 10 | Schéma validé par l'enseignant avant implémentation |
| Aucune tâche planifiée (cron) | Expiration et révélation calculées paresseusement au chargement de page |
| Livraison par lots validés | Le lot 1 doit être complet de bout en bout |
| `debug.php` à installer dans le dossier | Dès S2 |
| Soutenance de 15 minutes | Démo de 5 minutes maximum, répétée, sur deux téléphones réels |

---

## 4. Règles du jeu

### 4.1 Cycle quotidien (figé)

1. Chaque joueur reçoit chaque jour **3 défis tirés au hasard** dans la bibliothèque (règles de tirage : § 4.6).
2. Il en choisit **un seul**, attribué à son partenaire.
3. Rôles symétriques : **1 défi actif par joueur et par jour**, dans les deux sens.
4. Le défi expire **à minuit, heure serveur** (`Europe/Paris`), le jour de son attribution.

### 4.2 Modèle temporel (tranché le 15/09)

**Participation**
- À la première connexion du jour, **entre 00 h et 12 h**, chaque joueur répond à « On joue aujourd'hui ? ». Réponse définitive.
- La journée est jouée **uniquement si les deux répondent oui**.
- Un « non », ou une absence de réponse à 12 h, rend la journée **blanche** : aucun défi, aucune perte de PV.
- Si le partenaire a déjà répondu non, la question n'est pas posée.

**Choix** : après deux oui, chaque joueur choisit entre 00 h et 12 h. Choix **définitif**, accepté uniquement si l'horodatage serveur est antérieur à 12 h.

**Révélation** : le défi est visible par son destinataire au premier des deux événements : les deux ont choisi, ou il est 12 h.

**Défi aléatoire**
- À 12 h, si un joueur ayant dit oui n'a pas choisi, son partenaire reçoit un défi tiré **parmi les 3 propositions du joueur passif**.
- Si aucun n'a choisi, chacun reçoit un défi tiré parmi les propositions de l'autre.
- Punition du joueur passif : **privation de son choix** (alternative étudiée en lot 4).
- Un défi aléatoire est un défi normal, signalé « choisi par le hasard ».

| Réponses | Choix à 12 h | Résultat |
|---|---|---|
| Oui / Oui | Les deux | Journée normale |
| Oui / Oui | Un seul | Le joueur diligent reçoit un défi aléatoire |
| Oui / Oui | Aucun | Deux défis aléatoires |
| Oui / Non | — | Journée blanche |
| Absence de réponse | — | Journée blanche |

**Duo formé après 12 h** : premier jour de jeu le lendemain.

**Risque accepté** : un joueur en dette de gage peut répondre non chaque jour pour éviter la pénalité doublée. À analyser en recul réflexif.

**Choix assumé : bascule à 12 h.** La persona principale ouvre l'application en tout début ou toute fin de journée. Un couple qui ne se connecte que le soir ne peut pas jouer : la journée est blanche. Ce choix est délibéré : il garantit une demi-journée pour réaliser le défi et une révélation commune, sans tâche planifiée. Il est justifié dans le dossier, observé avec le couple témoin et analysé en recul réflexif.

### 4.3 Validation

- Le destinataire déclare le défi **réalisé**, après confirmation. **Aucune annulation**.
- L'émetteur dispose d'une **fenêtre de contestation de 24 h** (lot 2). Principe de confiance : la contestation est un lien discret, jamais mise en avant, **visible du seul émetteur**. L'écran du défi reçu (C3) n'affiche aucune mention de contestabilité, ni pendant le défi ni après sa réalisation.
- Un défi aléatoire est contestable par le partenaire dans les mêmes conditions.
- Passé ce délai, le défi est définitivement acquis.
- Preuve photo ou vidéo hors périmètre.

### 4.4 Points de vie

| Événement | Effet | Lot |
|---|---|---|
| Départ | 5 PV | 1 |
| Défi non réalisé avant minuit | −1 PV | 1 |
| Défi non réalisé, gage en attente | −2 PV | 2 |
| Défi contesté par l'émetteur | −1 PV (−2 si gage en attente) | 2 |
| Journée blanche | Aucun effet | 1 |
| 0 PV atteint | Lot 1 : retour à 5 PV avec écran d'événement (D5) ; lot 2 : déclenchement du gage | 1 / 2 |

### 4.5 Gages (lot 2)

- À 0 PV, le partenaire est invité à rédiger un gage libre : proposition prioritaire à la connexion, non bloquante.
- Les PV repassent **immédiatement à 5**.
- Le gage est **en attente dès sa rédaction**. Tant qu'au moins un gage est en attente, la pénalité passe à **2 PV, plafonnée à 2**.
- Le joueur concerné marque le gage réalisé. **Une seule contestation par gage** : le gage contesté repasse en attente, **sans perte de PV** ; la déclaration suivante est définitive.
- Pas d'échéance sur un gage (risque accepté, recul réflexif).
- Liste vide : la pénalité revient à 1 PV.

### 4.6 Bibliothèque et tirage

- Environ **50 défis** pour la V1, répartis en 4 à 5 catégories.
- Les 3 propositions sont tirées parmi les défis **jamais reçus par le destinataire**, sans doublon entre elles.
- Seul le défi **attribué** (choisi ou aléatoire) est exclu, **par destinataire** : 50 jours de jeu avant répétition.
- Une proposition non choisie n'est **pas reproposée pendant 3 jours**.
- Quand le réservoir d'un destinataire compte moins de 3 défis, son cycle repart à zéro.
- Registre : drôle, original, ancré dans le quotidien du couple, réalisable en une journée, sans dépense ni contenu intime.
- Méthode et contraintes de rédaction : § 8.4.

## 5. Cibles et contexte d'usage

### Cible prioritaire

Jeunes couples de 20 à 35 ans environ, à l'aise avec le mobile, en recherche de complicité ludique.

### Contexte d'usage : mobile d'abord

- Maquettes produites en priorité au format mobile ; le desktop est une adaptation.
- Cibles tactiles d'au moins 44 × 44 px.
- Une action principale unique par écran.
- La démo se fait sur deux téléphones : l'affichage mobile est un livrable évalué, pas un confort.

### Couple témoin

Aucun testeur identifié à ce jour. Recruter **un couple extérieur au binôme en S9**, qui utilise l'application **trois jours consécutifs en S11 ou S12**, avant le gel du code. Trois jours suffisent pour observer un cycle complet, une expiration et un gage. Les retours alimentent la section « tests utilisateurs » du dossier, rare dans les projets étudiants et systématiquement valorisée.

---

## 6. Points techniques

### 6.1 Calculs paresseux

À chaque chargement de page, un script, dans une transaction avec verrou : repère les missions échues au statut `en_cours` et applique les pénalités ; évalue la participation et la révélation ; à partir de 12 h, attribue les défis aléatoires manquants. Puis il rend la page. Solution retenue faute de cron, à justifier dans le dossier technique.

### 6.2 Tirage persistant

Les 3 propositions du jour sont enregistrées en base dès leur première génération, puis relues. Sinon, un rechargement permettrait de retirer jusqu'à obtenir un défi facile.

### 6.3 Temps serveur uniquement

- Échéances calculées en PHP, fuseau fixé explicitement : `date_default_timezone_set('Europe/Paris')`. Aucune date ne provient du client.
- Bornes de journée calculées par le calendrier (`today`, `tomorrow`), **jamais par addition de 86 400 secondes** : les jours de changement d'heure durent 23 h ou 25 h.
- Une journée de jeu est identifiée par une date `AAAA-MM-JJ`. Fenêtre de contestation calculée avec `modify('+24 hours')`. Validité du code d'invitation calculée avec `modify('+60 minutes')`.
- Secondes intercalaires sans effet (insérées à 1 h ou 2 h, heure de Paris, non représentées par PHP ni MariaDB).

### 6.4 Jeu de données

- **Jeu de données de démonstration reproductible**, rechargeable en une commande : un duo, quelques jours d'historique, un défi aléatoire, une journée blanche, un gage en attente (lot 2).

### 6.5 Notifications — [A3]

Une vraie notification push suppose service worker, abonnement VAPID et infrastructure d'envoi : hors de portée du lot 1, probablement du semestre.

Nuance à écrire dans le dossier technique : **l'événement déclencheur est synchrone**. Quand un joueur choisit, une requête PHP est déjà en cours ; l'obstacle n'est pas l'ordonnancement mais l'infrastructure d'envoi. Cela montre que la limite a été comprise, pas subie.

| Niveau | Mécanisme | Lot |
|---|---|---|
| Retenu | Révélation à la connexion, avec indicateur visuel non ambigu dès l'accueil | 1 |
| Envisageable | Courriel déclenché au choix, si l'envoi est possible sur Azrael | 3 |
| Documenté | Web push via PWA et service worker | 4 |

### 6.6 Sécurité

`password_hash()`, PDO en requêtes préparées, HTTPS, régénération de session à la connexion, protection CSRF sur les formulaires d'écriture, échappement systématique des textes libres. **Limite de 5 tentatives** puis délai sur la connexion et la saisie du code d'invitation.

### 6.7 Sessions multiples et idempotence

Un même compte ouvert sur deux appareils est le scénario normal de la soutenance. Les sessions PHP restent indépendantes et toute écriture doit être idempotente côté base. La contrainte d'unicité sur le tirage quotidien couvre le cas principal ; vérifier aussi la double validation d'une même mission.

### 6.8 Navigateurs cibles

- Safari iOS, deux dernières versions majeures.
- Chrome Android, deux dernières versions majeures.
- Firefox et Chrome sur poste fixe, dernière version (pour le jury).

---

## 7. Modèle de données

Préliminaire, à affiner puis faire valider en S8.

| Table | Champs principaux | Contraintes |
|---|---|---|
| `utilisateur` | id, pseudo, mdp_hash, couple_id (nullable), pv, date_creation | Pas d'adresse électronique ; compte réappairable après dissolution |
| `couple` | id, code_invitation, date_expiration_code, date_creation | Code à usage unique, valable 60 minutes, régénérable ; suppression en cascade |
| `participation` | id, joueur_id, date_jour, reponse, date_reponse | Unicité (`joueur_id`, `date_jour`) |
| `categorie` | id, libelle | — |
| `defi` | id, titre, description, categorie_id | — |
| `tirage` | id, date_jour, emetteur_id, destinataire_id, date_choix | Unicité (`date_jour`, `emetteur_id`) |
| `tirage_proposition` | id, tirage_id, defi_id, choisi | `choisi` renseignable par le système |
| `mission` | id, defi_id, emetteur_id, destinataire_id, origine (`choix` / `aleatoire`), date_attribution, date_revelation, date_decouverte, date_limite, statut, date_validation | — |
| `gage` | id, auteur_id, destinataire_id, texte, date_creation, statut, nb_contestations | Une contestation maximum |
| `tentative` | id, cle (pseudo ou session), type (`connexion` / `code`), date | Limitation des essais |

### Statuts de `mission`

```
attribue_non_revele → en_cours → valide → acquis
                         │          │
                         ▼          ▼
                      expire     conteste
```

- `valide → acquis` : après la fenêtre de contestation de 24 h.
- `date_decouverte` distingue « défi découvert » et « défi jamais découvert » à l'expiration.

**Point de vigilance.** Les contraintes d'unicité sur `tirage` et `participation` sont la seule protection contre un double envoi depuis deux appareils ou un double clic. Elles sont posées **en base**, pas seulement vérifiées en PHP.

## 8. Ligne éditoriale

### 8.1 Adresse à l'utilisateur (tranché le 15/09)

**Tutoiement intégral** dans toute l'application, défis compris. Pages légales en **tournures impersonnelles**. Règle écrite en tête du guide de style.

### 8.2 Voix de l'application (tranché le 15/09)

Posture de **maître du jeu** : annonce, dramatise légèrement. **Pas de « je »**. **Ne juge jamais le joueur** : l'humour vise la situation, pas la personne.

Exemple de référence : « Minuit a sonné. Le défi s'est envolé, et un cœur avec lui. »

### 8.3 Guide de style et textes d'interface

Les textes d'interface sont des fonctionnalités du projet : rédigés, versionnés et relus comme du contenu, pas improvisés dans le code. **Rédaction définitive en S6**, pendant l'intégration HTML/CSS, en remplacement des simples textes d'attente.

Écrans concernés (inventaire complet : fichier d'inventaire des écrans) :

- accueil face-à-face selon ses états, dont défi réalisé, journée blanche (trois variantes) et premier jour demain ;
- « On joue aujourd'hui ? » et attente de la réponse du partenaire ;
- salle d'attente, erreurs d'appairage, onboarding des règles ;
- tirage en éventail et carte piochée ;
- défi « choisi par le hasard » et message « le hasard a choisi pour toi » ;
- confirmation de validation ;
- écrans d'événement enchaînés : expiration (dont « jamais découvert »), perte de PV, duo dissous ; contestation au lot 2 ;
- déclenchement, rédaction et liste des gages (lot 2) ;
- dissolution du duo et suppression de compte, avec avertissement d'irréversibilité ;
- avertissement mot de passe à l'inscription ;
- erreurs et états vides.

### 8.4 Rédaction des défis

**Contrainte de point de vue.** Un défi tiré est attribué à l'autre : chaque défi est écrit **du point de vue de celui qui le reçoit**, jamais de celui qui le choisit. À poser avant d'écrire, sinon la moitié de la bibliothèque est à reprendre.

**Méthode** (catégories déduites plutôt que décidées a priori) :

1. **S3** — écrire 5 défis prototypes pour valider point de vue, longueur utile et registre ;
2. **avant S5** — porter le total à 15, librement, sans catégorie ;
3. **S5** — observer les regroupements et figer 4 à 5 catégories ;
4. **vacances de la Toussaint** — écrire les 35 restants en équilibrant les effectifs.

Familles probables, à confirmer : attention, complicité, action, expression, absurde.

La bibliothèque est le contenu que le jury lira en premier.

### 8.5 Ambiance graphique et principes d'interface

Minimalisme retenu, référence Duolingo. Hypothèse à valider : **structure minimaliste, chaleur ponctuelle**. Expressivité concentrée sur quatre moments : révélation du défi, validation, perte de PV, déclenchement du gage.

**Principes d'interface retenus au zonage (15/09)**

- **Accueil en face-à-face** : deux colonnes symétriques (avatar, pseudo, jauge de PV, état du jour, gages en lot 2), élément commun au duo au centre, action principale pleine largeur en dessous. Pas de cadrage « versus » : aucun score, aucun signe de rivalité.
- **Tirage en éventail** : 3 cartes face cachée ; la carte touchée sort du lot et se révèle. Toucher une autre carte la remplace : aucune action « reposer ». « Attribuer » valide sans écran de confirmation.
- **Confirmations** en panneau bas, y compris la déclaration « défi réalisé » depuis l'accueil. **Exception** : dissolution du duo et suppression de compte ont un écran dédié (F2, F3), exigé par le § 10 ; retour et annulation ramènent à l'écran d'origine.
- **Défi reçu réalisé** : variante de l'état 6 de l'accueil (ex-état 7, sans cadre séparé depuis le 18/09) ; le bloc du défi envoyé affiche son statut (en cours, réalisé, expiré).
- **Onboarding (B2)** : une règle par écran, puis accueil. Bouton « Passer » disponible sur chaque écran, vers l'accueil.
- **« Pas aujourd'hui »** : réponse définitive, donc confirmée en panneau bas. Une réponse envoyée après 12 h (horodatage serveur) est refusée avec un message d'erreur en texte, et l'accueil est rechargé dans son état réel.
- **Connexion** : joueur en duo vers l'accueil ; sans duo, vers B2 si un code valide a été conservé, sinon vers la salle d'attente (avec erreur si le code est invalide).
- **Code d'invitation** : valable **60 minutes**, affiché en texte statique ; un joueur connecté sans duo qui ouvre un lien arrive en salle d'attente, champ prérempli ; après dissolution, le code reçu n'est pas conservé. La salle d'attente vérifie la formation du duo au chargement de la page.
- **Salle d'attente (B1)** : compte obligatoire pour jouer. Code d'invitation régénérable par un lien discret « nouveau code » (l'ancien code est invalidé). Sortie par une flèche ← en en-tête, vers le profil (F1) complet, en état « sans duo » (pas de partenaire ni de dissolution). Retour du profil vers la salle d'attente par la même flèche.
- **Joueur déjà en duo** : la connexion mène directement à l'accueil (C1). S'il arrive sur la salle d'attente (lien d'invitation, URL), il voit une variante « déjà en duo » : ni code ni saisie, rappel du duo actuel, consigne de quitter son duo pour en rejoindre un autre, retour à l'accueil en action principale et « quitter mon duo » (vers F2) en action secondaire.
- **Navigation** : barre basse (Accueil, Gages, Historique, Profil), masquée sur les sous-écrans focalisés et les écrans d'événement.
- **Avatars** : initiale ou pictogramme généré en lot 1 ; version graphique en lot 3. Aucune photo (minimisation, § 10).
- Les **points de vie** sont le principal objet graphique, sans reposer sur la couleur seule.
- Corpus de 3 à 5 références en S5, avec pour chacune ce qui est retenu précisément.

**Convention d'annotation du zonage (18/09).** Trois formes, choisies selon ce que l'annotation désigne :

- *Étiquette in situ* : sur tout élément actif important (action principale ou secondaire, champ, lien, flèche de retour, onglet de navigation), libellé et destination écrits directement sur l'élément (`Libellé → ID`), sans flèche.
- *Annotation groupée* : une unité livrant plusieurs informations (carte, formulaire, en-tête, colonne) reçoit une seule flèche vers son conteneur, texte en liste de deux à quatre lignes, terminée par des points de suspension si elle continue.
- *Note d'écran* : une variante, un popover ou un dialogue standard n'ouvre pas de cadre séparé ; elle est documentée en note sous le cadre concerné, préfixée « Variante · » ou « Erreur · », qui ne décrit que le delta. Un cadre séparé n'est créé que si le zonage change réellement.

Seuil de lisibilité : au-delà de huit annotations sur un cadre, regrouper avant d'en ajouter une de plus.

**Composants annotés une seule fois.** Le socle C1, la carte de défi, le panneau bas de confirmation et le gabarit d'écran d'événement (D1, D2, D3, D5) sont des composants Figma isolés, annotés une seule fois. Toute modification se fait sur le composant, jamais sur une instance.


## 9. Accessibilité

**Niveau visé** : WCAG 2.1 AA sur le périmètre du lot 1, avec une déclaration honnête de ce qui est couvert ou non.

| Exigence | Mise en œuvre |
|---|---|
| Contrastes 4,5:1 (texte) et 3:1 (interface) | Vérifiés à la définition de la palette, en S5 |
| Pas d'information portée par la couleur seule | PV et statuts combinent forme, texte et couleur |
| Structure sémantique | `main`, `nav`, titres hiérarchisés, listes |
| Formulaires | `label` sur chaque champ, erreurs annoncées en texte |
| Navigation clavier | Parcours complet, focus visible conservé |
| Cibles tactiles | 44 × 44 px minimum pour toute zone interactive, sans exception : un pictogramme ou un lien plus petit reçoit une surface tactile de 44 × 44 px |
| Zoom | Utilisable à 200 % |
| Langue | `lang="fr"` |
| Animations | Respect de `prefers-reduced-motion` |
| Éventail de cartes | Cartes exposées comme boutons ordonnés, partie visible ≥ 44 px, parcours clavier, carte affichée directement sans animation en mouvement réduit |

**Compte à rebours JavaScript** : motif problématique (mise à jour automatique, pression temporelle, annonces répétées). L'échéance doit aussi être lisible en texte statique.

**Vérification en S12** : outil automatique, navigation clavier complète, lecteur d'écran mobile sur le chemin critique. Résultats et écarts forment une section du dossier.

---

## 10. Données personnelles et cadre légal

Application publique stockant des contenus produits par des personnes identifiables : le RGPD s'applique.

### Minimisation

Le modèle est déjà vertueux : un pseudonyme et une empreinte de mot de passe, ni nom, ni date de naissance, ni géolocalisation.

### Adresse électronique (tranché le 15/09)

- **Aucune adresse électronique**. Mot de passe oublié = compte perdu.
- **Avertissement en une phrase** sous le champ mot de passe, sans case à cocher.
- **Conditions d'utilisation** acceptées par une mention avec lien sous le bouton d'inscription, sans case.
- Récupération de compte (e-mail ou partenaire) documentée en lot 4.

### Contenus libres

Les gages sont rédigés librement, sans modération ni signalement. Acceptable dans un duo fermé et consenti, mais à écrire dans les conditions d'utilisation et à analyser en recul réflexif : la responsabilité éditoriale d'une plateforme sur des contenus qu'elle ne voit pas.

### Suppression (point fort à valoriser)

- **Dissolution unilatérale** du duo : suppression définitive et immédiate des missions, tirages, participations et gages, sans corbeille.
- **Comptes conservés** et réappairables ; le partenaire est informé à sa prochaine connexion, puis dirigé vers la salle d'attente.
- **Suppression de compte distincte**, qui entraîne la dissolution du duo s'il existe.
- Écran de confirmation explicite dans les deux cas, sans ressaisie du mot de passe : l'écran dédié suffit à rendre l'action consciente.

### Pages légales (livrables éditoriaux, S6)

- **Mentions légales** : éditeur, hébergeur, directeur de publication.
- **Politique de confidentialité** : données, finalité, durée, droits, contact.
- **Conditions d'utilisation** : règles du jeu, absence de modération, responsabilité des joueurs.

---

## 11. Périmètre par lots

| Lot | Contenu | Statut |
|---|---|---|
| **1 — Duo et défi du jour** | Inscription, connexion, appairage par code, participation quotidienne, tirage en éventail, choix et attribution, révélation, défi aléatoire, validation, expiration automatique, **compteur de PV et perte d'1 PV à l'expiration**, accueil face-à-face, dissolution et suppression de compte, pages légales | Obligatoire, complet |
| **2 — Gamification** | Contestation, gages (déclenchement, rédaction, liste, contestation), pénalité doublée, historique | Fortement souhaité |
| **3 — Confort** | Catégories et filtres, statistiques du couple, changement de mot de passe et de pseudo, avatars graphiques, finition graphique, notification par courriel (si faisable) | Optionnel |
| **4 — Perspectives** | Défis personnalisés (humain ou IA), preuve photo/vidéo, web push, récupération de compte, punition alternative du joueur passif | Non développé, documenté en recul réflexif |

**Élargissement du lot 1 (15/09).** Le compteur de PV et la perte à l'expiration passent au lot 1 : implémentation isolée (une colonne, quelques lignes dans le calcul d'expiration déjà prévu), sans les conséquences associées. Le lot 1 gagne un enjeu démontrable. Décision révisant Q1 de l'inventaire, à consigner au journal de bord.

La question « qui écrit les défis personnalisés, un humain ou une IA ? » reste ouverte et nourrit la section perspectives.

Le **lot 2 est une variable d'ajustement** : si le lot 1 n'est pas terminé en S11, il est abandonné sans état d'âme et documenté comme tel.

## 12. Rétroplanning

**Hypothèse de dates**, à recaler dès communication : rendu du dossier le **mercredi 16 décembre 2026**, soutenance le **vendredi 18 décembre 2026**.

| Sem. | Dates | Étape | Travail | Jalon |
|---|---|---|---|---|
| S1 | 14–20 sept | 1 | Pitch, règles du jeu, MVP | Validation du pitch |
| S2 | 21–27 sept | 2 | Dépôt Git, `debug.php`, accès Azrael, répartition, ouverture du journal de bord | Environnement opérationnel |
| S3 | 28 sept–4 oct | 3 | Scénarios, arborescence, wireframes (démarrés en S1). Arbitrage A3. 5 défis prototypes | Arbitrage A3 rendu |
| S4 | 5–11 oct | 3 | Doc fonctionnelle annotée, découpage en lots. Validation du lot 1 élargi | **Validation conception fonctionnelle** (orale) |
| S5 | 12–18 oct | 4 | Maquettes lot 1 mobile d'abord (PV et trois états d'accueil en premier), palette et typo avec contrastes, catégories figées sur 15 défis, corpus de références | — |
| S6 | 19–25 oct | 4 | HTML + CSS statiques, **textes d'interface définitifs**, pages légales | **Livraison lot 1 graphique** |
| — | 26 oct–1 nov | — | Vacances : rédaction des 35 défis restants | Bibliothèque de 50 défis |
| S7 | 2–8 nov | 5 | JavaScript lot 1 : sélection, compte à rebours (avec échéance en texte statique). Préparation du schéma BDD | — |
| S8 | 9–15 nov | 5 + 6 | Fin du JS, doc technique, modèle de données | **Livraison JS + schéma BDD validé** (oral) |
| S9 | 16–22 nov | 6 | Implémentation MariaDB, jeu de données de démo reproductible. Recrutement du couple témoin | **Livraison BDD** |
| S10 | 23–29 nov | 7 | PHP : sessions, auth, appairage, tirage. Faisabilité du courriel sur Azrael | — |
| S11 | 30 nov–6 déc | 7 | PHP : validation, révélation, expiration paresseuse, PV. Test couple témoin (ou S12) | **Lot 1 fonctionnel de bout en bout** |
| S12 | 7–13 déc | 7 | Lot 2 si le lot 1 tient, sinon consolidation. Audit accessibilité, captures sur appareil réel, sauvegarde BDD et vidéo du parcours | **Gel du code (dimanche 13)** |
| S13 | 14–16 déc | 8 | Rédaction du dossier, tests, recul réflexif, étude de cas portfolio | **Rendu mercredi 16** |
| S13 | 17–18 déc | 8 | Répétition de la démo sur deux téléphones, support de soutenance. Devenir de l'hébergement | **Soutenance vendredi 18** |

### Chemin critique

`Arbitrages (S3) → Conception fonctionnelle (S4) → Schéma validé (S8) → BDD (S9) → PHP (S10–S11)`

Tout retard sur la validation du schéma en S8 décale mécaniquement la fin : point de vigilance numéro un.

### Points de vigilance

- **Le gel du code en S12 n'est pas négociable.** Les projets étudiants échouent rarement sur le code, souvent sur le dossier écrit dans l'urgence.
- **Aucun contenu repoussé en décembre** : défis à la Toussaint, textes d'interface et pages légales en S6.
- **Douze semaines réellement productives**, pas quinze, une fois retirés les autres cours et les imprévus.

---

## 13. Risques

| Risque | Gravité | Mitigation |
|---|---|---|
| Modification des règles (dont révélation simultanée) après validation du schéma | Élevée | Arbitrages en S3 ; ce document fait foi ; toute évolution après S8 part en lot 4 |
| Absence de validation intermédiaire, dérive non détectée | Élevée | Journal de bord à chaque jalon, validations orales en S4 et S8 |
| Bibliothèque de défis bâclée | Élevée | Rédaction bloquée sur la semaine de vacances |
| Textes d'interface repoussés en décembre | Élevée | Rédaction définitive en S6 |
| Sous-estimation du temps de rédaction du dossier | Élevée | Gel du code en S12, 5 jours pleins pour l'écrit |
| Retard sur la validation du schéma | Moyenne | Schéma préparé dès S7 |
| Contrainte de point de vue découverte après les 50 défis | Moyenne | 5 prototypes en S3 |
| Démo impossible sans attendre minuit | Moyenne | Jeu de données reproductible montrant expiration, journée blanche et défi aléatoire ; vidéo du parcours complet en S12 |
| Conflits Git et travail en double | Moyenne | Répartition par écran, branches séparées |
| Plantage en soutenance | Moyenne | Répétition complète sur deux téléphones réels en S13 |
| Aucun test hors binôme | Moyenne | Couple témoin recruté en S9, testé en S11 ou S12 |
| Accessibilité annoncée mais non vérifiée | Moyenne | Contrastes en S5, audit en S12 |
| Mot de passe oublié rendant un compte inutilisable | Moyenne | Avertissement explicite à l'inscription |
| Concurrence entre un choix et l'attribution aléatoire à 12 h | Moyenne | Transaction avec verrou, horodatage serveur |
| Lot 1 élargi (PV, éventail) au détriment de la livraison | Moyenne | PV isolés sans conséquences ; animation dégradable ; lot 2 variable d'ajustement |
| Couple jouant uniquement le soir, journées blanches répétées | Moyenne | Choix assumé (§ 4.2), observé avec le couple témoin, analysé en recul réflexif |
| Esquive de la pénalité doublée par le « non » répété | Faible | Risque accepté, analysé en recul réflexif |
| Gage sans échéance | Faible | Risque accepté, analysé en recul réflexif |
| Perte de l'hébergement après la soutenance | Faible | Captures, vidéo et sauvegarde en S12 |

---

## 14. Critères de succès

1. Un couple réalise un cycle complet (tirage, choix, attribution, validation) sans intervention technique.
2. L'expiration et la perte de PV fonctionnent et sont démontrables.
3. La démo tourne 5 minutes sans erreur sur deux appareils.
4. Le périmètre livré correspond au périmètre annoncé en S4.
5. Le dossier complet est rendu dans les délais.

Le critère 4 est le plus valorisé : une petite application finie vaut mieux qu'une grande à moitié faite.

---

## 15. Après-projet et portfolio

L'application entrera au portfolio, indépendamment de sa survie fonctionnelle. Cela se prépare pendant le projet.

- **Captures de qualité** en S12 sur appareil réel, avant tout démontage.
- **Dépôt public** avec README : contexte, règles, choix techniques, limites.
- **Compte de démonstration** aux identifiants publics si l'hébergement reste actif.
- **Étude de cas en trois paragraphes** rédigée en S13 : le problème, les décisions, ce qui serait fait autrement.
- **Si Azrael est coupé** : export du code, sauvegarde de la base et vidéo de deux minutes du parcours complet, préparés en S12.

---

## 16. Décisions ouvertes

| # | Décision | Échéance | Responsable |
|---|---|---|---|
| 1 | Dates officielles de rendu et de soutenance | Dès communication | Commun |
| 2 | Format et longueur attendus du dossier | Dès communication | Commun |
| 3 | Backend ou API externe autorisés au-delà du périmètre du cours | Dès communication | Commun |
| 5 | Positionnement sur les notifications (A3) | S3 | Commun |
| 7 | Cinq défis prototypes écrits | S3 | Commun |
| 10 | Catégories de défis, déduites des quinze premiers | S5 | Commun |
| 11 | Palette et typographie, contrastes vérifiés | S5 | Commun |
| 12 | Traitement visuel des états d'accueil | S5 | B |
| 13 | Recrutement du couple témoin | S9 | A |
| 14 | Faisabilité de l'envoi de courriel sur Azrael | S10 | B |
| 15 | Devenir de l'hébergement après la soutenance | S13 | Commun |

**Tranchées le 15/09** : 4 (A1, A2), 6 (A4 et voix), 8 (adresse électronique), 9 (sort des comptes), 16 (0 PV au lot 1), 17 (frontière A / B).

**Tranchées le 16/09** : action « reposer la carte » supprimée ; aucune mention de contestabilité côté destinataire ; régénération du code en B1 ; sortie de la salle d'attente vers le profil ; retour d'A2 et A3 vers A1 · invitation si un code a été reçu ; inscription avec code valide vers B2, sinon B1 avec message d'erreur ; mode démo supprimé ; joueur déjà en duo : connexion vers C1, variante « déjà en duo » de la salle d'attente.

## 17. Note de fusion

Traçabilité de la version 2.0, utile pour le journal de bord.

**Doublons supprimés** : les deux listes de décisions ouvertes (fusionnées, la question « backend ou API externe » de la V1 avait disparu du complément et a été réintégrée) ; les deux tables de risques (fusionnées et triées par gravité, les deux risques liés à la modification des règles réunis en un seul) ; les mentions répétées du mode démo, de la démo sur deux téléphones, de la contrainte d'unicité, de l'indicateur des trois états et de la rédaction des défis pendant les vacances.

**Contenus du complément intégrés à la V1** : champs et statut du modèle temporel dans le modèle de données ; notifications courriel et push ventilées dans les lots 3 et 4 ; prototypes de défis, textes définitifs, pages légales, couple témoin, audit accessibilité et sauvegardes placés dans le rétroplanning ; jeu de données de démo ajouté à la mitigation du risque « démo impossible ».

**Point de la V1 remplacé** : les « textes d'attente » prévus en S6 deviennent les textes d'interface définitifs.

**Points de la V1 potentiellement modifiés, en attente d'arbitrage** : cycle quotidien (heure de bascule à midi) et cycle de statuts de `mission` (statut `attribue_non_revele`).

### Version 2.1 — 15 septembre 2026

Report des décisions de la séance de conception : modèle temporel et défi aléatoire (§ 4.2), validation et gages (§ 4.3, 4.5), règles de tirage (§ 4.6), calcul du temps et sécurité (§ 6), modèle de données (§ 7), adresse et voix (§ 8.1, 8.2), principes d'interface (§ 8.5), adresse électronique, CGU et suppression (§ 10), lot 1 élargi (§ 11), risques (§ 13), décisions ouvertes (§ 16). Source détaillée : fiche de décisions de l'étape 1 et inventaire des écrans.

### Version 2.2 — 15 septembre 2026

Comportement à 0 PV au lot 1 (§ 4.4) et frontière A / B sur l'accueil face-à-face (§ 2) tranchés.

### Version 2.3 — 16 septembre 2026

Tirage en éventail : suppression de l'action « reposer la carte », toucher une autre carte suffit (§ 8.5). Contestation : aucune mention côté destinataire sur l'écran du défi reçu (§ 4.3). Deux points ouverts de la séance du 15/09 clos.

### Version 2.4 — 16 septembre 2026

Salle d'attente (§ 8.5) : compte obligatoire, code régénérable par lien discret, sortie par flèche ← vers le profil complet en état « sans duo ».

### Version 2.5 — 16 septembre 2026

Mode démo supprimé du lot 1 (§ 6.4, § 11) : temps de démonstration trop court pour justifier son développement. Le jeu de données reproductible est conservé ; la mitigation du risque « démo impossible » s'appuie désormais sur lui et sur la vidéo du parcours (§ 13). Parcours d'arrivée : retours d'A2 et A3 vers A1 · invitation si code reçu ; inscription avec code valide vers B2, sinon B1 avec message d'erreur.

### Version 2.6 — 16 septembre 2026

Joueur déjà en duo (§ 8.5) : connexion vers l'accueil ; variante « déjà en duo » de la salle d'attente, qui invite à quitter son duo pour en rejoindre un autre.

### Version 2.7 — 17 septembre 2026

Suite à l'analyse du lot 1 : état 7 de l'accueil (défi réalisé) ; bascule à 12 h écrite comme choix assumé face à la persona (§ 4.2, § 13) ; code d'invitation valable 60 minutes (§ 6.3, § 7) ; exception des écrans dédiés F2 et F3 à la règle du panneau bas ; onboarding, connexion, lien reçu connecté et vérification au chargement (§ 8.5) ; contestation retirée des événements du lot 1 (§ 8.3).

### Version 2.8 — 17 septembre 2026

Arbitrage des points UX de l'analyse : confirmation en panneau bas et erreur hors délai pour « pas aujourd'hui » (§ 8.5) ; bouton « Passer » dans l'onboarding (§ 8.5) ; surface tactile de 44 × 44 px appliquée à toute zone interactive (§ 9) ; suppression de compte sans ressaisie du mot de passe (§ 10).

### Version 2.9 — 18 septembre 2026

Reprise du zonage Figma suite au retour du professeur sur la lisibilité (§ 8.5) : trois types d'annotation (étiquette in situ, annotation groupée, note d'écran), seuil de huit annotations par cadre, quatre composants partagés annotés une seule fois. Sept cadres fusionnés en notes de variante : C3 · confirmation et réalisé (sur C3 · en cours), C1 · confirmation « pas aujourd'hui » (sur C1 · état 1), C1 · état 7 (sur C1 · état 6), A1 · cas invitation (sur A1 · par défaut), C1 · premier jour de jeu (sur C1 · cas : aucun jeu aujourd'hui, ex-journée blanche), G2 (sur G1, renommé écrans d'erreur). Inventaire des écrans passé en v2.2 (34 → 27 cadres).
