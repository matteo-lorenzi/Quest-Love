# Quest & Love

Application web qui transforme le quotidien d'un couple en jeu : défi du jour tiré au sort, attribué au partenaire, points de vie, gages.

**Binôme :** Sheyrel, Matteo — Master 2
**Document de référence :** [`01-cadrage/cadrage-unifie.md`](01-cadrage/cadrage-unifie.md) (V2.9, 18/09/2026). En cas d'écart entre deux documents, le cadrage fait foi.

## Où trouver quoi

| Dossier | Contenu |
|---|---|
| [`01-cadrage/`](01-cadrage/) | Cadrage unifié (source de vérité), documentation fonctionnelle, Lean Canvas |
| [`02-recherche-utilisateur/`](02-recherche-utilisateur/) | Proto-personas, parcours utilisateur |
| [`03-conception/`](03-conception/) | Inventaire des écrans, carte et parcours de navigation (maquettes : Figma, voir ressources externes) |
| [`04-technique/`](04-technique/) | Description technique, modèle de données |
| `00-donnees-sensibles/` | **Local, non versionné** (RGPD) : enregistrements, questionnaires bruts, consentements. Absent du dépôt : chaque membre le crée sur son poste. |
| [`99-archives/`](99-archives/) | Versions périmées, conservées pour l'historique (dont les captures PNG des wireframes antérieures au 18/09) |

### Détail des documents

| Document | Rôle | Statut |
|---|---|---|
| `01-cadrage/cadrage-unifie.md` | Cahier des charges. Toute règle est arrêtée ici **avant** d'être codée. | V2.9 — à jour |
| `01-cadrage/doc-fonctionnelle.md` | Documentation fonctionnelle : écrans, règles, lots. | V1.0 (02/10/2026) — rédigée, maquettes liées au Figma, en attente de validation orale S4 |
| `01-cadrage/lean-canvas.json` | Cadrage stratégique : problème, utilisateurs, hypothèses. | Encadrés 7 et 8 à compléter |
| `02-recherche-utilisateur/proto-personas.*` | Sylvianne (initiatrice) et Joul (invité sceptique). | À confirmer par la recherche utilisateur |
| `02-recherche-utilisateur/parcours-utilisateur.*` | Customer journey map : découverte → fidélisation. | À jour |
| `03-conception/inventaire-ecrans.md` | Liste des écrans et avancement du zonage Figma. | v2.2 — lot 1 complet (27/27 cadres) |
| `04-technique/doc-technique.md` | Description technique : architecture, mécanismes, interface, manifeste des fichiers par lot, exploitation. Spécification écrite avant le code. | V1.0 — à confronter au code à partir de S10 |
| `04-technique/modele-donnees.md` | Annexe : tables, contraintes, index, cycle de vie d'une mission. | V1.0 — **à valider en S8** |

Les fichiers `.json` sont les fichiers de travail générés par l'outil de conception ; les `.md` du même nom en sont la version lisible. Les deux se modifient ensemble.

## Ressources externes

- Fichier Figma de zonage (maquettes du lot 1, 27 cadres) : https://www.figma.com/design/K4aQOZqlqUUh6oKx3lLloG/Quest---Love-%E2%80%94-Wireframes--zonage-?node-id=1-2
- Parcours 1 — arrivée et appairage (FigJam) : https://www.figma.com/board/2OL6KmD8ZdFMPjKEeg4nKe/Quest---Love---Parcours-1---arriv%C3%A9e-et-appairage--v3-?node-id=0-1
- Parcours 2 — journée de jeu (FigJam) : https://www.figma.com/board/EALHmvQwl5XW95yqfv0ek4/Quest---Love---Parcours-2---journ%C3%A9e-de-jeu--v3-
- Parcours 3 — fin de défi et événements (FigJam) : https://www.figma.com/board/468gJJ44NTXHyviYEMsgW3/Quest---Love---Parcours-3---fin-de-d%C3%A9fi-et-%C3%A9v%C3%A9nements--v3-
- Application déployée : https://azrael.sha.univ-poitiers.fr/~mlorenzi/
- Code source : https://azrael.sha.univ-poitiers.fr/~mlorenzi/debug.php

## Convention de nommage

- Noms de fichiers en minuscules, mots séparés par des tirets, **sans accent ni espace** (les accents cassent l'encodage entre systèmes ; ils restent obligatoires dans le contenu des documents).
- Contenu en français. Exceptions tolérées : termes métier sans équivalent (`lean-canvas`, `lo-fi`).
- Numérotation des dossiers (`01-`, `02-`…) pour forcer l'ordre de lecture. `99-` est réservé aux archives.
- Pas de date dans le nom des documents vivants : git en assure le suivi. Date obligatoire en préfixe `AAAA-MM-JJ_` dès qu'un fichier part dans `99-archives/`.
- Version suffixée `-vNN` uniquement sur un document figé. Jamais `final`, `final2`, `vraiment-final`.
