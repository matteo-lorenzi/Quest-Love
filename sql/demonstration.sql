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
-- Numéros fixés (3 et 4, ceux qu'ils prenaient déjà) : leur historique, en fin de fichier, y renvoie. 4 PV chacun : un défi expiré chacun dans cet historique.
INSERT INTO joueur (id, identifiant, pseudo, mdp_hash, duo_id, pv, onboarding_vu, date_creation) VALUES
  (3, 'lea', 'Léa', '$2y$10$rjwoUdt8qkxCXqIp/CTRieI7ivw1Rz0.gNWneozgKyCpRLBmr/38W', 1, 4, 1, '2026-10-02 17:50:00'),
  (4, 'tom', 'Tom', '$2y$10$rjwoUdt8qkxCXqIp/CTRieI7ivw1Rz0.gNWneozgKyCpRLBmr/38W', 1, 4, 1, '2026-10-02 17:55:00');
-- Léa et Tom n'ont pas répondu aujourd'hui : C1 état 1 (« On joue aujourd'hui ? »). Leur historique des jours précédents est en fin de fichier.

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
-- La mission de Jade reste non révélée : au premier chargement après 12 h (ou avec HORLOGE_DECALAGE, § 3.8), le rattrapage l'annule et les deux lisent la journée blanche.
-- Avant 12 h, Jade lit l'état 4 et Owen l'état 3. Le duo d'Ana et Noé passe aussi dans cette variante ; celui-ci la montre sans toucher à l'état 4. Même limite que l'état 2 (CURDATE()).
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
  (8, 4, 3, 112, 113, CONCAT(CURDATE(), ' 08:20:00'), CONCAT(CURDATE() + INTERVAL 1 DAY, ' 00:00:00'), 'attribue_non_revele');

-- Un neuvième duo pour C3 et C1 état 6 : Iris et Yann ont chacun reçu leur défi (missions révélées). Yann a déjà déclaré le sien réalisé : C3 montre « réalisé », C1 la variante de l'état 6.
-- Iris a déjà ouvert son défi, encore en cours : C1 état 6, où elle lit que Yann a relevé le défi qu'elle lui a envoyé ; elle peut valider le sien sur C3. Même limite que l'état 2 (CURDATE()).
INSERT INTO duo (id, date_creation, date_activation) VALUES (9, '2026-10-09 18:00:00', '2026-10-09 18:05:00');
INSERT INTO joueur (id, identifiant, pseudo, mdp_hash, duo_id, onboarding_vu, date_creation) VALUES
  (114, 'iris', 'Iris', '$2y$10$rjwoUdt8qkxCXqIp/CTRieI7ivw1Rz0.gNWneozgKyCpRLBmr/38W', 9, 1, '2026-10-09 17:50:00'),
  (115, 'yann', 'Yann', '$2y$10$rjwoUdt8qkxCXqIp/CTRieI7ivw1Rz0.gNWneozgKyCpRLBmr/38W', 9, 1, '2026-10-09 17:55:00');
INSERT INTO participation (duo_id, joueur_id, date_jour, reponse, date_reponse) VALUES
  (9, 114, CURDATE(), 'oui', CONCAT(CURDATE(), ' 07:00:00')),
  (9, 115, CURDATE(), 'oui', CONCAT(CURDATE(), ' 07:05:00'));
INSERT INTO tirage (id, duo_id, emetteur_id, destinataire_id, date_jour) VALUES
  (5, 9, 114, 115, CURDATE()),
  (6, 9, 115, 114, CURDATE());
INSERT INTO tirage_proposition (tirage_id, defi_id) VALUES (5, 1), (5, 2), (5, 3), (6, 4), (6, 5), (6, 6);
INSERT INTO mission (duo_id, tirage_id, defi_id, emetteur_id, destinataire_id, date_attribution, date_revelation, date_decouverte, date_limite, statut, date_validation) VALUES
  (9, 5, 2, 114, 115, CONCAT(CURDATE(), ' 07:20:00'), CONCAT(CURDATE(), ' 07:30:00'), CONCAT(CURDATE(), ' 07:35:00'), CONCAT(CURDATE() + INTERVAL 1 DAY, ' 00:00:00'), 'valide', CONCAT(CURDATE(), ' 07:50:00')),
  (9, 6, 4, 115, 114, CONCAT(CURDATE(), ' 07:30:00'), CONCAT(CURDATE(), ' 07:30:00'), CONCAT(CURDATE(), ' 07:40:00'), CONCAT(CURDATE() + INTERVAL 1 DAY, ' 00:00:00'), 'en_cours', NULL);

-- Un dixième duo pour D1, variante « jamais découvert », et D5 : hier, Rose et Théo ont chacun reçu leur défi. Théo a validé le sien ; Rose n'a jamais ouvert le sien.
-- Sa mission est encore en cours, minuit passé, et Rose n'a plus qu'1 PV : son premier chargement la fait expirer (rattrapage), elle tombe à 0 et remonte à 5 ; elle lit D1 puis D5. Théo ne voit rien.
-- La veille est celle du rechargement (CURDATE() - 1) : D1 se montre le jour même, à toute heure, tant que Rose ne s'est pas connectée.
INSERT INTO duo (id, date_creation, date_activation) VALUES (10, '2026-10-10 18:00:00', '2026-10-10 18:05:00');
INSERT INTO joueur (id, identifiant, pseudo, mdp_hash, duo_id, pv, onboarding_vu, date_creation) VALUES
  (116, 'rose', 'Rose', '$2y$10$rjwoUdt8qkxCXqIp/CTRieI7ivw1Rz0.gNWneozgKyCpRLBmr/38W', 10, 1, 1, '2026-10-10 17:50:00'),
  (117, 'theo', 'Théo', '$2y$10$rjwoUdt8qkxCXqIp/CTRieI7ivw1Rz0.gNWneozgKyCpRLBmr/38W', 10, 5, 1, '2026-10-10 17:55:00');
INSERT INTO participation (duo_id, joueur_id, date_jour, reponse, date_reponse) VALUES
  (10, 116, CURDATE() - INTERVAL 1 DAY, 'oui', CONCAT(CURDATE() - INTERVAL 1 DAY, ' 08:00:00')),
  (10, 117, CURDATE() - INTERVAL 1 DAY, 'oui', CONCAT(CURDATE() - INTERVAL 1 DAY, ' 08:05:00'));
INSERT INTO tirage (id, duo_id, emetteur_id, destinataire_id, date_jour) VALUES
  (7, 10, 116, 117, CURDATE() - INTERVAL 1 DAY),
  (8, 10, 117, 116, CURDATE() - INTERVAL 1 DAY);
INSERT INTO tirage_proposition (tirage_id, defi_id) VALUES (7, 1), (7, 2), (7, 3), (8, 4), (8, 5), (8, 6);
INSERT INTO mission (duo_id, tirage_id, defi_id, emetteur_id, destinataire_id, date_attribution, date_revelation, date_decouverte, date_limite, statut, date_validation) VALUES
  (10, 7, 1, 116, 117, CONCAT(CURDATE() - INTERVAL 1 DAY, ' 08:20:00'), CONCAT(CURDATE() - INTERVAL 1 DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 1 DAY, ' 09:00:00'), CONCAT(CURDATE(), ' 00:00:00'), 'valide', CONCAT(CURDATE() - INTERVAL 1 DAY, ' 18:00:00')),
  (10, 8, 6, 117, 116, CONCAT(CURDATE() - INTERVAL 1 DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 1 DAY, ' 08:30:00'), NULL, CONCAT(CURDATE(), ' 00:00:00'), 'en_cours', NULL);

-- Un onzième duo pour B2, variante d'attente, et F1 « duo sans accès au jeu » : Chloé a fini l'onboarding, Enzo non. Duo formé mais pas encore activé.
-- Chloé lit l'attente de B2, et son profil F1 la variante sans accès au jeu ; Enzo voit l'onboarding. Dès qu'Enzo le termine, le duo s'active et les deux passent à l'accueil.
INSERT INTO duo (id, date_creation, date_activation) VALUES (11, '2026-10-11 18:00:00', NULL);
INSERT INTO joueur (id, identifiant, pseudo, mdp_hash, duo_id, onboarding_vu, date_creation) VALUES
  (118, 'chloe', 'Chloé', '$2y$10$rjwoUdt8qkxCXqIp/CTRieI7ivw1Rz0.gNWneozgKyCpRLBmr/38W', 11, 1, '2026-10-11 17:50:00'),
  (119, 'enzo', 'Enzo', '$2y$10$rjwoUdt8qkxCXqIp/CTRieI7ivw1Rz0.gNWneozgKyCpRLBmr/38W', 11, 0, '2026-10-11 17:55:00');

-- Un joueur sans duo pour D3 à la connexion : son ancien partenaire a dissous leur duo pendant son absence. Il lit D3 une seule fois, puis B1.
INSERT INTO joueur (id, identifiant, pseudo, mdp_hash, dissolution_a_signaler, date_creation) VALUES
  (120, 'marc', 'Marc', '$2y$10$rjwoUdt8qkxCXqIp/CTRieI7ivw1Rz0.gNWneozgKyCpRLBmr/38W', 1, '2026-10-12 17:50:00');

-- Trois jours d'historique pour Léa et Tom (duo 1), avant-hier compris : missions validées et expirées, déjà passées par le rattrapage (statuts terminaux).
-- Rien aujourd'hui : C1 reste à l'état 1. Chacun a perdu 1 PV par défi expiré (pv à 4 plus haut, cadrage § 4.4).
-- Les défis reçus par Tom (7, 9, 11) et par Léa (8, 10, 12) ne reviennent plus à son tirage (cadrage § 4.6). Défis 1 à 15 seulement.
-- Les propositions non retenues d'hier et d'avant-hier sont écartées trois jours : il reste à chacun huit défis disponibles sur quinze.
INSERT INTO participation (duo_id, joueur_id, date_jour, reponse, date_reponse) VALUES
  (1, 3, CURDATE() - INTERVAL 3 DAY, 'oui', CONCAT(CURDATE() - INTERVAL 3 DAY, ' 08:00:00')),
  (1, 4, CURDATE() - INTERVAL 3 DAY, 'oui', CONCAT(CURDATE() - INTERVAL 3 DAY, ' 08:05:00')),
  (1, 3, CURDATE() - INTERVAL 2 DAY, 'oui', CONCAT(CURDATE() - INTERVAL 2 DAY, ' 08:00:00')),
  (1, 4, CURDATE() - INTERVAL 2 DAY, 'oui', CONCAT(CURDATE() - INTERVAL 2 DAY, ' 08:05:00')),
  (1, 3, CURDATE() - INTERVAL 1 DAY, 'oui', CONCAT(CURDATE() - INTERVAL 1 DAY, ' 08:00:00')),
  (1, 4, CURDATE() - INTERVAL 1 DAY, 'oui', CONCAT(CURDATE() - INTERVAL 1 DAY, ' 08:05:00'));
-- Tirages impairs : Léa émet pour Tom ; pairs : Tom émet pour Léa.
INSERT INTO tirage (id, duo_id, emetteur_id, destinataire_id, date_jour) VALUES
  (9, 1, 3, 4, CURDATE() - INTERVAL 3 DAY),
  (10, 1, 4, 3, CURDATE() - INTERVAL 3 DAY),
  (11, 1, 3, 4, CURDATE() - INTERVAL 2 DAY),
  (12, 1, 4, 3, CURDATE() - INTERVAL 2 DAY),
  (13, 1, 3, 4, CURDATE() - INTERVAL 1 DAY),
  (14, 1, 4, 3, CURDATE() - INTERVAL 1 DAY);
INSERT INTO tirage_proposition (tirage_id, defi_id) VALUES
  (9, 7), (9, 1), (9, 2),
  (10, 8), (10, 3), (10, 4),
  (11, 9), (11, 5), (11, 6),
  (12, 10), (12, 13), (12, 14),
  (13, 11), (13, 13), (13, 14),
  (14, 12), (14, 1), (14, 15);
-- Avant-hier : les deux validés. Hier et la veille, un défi expiré chacun, ouvert mais pas réalisé (D1 déjà montré, variante « jamais découvert » : duo de Rose).
INSERT INTO mission (duo_id, tirage_id, defi_id, emetteur_id, destinataire_id, date_attribution, date_revelation, date_decouverte, date_limite, statut, date_validation) VALUES
  (1, 9, 7, 3, 4, CONCAT(CURDATE() - INTERVAL 3 DAY, ' 08:20:00'), CONCAT(CURDATE() - INTERVAL 3 DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 3 DAY, ' 09:00:00'), CONCAT(CURDATE() - INTERVAL 2 DAY, ' 00:00:00'), 'valide', CONCAT(CURDATE() - INTERVAL 3 DAY, ' 19:00:00')),
  (1, 10, 8, 4, 3, CONCAT(CURDATE() - INTERVAL 3 DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 3 DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 3 DAY, ' 09:10:00'), CONCAT(CURDATE() - INTERVAL 2 DAY, ' 00:00:00'), 'valide', CONCAT(CURDATE() - INTERVAL 3 DAY, ' 20:00:00')),
  (1, 11, 9, 3, 4, CONCAT(CURDATE() - INTERVAL 2 DAY, ' 08:20:00'), CONCAT(CURDATE() - INTERVAL 2 DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 2 DAY, ' 09:00:00'), CONCAT(CURDATE() - INTERVAL 1 DAY, ' 00:00:00'), 'valide', CONCAT(CURDATE() - INTERVAL 2 DAY, ' 18:30:00')),
  (1, 12, 10, 4, 3, CONCAT(CURDATE() - INTERVAL 2 DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 2 DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 2 DAY, ' 09:10:00'), CONCAT(CURDATE() - INTERVAL 1 DAY, ' 00:00:00'), 'expire', NULL),
  (1, 13, 11, 3, 4, CONCAT(CURDATE() - INTERVAL 1 DAY, ' 08:20:00'), CONCAT(CURDATE() - INTERVAL 1 DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 1 DAY, ' 09:00:00'), CONCAT(CURDATE(), ' 00:00:00'), 'expire', NULL),
  (1, 14, 12, 4, 3, CONCAT(CURDATE() - INTERVAL 1 DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 1 DAY, ' 08:30:00'), CONCAT(CURDATE() - INTERVAL 1 DAY, ' 09:10:00'), CONCAT(CURDATE(), ' 00:00:00'), 'valide', CONCAT(CURDATE() - INTERVAL 1 DAY, ' 21:00:00'));
