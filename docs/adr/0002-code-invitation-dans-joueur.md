# Le code d'invitation vit dans `joueur`, la ligne `duo` naît à la formation

Le modèle de données (v1.3) rangeait `code_invitation` et `date_expiration_code` dans `duo` (« nullable une fois le duo formé »), et disait aussi que `joueur.duo_id` nul signifie « sans duo ». Les deux ne tiennent pas ensemble : un joueur en attente rattaché à une ligne `duo` aurait `duoId !== null`, donc passerait pour « en duo » dans le `Routeur` (niveau d'accès `duo`), dans `ConnexionControleur` et dans l'aiguillage d'A3.

Le code et son échéance passent dans `joueur`. La ligne `duo` n'est créée que lorsque le deuxième joueur saisit un code valide, en une transaction qui rattache les deux joueurs et remet leurs codes à nul. Un `duo` porte donc toujours exactement deux joueurs.

## Considered Options

- **Ligne `duo` à un seul joueur, « formé » = deux joueurs** : fidèle à la lettre du modèle v1.3. Rejetée : `duo_id` ne dit plus rien seul, il faut compter les membres, ce qui touche `Joueur`, `JoueurDepot`, le routeur (fichier commun), A2 et A3, et contredit le glossaire (« Duo : deux joueurs appairés »).
- **Code et échéance dans `joueur`** : retenu. `duo_id` nul reste la seule vérité, `Routeur`, A2 et A3 ne changent pas. Coût : une correction documentaire (modèle v1.4, cadrage § 7).
- **Table `invitation` à part** : écartée. Elle ajoute une table que ni le cadrage ni le ticket ne prévoient, pour un avantage (garder l'historique des codes) dont rien ne dit qu'il sera utile.

## Consequences

- Un code régénéré, un code faux et le code d'un joueur déjà en duo ne sont pas distinguables : le code est remis à nul à la formation et écrasé à la régénération. Un seul message, « pas valable » (cadrage § 6.6).
- La formation du duo est une transaction de `AppairageService` qui verrouille les deux `joueur` dans l'ordre des `id` : un code périmé lu avant le verrou est revérifié dessous.
- La clé étrangère `joueur.duo_id` vers `duo` est en `ON DELETE SET NULL` : supprimer la ligne `duo` à la dissolution ne peut pas être bloqué par les joueurs.
