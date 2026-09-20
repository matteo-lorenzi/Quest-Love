# Quest & Love

Application web qui transforme le quotidien d'un couple en jeu : défi du jour tiré au sort, attribué au partenaire, points de vie, gages.

**Binôme :** Sheyrel, Matteo — Master 2
**Document de référence :** [`01-cadrage/cadrage-unifie.md`](01-cadrage/cadrage-unifie.md) (V2.9, 18/09/2026). En cas d'écart entre deux documents, le cadrage fait foi.

## Où trouver quoi

| Dossier | Contenu |
|---|---|
| [`01-cadrage/`](01-cadrage/) | Cadrage unifié (source de vérité), documentation fonctionnelle, Lean Canvas |
| [`02-recherche-utilisateur/`](02-recherche-utilisateur/) | Proto-personas, parcours utilisateur |
| [`03-conception/`](03-conception/) | Inventaire des écrans, maquettes |
| [`04-technique/`](04-technique/) | Description technique, modèle de données |
| [`99-archives/`](99-archives/) | Versions périmées, conservées pour l'historique |

### Détail des documents

| Document | Rôle | Statut |
|---|---|---|
| `01-cadrage/cadrage-unifie.md` | Cahier des charges. Toute règle est arrêtée ici **avant** d'être codée. | V2.9 — à jour |
| `01-cadrage/doc-fonctionnelle.md` | Documentation fonctionnelle : écrans, règles, lots. | Trame — rédaction en cours |
| `01-cadrage/lean-canvas.json` | Cadrage stratégique : problème, utilisateurs, hypothèses. | Encadrés 7 et 8 à compléter |
| `02-recherche-utilisateur/proto-personas.*` | Sylvianne (initiatrice) et Joul (invité sceptique). | À confirmer par la recherche utilisateur |
| `02-recherche-utilisateur/parcours-utilisateur.*` | Customer journey map : découverte → fidélisation. | À jour |
| `03-conception/inventaire-ecrans.md` | Liste des écrans et avancement du zonage Figma. | v2.2 — lot 1 complet (27/27 cadres) |
| `03-conception/wireframes/` | Captures PNG du zonage Figma : un dossier par bande (00 à 04), fichiers numérotés dans l'ordre du Figma. | Lot 1 — captures antérieures à la reprise du 18/09 (34 cadres + légende), à réexporter sur les 27 cadres actuels |
| `04-technique/doc-technique.md` | Description technique : architecture, mécanismes, interface, manifeste des fichiers par lot, exploitation. Spécification écrite avant le code. | V1.0 — à confronter au code à partir de S10 |
| `04-technique/modele-donnees.md` | Annexe : tables, contraintes, index, cycle de vie d'une mission. | V1.0 — **à valider en S8** |

Les fichiers `.json` sont les fichiers de travail générés par l'outil de conception ; les `.md` du même nom en sont la version lisible. Les deux se modifient ensemble.

## Ressources externes

- Fichier Figma de zonage : https://www.figma.com/design/K4aQOZqlqUUh6oKx3lLloG/Quest---Love-—-Wireframes--zonage-
- Application déployée : `[URL à compléter]`
- Code source : `[URL à compléter]`

## Convention de nommage

- Noms de fichiers en minuscules, mots séparés par des tirets, **sans accent ni espace** (les accents cassent l'encodage entre systèmes ; ils restent obligatoires dans le contenu des documents).
- Contenu en français. Exceptions tolérées : termes métier sans équivalent (`lean-canvas`, `lo-fi`).
- Numérotation des dossiers (`01-`, `02-`…) pour forcer l'ordre de lecture. `99-` est réservé aux archives.
- Pas de date dans le nom des documents vivants : git en assure le suivi. Date obligatoire en préfixe `AAAA-MM-JJ_` dès qu'un fichier part dans `99-archives/`.
- Version suffixée `-vNN` uniquement sur un document figé. Jamais `final`, `final2`, `vraiment-final`.
