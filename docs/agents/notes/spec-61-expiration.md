# Notes d'implémentation — spec #61, expiration, points de vie et passage à zéro

Notes des implémenteurs des tickets de la spec #61, sur la branche d'intégration `expiration-pv`.

## Ticket #78 — D1, expiration à minuit et perte de 1 PV

- `PointsDeVieService` (nouveau) : `PENALITE = 1`, seule constante de pénalité (cadrage § 4.4). `perdre(joueurId): int` relit `pv` (ligne déjà verrouillée par le rattrapage, donc les pertes d'un même rattrapage se cumulent), applique le plancher à 0, écrit par `JoueurDepot::changerPv` et rend le nouveau capital (depuis #79 : rend vrai au passage à zéro, voir plus bas).
- `MissionService::expirer(Mission, $maintenant)` (rend `Evenement[]` depuis #79, voir plus bas) : contrôle `en_cours` et `$maintenant >= dateLimite` sur la mission relue sous verrou, `changerStatut(Expire)`, puis `PointsDeVieService::perdre`. Pas de transaction propre : appelée dans celle du rattrapage. Le constructeur de `MissionService` prend désormais un quatrième argument (`PointsDeVieService`) ; `construire` le fournit.
- `RattrapageService` reçoit un `MissionService` (cinquième argument, fourni par `construire`). Ordre sous verrou : D3, révélation, annulation, **puis** expiration (décision de la revue de #59 : une révélation constatée en retard expire dans le même rattrapage). Le § 3.2 de la doc technique suit cet ordre depuis la revue de la spec.
- **Seul le joueur qui charge la page voit expirer ses missions reçues** : D1 va dans sa file de session, et la session du partenaire est inaccessible. Les missions du partenaire expirent à son propre rattrapage. Effet visible : un capital de PV du partenaire n'est à jour qu'après sa connexion (la mission de la veille ne s'affiche plus sur C1, donc rien d'autre ne change). Écart assumé au texte de la spec (« chaque mission en cours échue »).
- Sortie rapide élargie : marqueur D3, ou mission non révélée dans le duo, ou mission reçue `en_cours` dont minuit est passé (`echues`, index `ix_mission_rattrapage`). Le `TODO(simplif)` de #59 est levé.
- Événements : `Evenement::DefiExpire` et `Evenement::DefiExpireJamaisDecouvert` (`date_decouverte` nulle) ; un D1 par mission expirée, dans l'ordre des `date_limite`. `EvenementControleur` : deux branches, action vers `accueil`, textes `[TEXTE À RÉDIGER : …]`.
- Démonstration : duo 10, Rose (116) et Théo (117), missions de la veille (`CURDATE() - INTERVAL 1 DAY`) révélées. Théo a validé ; celle de Rose, jamais ouverte, est encore `en_cours` : sa connexion la fait expirer, D1 « jamais découvert », 4 PV. Vérifié à la main avec `php -S` (depuis #79, Rose part à 1 PV : D1 puis D5, 5 PV). Vaut le jour du rechargement, à toute heure, jusqu'à la première connexion de Rose.
- Tests (`RattrapageServiceTest`) : `defisRevelesLe(lou, mia, jour)` révèle deux défis à 9 h ; cas : expiration à minuit, variante découverte et unicité, double rattrapage (objet `Joueur` périmé, rejoué en séquence), deux jours manqués, journée blanche et mission annulée, révélation en retard puis expiration.

## Ticket #79 — D5, passage à zéro et retour à 5

- `PointsDeVieService::perdre(joueurId): bool` : applique la pénalité avec plancher à 0 ; à 0, écrit aussitôt `Joueur::PV_DEPART` (5, F2) et rend vrai. Le capital n'est donc jamais lu à 0 en base hors de la transaction du rattrapage.
- `Joueur::PV_DEPART` reste dans l'entité (le test de F2 d'`AppairageServiceTest` lit cette chaîne dans `AppairageService`) : `PointsDeVieService` la lit, aucune seconde définition.
- `MissionService::expirer` rend désormais les événements à empiler (`Evenement[]`, vide si rien n'a été écrit) : D1 dans sa variante, puis `Evenement::PassageAZero` (D5) si `perdre` a rendu vrai. Le choix de la variante de D1 a quitté `RattrapageService::expirer`, qui ne fait plus que concaténer : D5 suit toujours le D1 qui l'a causé (deux jours manqués à 1 PV : D1, D5, D1, capital final 4).
- `EvenementControleur` : branche D5, action vers `accueil`, texte `[TEXTE À RÉDIGER : …]`.
- Démonstration : Rose (116) part à 1 PV (colonne `pv` ajoutée à l'insertion du duo 10) ; sa connexion donne D1 « jamais découvert » puis D5, et 5 PV.
- Tests (`RattrapageServiceTest`) : 1 PV vers 5 avec D1 puis D5 et D5 une seule fois, deux expirations à 1 PV (D1, D5, D1, 4 PV), capital posé à 0 à la main qui remonte à 5 sans passer sous 0.

## Décisions de la revue de la spec

- D5 suit immédiatement le D1 qui l'a causé, pas en fin de file : deux jours manqués à 1 PV donnent D1, D5, D1 (testé).
- D3 puis D1 dans un même rattrapage est impossible : la dissolution supprime les missions (cadrage § 10, « suppression définitive et immédiate des missions »), il ne reste rien à expirer. D'où pas de test D3-D1-D5 ; l'ordre reste tenu par le code (D3 empilé avant l'expiration).
- Textes de D1 et D5 : `EvenementControleur` tire les valeurs de `PointsDeVieService::PENALITE` et `Joueur::PV_DEPART`.

## Ticket #80 — documents à jour après le lot

- README (13 écrans sur 17, C1 à D5 faits) et doc technique § 5.1 recalés sur le code ; § 6.6 (recette) laissé tel quel, il se coche à la recette.
- Reste ouvert : démonstration de Rose vérifiée au niveau du modèle (rattrapage en ligne de commande : D1 « jamais découvert », D5, 5 PV), pas encore dans le navigateur. `echeance.js` n'existe pas encore.

## Pièges

- `JourneeService::etatAccueil` n'a plus de `TODO(simplif)` sur l'état 6 : une mission n'expire qu'à minuit, quand la journée a changé, donc C1 ne la lit plus. Verrouillé par `MissionServiceTest::testLAccueilNeGardePasLEtat6UneFoisMinuitPasse` : le lendemain, deux oui posés et aucune mission du jour, la mission de la veille encore en cours n'est pas relue (état 3, pas état 6).
