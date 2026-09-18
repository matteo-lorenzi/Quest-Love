# Quest & Love — Inventaire des écrans (v2.2)

> 18 septembre 2026. Remplace l'inventaire v2.1. Répercute la reprise du zonage (retour enseignant sur la lisibilité). Référence : cadrage unifié V2.9.
> Organisation identique au fichier Figma de zonage : **une page unique, une bande par parcours** (sections 00 à 05).
> Statut : ✅ zoné · ⬜ à zoner. Lot 2 : zones en pointillés sur les cadres du lot 1, écrans dédiés en bande 05.
> Nommage des cadres : `ID · Écran — état` ; cas particuliers « cas : … » ; précisions en pastille sous le titre.
> Une variante ou un dialogue standard n'a plus systématiquement son propre cadre : elle est documentée en note sous le cadre concerné quand le zonage ne change pas.

## Bande 01 — Arrivée et appairage (périmètre A)

| ID | Écran / état | Lot | Statut |
|---|---|---|---|
| A1 | Accueil public | 1 | ✅ |
| A1 | Accueil public · variante : invitation reçue (créer un compte / j'ai déjà un compte ; note sous le cadre A1, pas de cadre dédié) | 1 | ✅ |
| A2 | Inscription (avertissement mot de passe, mention CGU, erreurs, code prérempli ; retour vers A1 · invitation si code reçu, code conservé) | 1 | ✅ |
| A3 | Connexion (erreurs, délai après 5 échecs, code conservé) | 1 | ✅ |
| A4 | Pages légales (gabarit unique) | 1 | ✅ |
| B1 | Salle d'attente (flèche ← vers F1, code à partager, lien « nouveau code », saisie, erreurs : propre code, expiré (60 min) ou régénéré, délai) | 1 | ✅ |
| B1 | Salle d'attente · cas : déjà en duo (flèche ← vers C1, duo actuel, consigne, retour C1, quitter mon duo vers F2) | 1 | ✅ |
| B2 | Duo formé + onboarding des règles (une règle par écran, bouton « Passer ») | 1 | ✅ |
| B3 | Règles du jeu | 1 | ✅ |

## Bande 02 — Journée de jeu (colonnes : A · zone d'action : B)

| ID | Écran / état | Lot | Statut |
|---|---|---|---|
| C1 | État 1 : on joue aujourd'hui ? (porte en variante la confirmation « pas aujourd'hui » : panneau bas, erreur hors délai) | 1 | ✅ |
| C1 | État 2 : attente du partenaire | 1 | ✅ |
| C1 | État 3 : à toi de piocher | 1 | ✅ |
| C2 | Tirage · éventail de 3 cartes | 1 | ✅ |
| C2 | Tirage · carte piochée (sans action « reposer ») | 1 | ✅ |
| C1 | État 4 : défi envoyé, en attente de révélation | 1 | ✅ |
| D2 | Événement : le hasard a choisi pour toi | 1 | ✅ |
| C1 | État 5 : révélation | 1 | ✅ |
| C1 | État 6 : défi reçu en cours (porte en variante l'ancien état 7 : défi réalisé, statut du défi envoyé) | 1 | ✅ |
| C3 | Défi reçu · en cours / confirmation « réalisé » / réalisé (sans navigation, sans mention de contestabilité) | 1 | ✅ |
| C1 | Cas : aucun jeu aujourd'hui (journée blanche en 3 variantes de texte, ou premier jour de jeu demain en variante) | 1 | ✅ |

## Bande 03 — Fin de défi et événements

| ID | Écran / état | Lot | Statut |
|---|---|---|---|
| D1 | Défi expiré, −1 PV (variante « jamais découvert ») | 1 | ✅ |
| D5 | 0 PV, retour à 5 | 1 | ✅ |
| D3 | Duo dissous (partenaire) | 1 | ✅ |

## Bande 04 — Profil, duo et système (périmètre A)

| ID | Écran / état | Lot | Statut |
|---|---|---|---|
| F1 | Profil (pseudo, partenaire, règles, pages légales, déconnexion, suppression de compte) | 1 | ✅ |
| F1 | Profil · cas : sans duo (accès depuis B1, sans partenaire ni dissolution, flèche ← vers B1) | 1 | ✅ |
| F2 | Dissolution du duo (écran dédié, avertissement, confirmation, retour à l'écran d'origine) | 1 | ✅ |
| F3 | Suppression de compte (avertissement, confirmation, sans mot de passe) | 1 | ✅ |
| G1 | Écrans d'erreur (page introuvable ; variante : erreur serveur ou action impossible, ex-G2) | 1 | ✅ |

## Bande 05 — Lot 2 · gamification

| ID | Écran / état | Lot | Statut |
|---|---|---|---|
| C3+ | Défi envoyé réalisé : lien discret « contester », confirmation, contesté | 2 | ⬜ |
| D1+ | Défi expiré, −2 PV (gage en attente) | 2 | ⬜ |
| D4 | Défi contesté, perte de PV | 2 | ⬜ |
| D5+ | 0 PV : gage déclenché | 2 | ⬜ |
| D6 | Partenaire : rédige le gage | 2 | ⬜ |
| D7 | Gage contesté, retour en attente | 2 | ⬜ |
| E1 | Rédaction d'un gage | 2 | ⬜ |
| E2 | Liste des gages en attente | 2 | ⬜ |
| E3 | Détail d'un gage | 2 | ⬜ |
| E4 | Historique | 2 | ⬜ |

## Lot 3 (non zoné)

F4 Statistiques du couple · F5 Changement de mot de passe / pseudo · avatars graphiques.

## Avancement lot 1

27 cadres zonés sur 27 (34 avant la reprise du 18/09 : sept cadres fusionnés en notes de variante sur le cadre qui reste). Lot 1 complet et relu pour la lisibilité. Reste la bande 05 (lot 2).