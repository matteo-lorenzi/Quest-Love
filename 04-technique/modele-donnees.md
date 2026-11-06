# Quest & Love — Modèle de données

> **Statut : à valider en S8**, avec un enseignant, avant toute implémentation. Annexe de [`doc-technique.md`](doc-technique.md).
> Référence : cadrage unifié V2.15, § 7. **En cas d'écart, le cadrage fait foi.**
> Ce document décrit ; le SQL exécutable vit dans `sql/schema.sql`.
> Conventions : moteur InnoDB, jeu de caractères `utf8mb4`, clés primaires entières auto-incrémentées, noms en français sans accent, dates en `Europe/Paris`.
>
> **Version 1.5 — 6 novembre 2026.** Ajout de `joueur.onboarding_vu` et de `duo.date_activation` pour l'onboarding de B2 (cadrage § 8.5) : l'accès à C1 exige les deux colonnes `onboarding_vu` à 1, et « duo formé après 12 h » se calcule sur `date_activation` (cadrage § 4.2).
> Version 1.4 du 6 novembre 2026 : le code d'invitation et son échéance passent de `duo` à `joueur` ; la ligne `duo` n'est créée qu'à la formation et porte toujours deux joueurs ([ADR-0002](../docs/adr/0002-code-invitation-dans-joueur.md)). Longueur et alphabet du code tranchés (§ 7).
> Version 1.3 du 15 octobre 2026 : identifiant de connexion unique séparé du pseudo affiché, libre (cadrage § 6.6).
> Version 1.2 du 14 octobre 2026 : unicité du pseudo sans tenir compte des majuscules ; le tableau préliminaire du cadrage § 7 utilise désormais lui aussi `joueur` et `duo`.
> Version 1.1 du 9 octobre 2026 : tables renommées selon le glossaire (`utilisateur` devient `joueur`, `couple` devient `duo`). Version 1.0 du 18 septembre 2026.

## 1. Vue d'ensemble

- **Comptes et duo** — `joueur`, `duo`
- **Rythme quotidien** — `participation`, `tirage`, `tirage_proposition`
- **Contenu** — `categorie`, `defi`
- **Jeu** — `mission`, `gage` _(lot 2)_
- **Technique** — `tentative`

Relations :

- un `duo` porte exactement deux `joueur` ; un `joueur` appartient à zéro ou un `duo` ;
- un `tirage` porte exactement trois `tirage_proposition` ;
- une `mission` naît d'un `tirage` choisi ou d'une attribution aléatoire, et pointe un `defi`.

## 2. Tables

### `joueur`

| Champ | Type | Contrainte |
|---|---|---|
| `id` | entier non signé | clé primaire |
| `identifiant` | chaîne 30 | unique sans tenir compte des majuscules ni des accents (interclassement `utf8mb4_unicode_ci`), non nul ; format : cadrage § 6.6 |
| `pseudo` | chaîne 30 | non nul, non unique ; format : cadrage § 6.6 |
| `mdp_hash` | chaîne 255 | non nul, produit par `password_hash` |
| `code_invitation` | chaîne 8, ASCII sensible à la casse | unique, nullable : nul tant qu'aucun code n'est généré et une fois le duo formé |
| `date_expiration_code` | date-heure | nullable, génération + 60 minutes |
| `duo_id` | entier non signé | nullable, clé étrangère vers `duo`, suppression du duo = remise à nul |
| `onboarding_vu` | booléen | non nul, défaut 0 ; mis à 1 quand le joueur termine ou passe l'onboarding ; remis à 0 pour les deux joueurs à la formation d'un duo |
| `pv` | entier très court non signé | non nul, défaut 5 |
| `dissolution_a_signaler` | booléen | non nul, défaut 0 ; mis à 1 sur le partenaire seul quand l'autre dissout le duo ; lu par D3, qui le remet à 0 |
| `date_creation` | date-heure | non nul |

- `duo_id` nul signifie « sans duo » : c'est ce champ qui aiguille vers B1 ; avec un duo, `onboarding_vu` des deux joueurs aiguille vers B2 ou C1 (doc technique § 3.6).
- La régénération écrase le code et repousse l'échéance : c'est ce qui invalide l'ancien. Un code expiré ou nul est régénéré au chargement de B1.
- Aucune autre donnée personnelle : minimisation, cadrage § 10.

### `duo`

| Champ | Type | Contrainte |
|---|---|---|
| `id` | entier non signé | clé primaire |
| `date_creation` | date-heure | non nul |
| `date_activation` | date-heure | nullable : nulle à la formation, posée avec `$maintenant` quand les deux joueurs ont `onboarding_vu` à 1 ; instant de référence de « duo formé après 12 h » |

- Créé en une transaction, quand le deuxième joueur saisit un code valide : il porte aussitôt ses deux joueurs.
- Le code et son échéance ne sont pas ici : un joueur en attente n'a pas de `duo`, ce qui garde `joueur.duo_id` nul comme seule définition de « sans duo ».

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
| `cle` | chaîne 64 | connexion : identifiant saisi ou identifiant de session ; code : numéro du joueur ou identifiant de session |
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

### Dissolution : ce que F2 livre et ce qui reste à faire avec les tables de jeu

F2 (`AppairageService::dissoudre`) livre avant les tables de jeu. Dans une seule transaction, sous verrou des deux lignes `joueur` : les deux joueurs repassent à `pv = 5`, `onboarding_vu = 0` et `duo_id` nul ; le partenaire reçoit `dissolution_a_signaler = 1` (lu par D3, qui remet la colonne à 0) ; puis `DELETE FROM duo`. Aucune ligne de jeu n'existe encore, la cascade n'a donc rien à supprimer. Décisions closes, à ne pas rouvrir. À faire quand les tables de jeu sont créées :

- [ ] **Cascade** : `mission`, `tirage`, `participation` et `gage` portent un `duo_id` avec `ON DELETE CASCADE` vers `duo`. `tirage_proposition` suit `tirage` par la même cascade. Aucune clé étrangère vers `joueur` ne doit bloquer le `DELETE FROM duo` : les joueurs ne sont pas supprimés à la dissolution. Un oubli de cascade échoue franchement (erreur de clé étrangère à la suppression), il ne laisse pas de données orphelines en silence. `DuoDepot::supprimer` reste une seule instruction et ne change pas.
- [ ] **`sql/schema.sql`** : les tables de jeu sont supprimées avant `joueur` et `duo` (ordre des `DROP`).
- [ ] **Test** : ajouter à `tests/AppairageServiceTest.php` un cas qui crée des lignes de chaque table de jeu pour deux duos, dissout l'un et vérifie que seules les lignes du duo dissous disparaissent.
- [ ] **F3** : `SupprimerCompteControleur` appelle `dissoudre` avant de supprimer la ligne `joueur` (cadrage § 10), sans réécrire la suppression des données de jeu.
- Code d'invitation : rien à faire. `code_invitation` est déjà nul depuis la formation du duo ; B1 en génère un nouveau à son prochain affichage (cadrage § 7).
- `tentative` n'est pas une donnée de jeu : la dissolution n'y touche pas.

## 7. À trancher en S8

- [x] Longueur et alphabet du code d'invitation — 8 caractères parmi 32 : A à Z sans I ni O, 2 à 9 (tranché le 06/11).
- [ ] Longueur maximale d'un titre et d'une description de défi, une fois les 15 premiers défis écrits (S5).
- [ ] Longueur maximale d'un gage.
- [ ] Purge des `tentative` : au rattrapage, ou par une instruction manuelle.
- [ ] Conservation ou non de l'historique des missions après dissolution — actuellement supprimé.
