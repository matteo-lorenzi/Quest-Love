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

-- Un troisième duo pour la journée blanche : Nora a dit non aujourd'hui, Paul voit la journée blanche sans qu'on lui pose la question.
INSERT INTO duo (id, date_creation, date_activation) VALUES (3, '2026-10-04 18:00:00', '2026-10-04 18:05:00');
INSERT INTO joueur (id, identifiant, pseudo, mdp_hash, duo_id, onboarding_vu, date_creation) VALUES
  (102, 'nora', 'Nora', '$2y$10$rjwoUdt8qkxCXqIp/CTRieI7ivw1Rz0.gNWneozgKyCpRLBmr/38W', 3, 1, '2026-10-04 17:50:00'),
  (103, 'paul', 'Paul', '$2y$10$rjwoUdt8qkxCXqIp/CTRieI7ivw1Rz0.gNWneozgKyCpRLBmr/38W', 3, 1, '2026-10-04 17:55:00');
-- Même limite que l'état 2 : la journée blanche ne vaut que le jour du rechargement (CURDATE()).
INSERT INTO participation (duo_id, joueur_id, date_jour, reponse, date_reponse) VALUES
  (3, 102, CURDATE(), 'non', CONCAT(CURDATE(), ' 08:30:00'));

-- Un quatrième duo activé aujourd'hui à 14 h : Zoé et Max lisent « premier jour de jeu demain » jusqu'à minuit.
-- Rechargé avant 14 h, l'activation est encore à venir mais déjà après 12 h : le cas s'affiche quand même.
INSERT INTO duo (id, date_creation, date_activation) VALUES (4, CONCAT(CURDATE(), ' 13:50:00'), CONCAT(CURDATE(), ' 14:00:00'));
INSERT INTO joueur (id, identifiant, pseudo, mdp_hash, duo_id, onboarding_vu, date_creation) VALUES
  (104, 'zoe', 'Zoé', '$2y$10$rjwoUdt8qkxCXqIp/CTRieI7ivw1Rz0.gNWneozgKyCpRLBmr/38W', 4, 1, CONCAT(CURDATE(), ' 13:40:00')),
  (105, 'max', 'Max', '$2y$10$rjwoUdt8qkxCXqIp/CTRieI7ivw1Rz0.gNWneozgKyCpRLBmr/38W', 4, 1, CONCAT(CURDATE(), ' 13:45:00'));

-- Un cinquième duo pour C1 état 3 : Eva et Léo ont dit oui aujourd'hui, chacun lit « à toi de piocher » et ouvre C2.
-- Aucun tirage écrit ici : le premier passage sur C2 le crée. Même limite que l'état 2 : ne vaut que le jour du rechargement (CURDATE()), avant 12 h.
INSERT INTO duo (id, date_creation, date_activation) VALUES (5, '2026-10-05 18:00:00', '2026-10-05 18:05:00');
INSERT INTO joueur (id, identifiant, pseudo, mdp_hash, duo_id, onboarding_vu, date_creation) VALUES
  (106, 'eva', 'Eva', '$2y$10$rjwoUdt8qkxCXqIp/CTRieI7ivw1Rz0.gNWneozgKyCpRLBmr/38W', 5, 1, '2026-10-05 17:50:00'),
  (107, 'leo', 'Léo', '$2y$10$rjwoUdt8qkxCXqIp/CTRieI7ivw1Rz0.gNWneozgKyCpRLBmr/38W', 5, 1, '2026-10-05 17:55:00');
INSERT INTO participation (duo_id, joueur_id, date_jour, reponse, date_reponse) VALUES
  (5, 106, CURDATE(), 'oui', CONCAT(CURDATE(), ' 08:00:00')),
  (5, 107, CURDATE(), 'oui', CONCAT(CURDATE(), ' 08:10:00'));

-- Un sixième duo pour C1 état 4 : Ana a choisi le défi de Noé (mission non révélée), Noé doit encore piocher (état 3).
-- Même limite que l'état 2 : ne vaut que le jour du rechargement (CURDATE()), avant 12 h. Défis 1 à 3 : les premiers de defis.sql.
INSERT INTO duo (id, date_creation, date_activation) VALUES (6, '2026-10-06 18:00:00', '2026-10-06 18:05:00');
INSERT INTO joueur (id, identifiant, pseudo, mdp_hash, duo_id, onboarding_vu, date_creation) VALUES
  (108, 'ana', 'Ana', '$2y$10$rjwoUdt8qkxCXqIp/CTRieI7ivw1Rz0.gNWneozgKyCpRLBmr/38W', 6, 1, '2026-10-06 17:50:00'),
  (109, 'noe', 'Noé', '$2y$10$rjwoUdt8qkxCXqIp/CTRieI7ivw1Rz0.gNWneozgKyCpRLBmr/38W', 6, 1, '2026-10-06 17:55:00');
INSERT INTO participation (duo_id, joueur_id, date_jour, reponse, date_reponse) VALUES
  (6, 108, CURDATE(), 'oui', CONCAT(CURDATE(), ' 08:00:00')),
  (6, 109, CURDATE(), 'oui', CONCAT(CURDATE(), ' 08:05:00'));
INSERT INTO tirage (id, duo_id, emetteur_id, destinataire_id, date_jour) VALUES (1, 6, 108, 109, CURDATE());
INSERT INTO tirage_proposition (tirage_id, defi_id) VALUES (1, 1), (1, 2), (1, 3);
INSERT INTO mission (duo_id, tirage_id, defi_id, emetteur_id, destinataire_id, date_attribution, date_limite, statut) VALUES
  (6, 1, 2, 108, 109, CONCAT(CURDATE(), ' 08:20:00'), CONCAT(CURDATE() + INTERVAL 1 DAY, ' 00:00:00'), 'attribue_non_revele');

-- Un septième duo pour C1 état 5 : Lina et Sam ont chacun choisi le défi de l'autre, missions non révélées.
-- Le premier chargement de l'un ou l'autre les révèle (rattrapage) : les deux lisent « un défi t'attend ». Même limite que l'état 2 (CURDATE()).
INSERT INTO duo (id, date_creation, date_activation) VALUES (7, '2026-10-07 18:00:00', '2026-10-07 18:05:00');
INSERT INTO joueur (id, identifiant, pseudo, mdp_hash, duo_id, onboarding_vu, date_creation) VALUES
  (110, 'lina', 'Lina', '$2y$10$rjwoUdt8qkxCXqIp/CTRieI7ivw1Rz0.gNWneozgKyCpRLBmr/38W', 7, 1, '2026-10-07 17:50:00'),
  (111, 'sam', 'Sam', '$2y$10$rjwoUdt8qkxCXqIp/CTRieI7ivw1Rz0.gNWneozgKyCpRLBmr/38W', 7, 1, '2026-10-07 17:55:00');
INSERT INTO participation (duo_id, joueur_id, date_jour, reponse, date_reponse) VALUES
  (7, 110, CURDATE(), 'oui', CONCAT(CURDATE(), ' 08:00:00')),
  (7, 111, CURDATE(), 'oui', CONCAT(CURDATE(), ' 08:05:00'));
INSERT INTO tirage (id, duo_id, emetteur_id, destinataire_id, date_jour) VALUES
  (2, 7, 110, 111, CURDATE()),
  (3, 7, 111, 110, CURDATE());
INSERT INTO tirage_proposition (tirage_id, defi_id) VALUES (2, 1), (2, 2), (2, 3), (3, 4), (3, 5), (3, 6);
INSERT INTO mission (duo_id, tirage_id, defi_id, emetteur_id, destinataire_id, date_attribution, date_limite, statut) VALUES
  (7, 2, 1, 110, 111, CONCAT(CURDATE(), ' 08:20:00'), CONCAT(CURDATE() + INTERVAL 1 DAY, ' 00:00:00'), 'attribue_non_revele'),
  (7, 3, 5, 111, 110, CONCAT(CURDATE(), ' 08:30:00'), CONCAT(CURDATE() + INTERVAL 1 DAY, ' 00:00:00'), 'attribue_non_revele');

-- Un huitième duo pour C1 « aucun jeu », variante « l'un de vous n'a pas choisi » : Jade a choisi le défi d'Owen, Owen n'a pas pioché.
-- La mission de Jade est déjà annulée, comme le rattrapage l'aurait fait à 12 h : les deux lisent la journée blanche à toute heure. Même limite que l'état 2 (CURDATE()).
-- Le duo d'Ana et Noé (état 4) passe aussi dans cette variante au premier chargement après 12 h.
INSERT INTO duo (id, date_creation, date_activation) VALUES (8, '2026-10-08 18:00:00', '2026-10-08 18:05:00');
INSERT INTO joueur (id, identifiant, pseudo, mdp_hash, duo_id, onboarding_vu, date_creation) VALUES
  (112, 'jade', 'Jade', '$2y$10$rjwoUdt8qkxCXqIp/CTRieI7ivw1Rz0.gNWneozgKyCpRLBmr/38W', 8, 1, '2026-10-08 17:50:00'),
  (113, 'owen', 'Owen', '$2y$10$rjwoUdt8qkxCXqIp/CTRieI7ivw1Rz0.gNWneozgKyCpRLBmr/38W', 8, 1, '2026-10-08 17:55:00');
INSERT INTO participation (duo_id, joueur_id, date_jour, reponse, date_reponse) VALUES
  (8, 112, CURDATE(), 'oui', CONCAT(CURDATE(), ' 08:00:00')),
  (8, 113, CURDATE(), 'oui', CONCAT(CURDATE(), ' 08:05:00'));
INSERT INTO tirage (id, duo_id, emetteur_id, destinataire_id, date_jour) VALUES (4, 8, 112, 113, CURDATE());
INSERT INTO tirage_proposition (tirage_id, defi_id) VALUES (4, 1), (4, 2), (4, 3);
INSERT INTO mission (duo_id, tirage_id, defi_id, emetteur_id, destinataire_id, date_attribution, date_limite, statut) VALUES
  (8, 4, 3, 112, 113, CONCAT(CURDATE(), ' 08:20:00'), CONCAT(CURDATE() + INTERVAL 1 DAY, ' 00:00:00'), 'annule');
