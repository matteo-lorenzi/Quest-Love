# Quest & Love — Modèle de données

> **Statut : à valider en S8**, avec un enseignant, avant toute implémentation. Annexe de [`doc-technique.md`](doc-technique.md).
> Référence : cadrage unifié V2.9, § 7. **En cas d'écart, le cadrage fait foi.**
> Ce document décrit ; le SQL exécutable vit dans `sql/schema.sql`.
> Conventions : moteur InnoDB, jeu de caractères `utf8mb4`, clés primaires entières auto-incrémentées, noms en français sans accent, dates en `Europe/Paris`.
>
> **Version 1.1 — 9 octobre 2026.** Tables renommées selon le glossaire : `utilisateur` devient `joueur`, `couple` devient `duo` (écart de nom assumé avec le tableau préliminaire du cadrage § 7). Version 1.0 du 18 septembre 2026.

## 1. Vue d'ensemble

- **Comptes et duo** — `joueur`, `duo`
- **Rythme quotidien** — `participation`, `tirage`, `tirage_proposition`
- **Contenu** — `categorie`, `defi`
- **Jeu** — `mission`, `gage` _(lot 2)_
- **Technique** — `tentative`

Relations :

- un `duo` porte zéro à deux `joueur` ; un `joueur` appartient à zéro ou un `duo` ;
- un `tirage` porte exactement trois `tirage_proposition` ;
- une `mission` naît d'un `tirage` choisi ou d'une attribution aléatoire, et pointe un `defi`.

## 2. Tables

### `joueur`

| Champ | Type | Contrainte |
|---|---|---|
| `id` | entier non signé | clé primaire |
| `pseudo` | chaîne 30 | unique, non nul |
| `mdp_hash` | chaîne 255 | non nul, produit par `password_hash` |
| `duo_id` | entier non signé | nullable, clé étrangère vers `duo` |
| `pv` | entier très court non signé | non nul, défaut 5 |
| `date_creation` | date-heure | non nul |

- `duo_id` nul signifie « sans duo » : c'est ce champ qui aiguille vers B1 ou C1.
- Aucune autre donnée personnelle : minimisation, cadrage § 10.

### `duo`

| Champ | Type | Contrainte |
|---|---|---|
| `id` | entier non signé | clé primaire |
| `code_invitation` | chaîne 8 | unique, nullable une fois le duo formé |
| `date_expiration_code` | date-heure | nullable, création + 60 minutes |
| `date_creation` | date-heure | non nul |

- La régénération écrase le code et repousse l'échéance : c'est ce qui invalide l'ancien.

### `participation`

| Champ | Type | Contrainte |
|---|---|---|
| `id` | entier non signé | clé primaire |
| `joueur_id` | entier non signé | clé étrangère vers `joueur` |
| `date_jour` | date | non nul |
| `reponse` | énumération `oui` / `non` | non nul |
| `date_reponse` | date-heure | non nul |

### `categorie` et `defi`

| Table | Champ | Type | Contrainte |
|---|---|---|---|
| `categorie` | `id` | entier non signé | clé primaire |
| `categorie` | `libelle` | chaîne 40 | unique |
| `defi` | `id` | entier non signé | clé primaire |
| `defi` | `titre` | chaîne 120 | non nul |
| `defi` | `description` | texte | non nul |
| `defi` | `categorie_id` | entier non signé | clé étrangère vers `categorie` |

- Données de référence, chargées par `sql/defis.sql`. Aucune écriture depuis l'application en lot 1, aucune suppression jamais.

### `tirage` et `tirage_proposition`

| Table | Champ | Type | Contrainte |
|---|---|---|---|
| `tirage` | `id` | entier non signé | clé primaire |
| `tirage` | `date_jour` | date | non nul |
| `tirage` | `emetteur_id` | entier non signé | clé étrangère vers `joueur` |
| `tirage` | `destinataire_id` | entier non signé | clé étrangère vers `joueur` |
| `tirage` | `date_choix` | date-heure | nullable tant que le choix n'est pas fait |
| `tirage_proposition` | `id` | entier non signé | clé primaire |
| `tirage_proposition` | `tirage_id` | entier non signé | clé étrangère vers `tirage`, suppression en cascade |
| `tirage_proposition` | `defi_id` | entier non signé | clé étrangère vers `defi` |
| `tirage_proposition` | `choisi` | booléen | non nul, défaut faux |

- `choisi` est renseigné par le joueur, ou par le système lors d'une attribution aléatoire.
- `date_choix` nulle après la bascule de midi signale un joueur passif.

### `mission`

| Champ | Type | Contrainte |
|---|---|---|
| `id` | entier non signé | clé primaire |
| `defi_id` | entier non signé | clé étrangère vers `defi` |
| `emetteur_id` | entier non signé | clé étrangère vers `joueur` |
| `destinataire_id` | entier non signé | clé étrangère vers `joueur` |
| `origine` | énumération `choix` / `aleatoire` | non nul |
| `date_attribution` | date-heure | non nul |
| `date_revelation` | date-heure | nullable |
| `date_decouverte` | date-heure | nullable |
| `date_limite` | date-heure | non nul, minuit du jour d'attribution |
| `statut` | énumération, voir § 3 | non nul |
| `date_validation` | date-heure | nullable |

- `date_decouverte` nulle à l'expiration déclenche la variante « jamais découvert » de D1.

### `gage` _(lot 2)_

| Champ | Type | Contrainte |
|---|---|---|
| `id` | entier non signé | clé primaire |
| `auteur_id` | entier non signé | clé étrangère vers `joueur` |
| `destinataire_id` | entier non signé | clé étrangère vers `joueur` |
| `texte` | texte | non nul, échappé à l'affichage |
| `date_creation` | date-heure | non nul |
| `statut` | énumération `en_attente` / `realise` / `conteste` | non nul |
| `nb_contestations` | entier très court non signé | non nul, défaut 0, plafond 1 |

### `tentative`

| Champ | Type | Contrainte |
|---|---|---|
| `id` | entier non signé | clé primaire |
| `cle` | chaîne 64 | pseudo ou identifiant de session |
| `type` | énumération `connexion` / `code` | non nul |
| `date` | date-heure | non nul |

- Purge des lignes de plus de 24 h, au rattrapage, pour éviter que la table enfle.

## 3. Cycle de vie d'une mission

```
attribue_non_revele → en_cours → valide → acquis
                         │          │
                         ▼          ▼
                      expire     conteste
```

| Départ | Arrivée | Déclencheur | Effet |
|---|---|---|---|
| — | `attribue_non_revele` | choix de l'émetteur, ou attribution aléatoire à midi | création de la mission |
| `attribue_non_revele` | `en_cours` | les deux ont choisi, ou il est midi | `date_revelation` renseignée |
| `en_cours` | `valide` | déclaration du destinataire, après confirmation | `date_validation` renseignée |
| `en_cours` | `expire` | minuit passé, au rattrapage | perte de point de vie |
| `valide` | `acquis` | 24 h après validation _(lot 2)_ | définitif |
| `valide` | `conteste` | contestation de l'émetteur _(lot 2)_ | perte de point de vie |

- Toute transition vérifie le statut de départ avant d'écrire : protection contre la double validation depuis deux appareils.
- En lot 1, `acquis` et `conteste` ne sont jamais atteints ; l'énumération les prévoit pour éviter une migration.

## 4. Contraintes critiques

Posées **en base**, pas seulement vérifiées en PHP. Ce sont elles qui rendent l'application correcte sur deux appareils simultanés — scénario normal de la soutenance, cadrage § 6.7.

| Contrainte | Table | Ce qu'elle empêche |
|---|---|---|
| Unicité `(joueur_id, date_jour)` | `participation` | Deux réponses contradictoires, et le double clic |
| Unicité `(date_jour, emetteur_id)` | `tirage` | Un second tirage obtenu par rechargement, et la double attribution aléatoire |
| Unicité `(tirage_id, defi_id)` | `tirage_proposition` | Un doublon parmi les trois propositions |

## 5. Index

Au-delà des clés primaires et étrangères :

- `mission` sur `(destinataire_id, statut, date_limite)` — requête du rattrapage, la plus fréquente de l'application.
- `mission` sur `(emetteur_id, date_attribution)` — accueil et historique.
- `tirage` sur `(emetteur_id, date_jour)` — lecture des propositions du jour.
- `tentative` sur `(cle, type, date)` — comptage des dernières minutes.
- `joueur` sur `duo_id`.

## 6. Suppressions

Règles : cadrage § 10. Aucune corbeille, aucune conservation différée.

| Action | Supprimé | Conservé |
|---|---|---|
| Dissolution du duo | `mission`, `tirage`, `tirage_proposition`, `participation`, `gage` du duo, puis la ligne `duo` | Les deux `joueur`, remis à `duo_id` nul et 5 points de vie |
| Suppression de compte | Tout ce qui précède, plus la ligne `joueur` | Rien |

- Les clés étrangères des tables de jeu sont déclarées en suppression en cascade depuis `duo` : la dissolution est une seule instruction, pas une séquence à maintenir.

## 7. À trancher en S8

- [ ] Longueur et alphabet du code d'invitation — 8 caractères non ambigus proposés.
- [ ] Longueur maximale d'un titre et d'une description de défi, une fois les 15 premiers défis écrits (S5).
- [ ] Longueur maximale d'un gage.
- [ ] Purge des `tentative` : au rattrapage, ou par une instruction manuelle.
- [ ] Conservation ou non de l'historique des missions après dissolution — actuellement supprimé.
