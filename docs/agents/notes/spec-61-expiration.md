# Notes d'implémentation — spec #61, expiration, points de vie et passage à zéro

Notes des implémenteurs des tickets de la spec #61, sur la branche d'intégration `expiration-pv`.

## Ticket #78 — D1, expiration à minuit et perte de 1 PV

- `PointsDeVieService` (nouveau) : `PENALITE = 1`, seule constante de pénalité (cadrage § 4.4). `perdre(joueurId): int` relit `pv` (ligne déjà verrouillée par le rattrapage, donc les pertes d'un même rattrapage se cumulent), applique le plancher à 0, écrit par `JoueurDepot::changerPv` et rend le nouveau capital : #79 s'y branche pour le passage à 0 (retour à 5, D5).
- `MissionService::expirer(Mission, $maintenant): bool` : contrôle `en_cours` et `$maintenant >= dateLimite` sur la mission relue sous verrou, `changerStatut(Expire)`, puis `PointsDeVieService::perdre`. Pas de transaction propre : appelée dans celle du rattrapage. Le constructeur de `MissionService` prend désormais un quatrième argument (`PointsDeVieService`) ; `construire` le fournit.
- `RattrapageService` reçoit un `MissionService` (cinquième argument, fourni par `construire`). Ordre sous verrou : D3, révélation, annulation, **puis** expiration (décision de la revue de #59 : une révélation constatée en retard expire dans le même rattrapage). Le § 3.2 de la doc technique met encore l'expiration en tête : à corriger avec #80 si le binôme le confirme.
- **Seul le joueur qui charge la page voit expirer ses missions reçues** : D1 va dans sa file de session, et la session du partenaire est inaccessible. Les missions du partenaire expirent à son propre rattrapage. Effet visible : un capital de PV du partenaire n'est à jour qu'après sa connexion (la mission de la veille ne s'affiche plus sur C1, donc rien d'autre ne change). Écart assumé au texte de la spec (« chaque mission en cours échue »).
- Sortie rapide élargie : marqueur D3, ou mission non révélée dans le duo, ou mission reçue `en_cours` dont minuit est passé (`echues`, index `ix_mission_rattrapage`). Le `TODO(simplif)` de #59 est levé.
- Événements : `Evenement::DefiExpire` et `Evenement::DefiExpireJamaisDecouvert` (`date_decouverte` nulle) ; un D1 par mission expirée, dans l'ordre des `date_limite`. `EvenementControleur` : deux branches, action vers `accueil`, textes `[TEXTE À RÉDIGER : …]`.
- Démonstration : duo 10, Rose (116) et Théo (117), missions de la veille (`CURDATE() - INTERVAL 1 DAY`) révélées. Théo a validé ; celle de Rose, jamais ouverte, est encore `en_cours` : sa connexion la fait expirer, D1 « jamais découvert », 4 PV. Vérifié à la main avec `php -S`. Vaut le jour du rechargement, à toute heure, jusqu'à la première connexion de Rose.
- Tests (`RattrapageServiceTest`) : `defisRevelesLe(lou, mia, jour)` révèle deux défis à 9 h ; cas : expiration à minuit, variante découverte et unicité, double rattrapage (objet `Joueur` périmé, rejoué en séquence), deux jours manqués, journée blanche et mission annulée, révélation en retard puis expiration.

## Pour #79 (passage à zéro)

- Brancher le retour à 5 dans `PointsDeVieService::perdre` (son retour dit le capital) et empiler D5 après le D1 concerné dans `RattrapageService::expirer`.
- `Joueur::PV_DEPART` vit encore dans l'entité (son commentaire prévoit que `PointsDeVieService` la reprenne) ; `AppairageServiceTest` lit la chaîne `Joueur::PV_DEPART` dans `AppairageService` : à adapter si la constante déménage.
- Démonstration : il manque encore le joueur à 1 PV.

## Pièges

- `JourneeService::etatAccueil` garde son `TODO(simplif)` (mission expirée après découverte vue en état 6) : en pratique une mission n'expire qu'à minuit, quand la journée a changé, donc C1 ne la montre plus.
