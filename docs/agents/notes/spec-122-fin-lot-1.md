# Notes d'implémentation — spec #122, fin du lot 1

Une section par ticket (#123 à #126, puis #129). Chaque ticket y écrit sa ligne « Livré : … » ; le manifeste (doc technique § 5) est recoché par #129.

## Ticket #126 : démonstration (attente de B2, D3 à la connexion, historique de Léa et Tom)

- `sql/demonstration.sql` : duo 11 (Chloé `chloe` onboarding fini, Enzo `enzo` non, `date_activation` nulle) ; Marc `marc`, sans duo, `dissolution_a_signaler = 1` ; trois jours d'historique du duo 1 (tirages 9 à 14, défis 1 à 15 seulement).
- Léa et Tom ont désormais des numéros fixés (3 et 4) et 4 PV : un défi expiré chacun dans l'historique. Rien n'est écrit pour aujourd'hui, C1 reste à l'état 1.
- Missions d'historique en statut terminal (`valide`, `expire`) : le rattrapage n'y touche pas, aucun D1 à leur connexion.
- D3 de Marc ne se montre qu'une fois : recharger la base pour le revoir.
- Vérifié dans le navigateur (`php -S 127.0.0.3`, 375 px, un contexte neuf par compte, après 18 h) : Chloé arrive sur l'attente de B2 (« En attente de Enzo ») et ouvre F1 ; Enzo sur l'onboarding (1 / 3) ; Marc sur D3 puis B1. Léa, après 12 h, lit la journée blanche « n'avait pas répondu à 12 h » : l'état 1 ne se voit qu'avant 12 h, comme pour tout duo sans réponse.
- Prochain numéro libre : duo 12, joueur 121, tirage 15.
- Livré : `sql/demonstration.sql` (duo 11, Marc, historique de Léa et Tom), doc technique § 6.5.
