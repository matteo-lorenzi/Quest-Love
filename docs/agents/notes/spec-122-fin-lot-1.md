# Notes d'implémentation — spec #122, fin du lot 1

Une section par ticket (#123 à #126, puis #129). Chaque ticket y écrit sa ligne « Livré : … » ; le manifeste (doc technique § 5) est recoché par #129.

## Ticket #125 : bibliothèque d'environ cinquante défis

- `sql/defis.sql` : cinquante défis, Attention 13, Complicité 12, Action 13, Expression 12. Les quinze premiers gardent leurs numéros 1 à 15 (le jeu de démonstration y renvoie) ; les numéros 16 à 50 sont un premier jet de l'agent, à valider, corriger ou remplacer par le binôme dans la relecture de la PR. Un défi ajouté plus tard se place en fin de liste, pour ne décaler aucun numéro.
- `tests/SchemaTest.php` : le test du rechargement attend désormais 45 à 55 défis et 10 à 14 par catégorie (il attendait 12 à 18 défis). Pas d'autre test de la couche modèle (spec, Testing Decisions).
- Manifeste (doc technique § 5, ligne de `sql/defis.sql`) laissé tel quel pour #129 : sa mention « en cours : jeu provisoire » est devenue fausse.
- Livré : `sql/defis.sql` (bibliothèque d'environ cinquante défis en quatre catégories).
