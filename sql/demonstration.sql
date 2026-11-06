-- Jeu de démonstration — A3 et suivants — lot 1 — commun. Rechargé par php sql/reinitialiser.php ; jamais chargé sur Azrael avec de vraies données (doc technique § 6.5).
-- Complété ticket par ticket : duo, historique et missions arrivent avec leurs écrans.

SET NAMES utf8mb4;

-- Deux joueurs sans duo, les personas du parcours 1. Mot de passe de démonstration, commun aux deux : Quest-Love-demo-2026!
-- Deux comptes plutôt qu'un : on vérifie à la main que changer d'identifiant dans la même session ne remet pas le compteur à zéro.
INSERT INTO joueur (identifiant, pseudo, mdp_hash, date_creation) VALUES
  ('sylvianne', 'Sylvianne', '$2y$10$rjwoUdt8qkxCXqIp/CTRieI7ivw1Rz0.gNWneozgKyCpRLBmr/38W', '2026-10-01 09:00:00'),
  ('joul', 'Joul', '$2y$10$rjwoUdt8qkxCXqIp/CTRieI7ivw1Rz0.gNWneozgKyCpRLBmr/38W', '2026-10-01 09:05:00');

-- Un duo déjà formé : Léa et Tom, pour la variante « déjà en duo » de B1 et une connexion qui mène à l'accueil. Même mot de passe de démonstration.
INSERT INTO duo (id, date_creation) VALUES (1, '2026-10-02 18:00:00');
INSERT INTO joueur (identifiant, pseudo, mdp_hash, duo_id, date_creation) VALUES
  ('lea', 'Léa', '$2y$10$rjwoUdt8qkxCXqIp/CTRieI7ivw1Rz0.gNWneozgKyCpRLBmr/38W', 1, '2026-10-02 17:50:00'),
  ('tom', 'Tom', '$2y$10$rjwoUdt8qkxCXqIp/CTRieI7ivw1Rz0.gNWneozgKyCpRLBmr/38W', 1, '2026-10-02 17:55:00');
