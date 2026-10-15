-- Jeu de démonstration — A3 et suivants — lot 1 — commun. Rechargé par php sql/reinitialiser.php ; jamais chargé sur Azrael avec de vraies données (doc technique § 6.5).
-- Complété ticket par ticket : duo, historique et missions arrivent avec leurs écrans.

SET NAMES utf8mb4;

-- Deux joueurs sans duo, les personas du parcours 1. Mot de passe de démonstration, commun aux deux : Quest-Love-demo-2026!
-- Deux comptes plutôt qu'un : on vérifie à la main que changer d'identifiant dans la même session ne remet pas le compteur à zéro.
INSERT INTO joueur (identifiant, pseudo, mdp_hash, date_creation) VALUES
  ('sylvianne', 'Sylvianne', '$2y$10$rjwoUdt8qkxCXqIp/CTRieI7ivw1Rz0.gNWneozgKyCpRLBmr/38W', '2026-10-01 09:00:00'),
  ('joul', 'Joul', '$2y$10$rjwoUdt8qkxCXqIp/CTRieI7ivw1Rz0.gNWneozgKyCpRLBmr/38W', '2026-10-01 09:05:00');
