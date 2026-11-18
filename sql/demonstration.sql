-- Jeu de démonstration — A3 et suivants — lot 1 — commun. Rechargé par php sql/reinitialiser.php ; jamais chargé sur Azrael avec de vraies données (doc technique § 6.5).
-- Complété ticket par ticket : duo, historique et missions arrivent avec leurs écrans.

SET NAMES utf8mb4;

-- Deux joueurs sans duo, les personas du parcours 1. Mot de passe de démonstration, commun aux deux : Quest-Love-demo-2026!
-- Deux comptes plutôt qu'un : on vérifie à la main que changer d'identifiant dans la même session ne remet pas le compteur à zéro.
INSERT INTO joueur (identifiant, pseudo, mdp_hash, date_creation) VALUES
  ('sylvianne', 'Sylvianne', '$2y$10$rjwoUdt8qkxCXqIp/CTRieI7ivw1Rz0.gNWneozgKyCpRLBmr/38W', '2026-10-01 09:00:00'),
  ('joul', 'Joul', '$2y$10$rjwoUdt8qkxCXqIp/CTRieI7ivw1Rz0.gNWneozgKyCpRLBmr/38W', '2026-10-01 09:05:00');

-- Un duo déjà formé : Léa et Tom, pour la variante « déjà en duo » de B1 et une connexion qui mène à l'accueil. Même mot de passe de démonstration.
INSERT INTO duo (id, date_creation, date_activation) VALUES (1, '2026-10-02 18:00:00', '2026-10-02 18:05:00');
-- Onboarding déjà fini par les deux : leur connexion mène à l'accueil.
INSERT INTO joueur (identifiant, pseudo, mdp_hash, duo_id, onboarding_vu, date_creation) VALUES
  ('lea', 'Léa', '$2y$10$rjwoUdt8qkxCXqIp/CTRieI7ivw1Rz0.gNWneozgKyCpRLBmr/38W', 1, 1, '2026-10-02 17:50:00'),
  ('tom', 'Tom', '$2y$10$rjwoUdt8qkxCXqIp/CTRieI7ivw1Rz0.gNWneozgKyCpRLBmr/38W', 1, 1, '2026-10-02 17:55:00');
-- Léa et Tom n'ont pas répondu aujourd'hui : C1 état 1 (« On joue aujourd'hui ? »).

-- Un second duo pour C1 état 2 : Inès a dit oui aujourd'hui, Hugo pas encore. Inès voit l'attente du partenaire, Hugo la question.
INSERT INTO duo (id, date_creation, date_activation) VALUES (2, '2026-10-03 18:00:00', '2026-10-03 18:05:00');
INSERT INTO joueur (id, identifiant, pseudo, mdp_hash, duo_id, onboarding_vu, date_creation) VALUES
  (100, 'ines', 'Inès', '$2y$10$rjwoUdt8qkxCXqIp/CTRieI7ivw1Rz0.gNWneozgKyCpRLBmr/38W', 2, 1, '2026-10-03 17:50:00'),
  (101, 'hugo', 'Hugo', '$2y$10$rjwoUdt8qkxCXqIp/CTRieI7ivw1Rz0.gNWneozgKyCpRLBmr/38W', 2, 1, '2026-10-03 17:55:00');
-- La journée de jeu est celle du rechargement : CURDATE() est la seule date relative du jeu de démonstration, l'application n'écrit jamais ainsi (§ 3.8).
-- TODO(simplif): l'état 2 ne vaut que le jour du rechargement et sans HORLOGE_DECALAGE qui change de jour, à revoir si la démonstration se prépare la veille.
INSERT INTO participation (duo_id, joueur_id, date_jour, reponse, date_reponse) VALUES
  (2, 100, CURDATE(), 'oui', CONCAT(CURDATE(), ' 08:00:00'));
