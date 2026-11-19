# Notes d'implémentation — spec #89, C2 après 12 h

- `TirageService::aChoisi(Joueur, $maintenant): bool` : vrai si le tirage du jour de l'émetteur a une mission, quel que soit son statut (la mission annulée par le rattrapage compte). Testé dans `TirageServiceTest`.
- `TirageControleur::tirageOuSortie` : sur `JourneeBlancheSansChoix`, le message « midi passé » ne va qu'à celui qui n'a pas choisi ; l'autre est redirigé vers l'accueil, dont la journée blanche explique la situation.
- Vérifié par HTTP (`php -S`) après 12 h sur le duo 8 : Jade, C2 → 302 vers l'accueil ; Owen, C2 → « midi passé ». La limite de démonstration du duo 8 est notée au § 6.5 de la doc technique.
