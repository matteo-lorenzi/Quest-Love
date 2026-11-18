-- Schéma de la base — aucun écran — lot 1 — commun. Comptes et duo (#2, #3, #9), puis tables de la journée de jeu (#65) ; gage et contestation au lot 2.
-- Conforme à 04-technique/modele-donnees.md § 2. Rechargé par php sql/reinitialiser.php.

SET NAMES utf8mb4;

-- Ordre : une table est supprimée avant celles qu'elle référence et créée après elles.
DROP TABLE IF EXISTS mission;
DROP TABLE IF EXISTS tirage_proposition;
DROP TABLE IF EXISTS tirage;
DROP TABLE IF EXISTS participation;
DROP TABLE IF EXISTS defi;
DROP TABLE IF EXISTS categorie;
DROP TABLE IF EXISTS tentative;
DROP TABLE IF EXISTS joueur;
DROP TABLE IF EXISTS duo;

-- Une ligne n'existe qu'une fois le duo formé : elle porte toujours deux joueurs (ADR-0002).
CREATE TABLE duo (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  date_creation DATETIME NOT NULL,
  -- Nulle à la formation ; posée quand les deux joueurs ont fini l'onboarding (cadrage § 4.2 : instant de référence de « duo formé après 12 h »).
  date_activation DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE joueur (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  -- L'interclassement unicode_ci rend l'unicité insensible aux majuscules et aux accents : « Lou » et « lou », « Léa » et « Lea » ne peuvent coexister.
  identifiant VARCHAR(30) NOT NULL,
  -- Prénom ou surnom affiché au partenaire, volontairement non unique.
  pseudo VARCHAR(30) NOT NULL,
  mdp_hash VARCHAR(255) NOT NULL,
  -- Code d'invitation (ADR-0002) : ASCII sensible à la casse, nul sans code et une fois le duo formé. NULL multiples admis par la clé unique.
  code_invitation CHAR(8) CHARACTER SET ascii COLLATE ascii_bin NULL,
  date_expiration_code DATETIME NULL,
  -- Nul = sans duo ; la suppression du duo (dissolution) remet le joueur à nul.
  duo_id INT UNSIGNED NULL,
  pv TINYINT UNSIGNED NOT NULL DEFAULT 5,
  -- Onboarding (B2) terminé ou passé avec le duo actuel ; remis à 0 pour les deux joueurs à chaque formation de duo.
  onboarding_vu TINYINT(1) NOT NULL DEFAULT 0,
  -- Posé à 1 sur le partenaire seul quand l'autre dissout le duo ; D3 le lit puis le remet à 0.
  dissolution_a_signaler TINYINT(1) NOT NULL DEFAULT 0,
  date_creation DATETIME NOT NULL,
  UNIQUE KEY uk_joueur_identifiant (identifiant),
  UNIQUE KEY uk_joueur_code (code_invitation),
  KEY ix_joueur_duo (duo_id),
  CONSTRAINT fk_joueur_duo FOREIGN KEY (duo_id) REFERENCES duo (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tentative (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cle VARCHAR(64) NOT NULL,
  type ENUM('connexion', 'code', 'inscription') NOT NULL,
  date DATETIME NOT NULL,
  KEY ix_tentative_cle_type_date (cle, type, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tables de jeu (modèle de données § 2). Chacune porte duo_id en cascade : la dissolution reste un seul DELETE FROM duo (§ 6).
-- Les clés vers joueur restent en RESTRICT : la dissolution ne supprime aucun joueur, elles ne la bloquent donc pas.

CREATE TABLE participation (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  duo_id INT UNSIGNED NOT NULL,
  joueur_id INT UNSIGNED NOT NULL,
  -- Journée de jeu en Europe/Paris, calculée par l'application (doc technique § 3.1).
  date_jour DATE NOT NULL,
  reponse ENUM('oui', 'non') NOT NULL,
  date_reponse DATETIME NOT NULL,
  -- Une réponse par joueur et par journée : rend la réponse définitive et le double clic inoffensif.
  UNIQUE KEY uk_participation_joueur_jour (joueur_id, date_jour),
  CONSTRAINT fk_participation_duo FOREIGN KEY (duo_id) REFERENCES duo (id) ON DELETE CASCADE,
  CONSTRAINT fk_participation_joueur FOREIGN KEY (joueur_id) REFERENCES joueur (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données de référence chargées par sql/defis.sql ; l'application ne les écrit ni ne les supprime.
CREATE TABLE categorie (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  libelle VARCHAR(40) NOT NULL,
  UNIQUE KEY uk_categorie_libelle (libelle)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE defi (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  titre VARCHAR(120) NOT NULL,
  description TEXT NOT NULL,
  categorie_id INT UNSIGNED NOT NULL,
  CONSTRAINT fk_defi_categorie FOREIGN KEY (categorie_id) REFERENCES categorie (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tirage (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  duo_id INT UNSIGNED NOT NULL,
  emetteur_id INT UNSIGNED NOT NULL,
  -- TODO(simplif): copie de l'autre joueur du duo, divergence possible avec la source, à revoir si un bug apparaît.
  destinataire_id INT UNSIGNED NOT NULL,
  date_jour DATE NOT NULL,
  -- Un tirage par émetteur et par journée : un rechargement ne redonne pas d'autres propositions. Sert aussi d'index sur emetteur_id (§ 5).
  UNIQUE KEY uk_tirage_emetteur_jour (emetteur_id, date_jour),
  CONSTRAINT fk_tirage_duo FOREIGN KEY (duo_id) REFERENCES duo (id) ON DELETE CASCADE,
  CONSTRAINT fk_tirage_emetteur FOREIGN KEY (emetteur_id) REFERENCES joueur (id),
  CONSTRAINT fk_tirage_destinataire FOREIGN KEY (destinataire_id) REFERENCES joueur (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table de liaison (ADR-0003). « Exactement trois » est tenu par l'application ; la base garantit qu'elles sont distinctes.
CREATE TABLE tirage_proposition (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tirage_id INT UNSIGNED NOT NULL,
  defi_id INT UNSIGNED NOT NULL,
  -- Cible de la clé composite de mission.
  UNIQUE KEY uk_tirage_proposition (tirage_id, defi_id),
  CONSTRAINT fk_tirage_proposition_tirage FOREIGN KEY (tirage_id) REFERENCES tirage (id) ON DELETE CASCADE,
  CONSTRAINT fk_tirage_proposition_defi FOREIGN KEY (defi_id) REFERENCES defi (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mission (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  -- TODO(simplif): duo_id, emetteur_id et destinataire_id copient le tirage (cascade et index sans jointure), divergence possible, à revoir si un bug apparaît.
  duo_id INT UNSIGNED NOT NULL,
  tirage_id INT UNSIGNED NOT NULL,
  defi_id INT UNSIGNED NOT NULL,
  emetteur_id INT UNSIGNED NOT NULL,
  destinataire_id INT UNSIGNED NOT NULL,
  date_attribution DATETIME NOT NULL,
  date_revelation DATETIME NULL,
  -- Nulle à l'expiration : variante « jamais découvert » de D1.
  date_decouverte DATETIME NULL,
  -- TODO(simplif): minuit du jour d'attribution, copie calculée de date_attribution, à revoir si un bug apparaît.
  date_limite DATETIME NOT NULL,
  -- Lot 1 seulement ; conteste et acquis arrivent au lot 2. Valeurs de StatutMission.
  statut ENUM('attribue_non_revele', 'en_cours', 'valide', 'expire', 'annule') NOT NULL,
  date_validation DATETIME NULL,
  -- Une mission par tirage : un second choix est refusé.
  UNIQUE KEY uk_mission_tirage (tirage_id),
  KEY ix_mission_rattrapage (destinataire_id, statut, date_limite),
  KEY ix_mission_accueil (emetteur_id, date_attribution),
  CONSTRAINT fk_mission_duo FOREIGN KEY (duo_id) REFERENCES duo (id) ON DELETE CASCADE,
  -- Clé composite : le défi retenu est forcément l'une des propositions de son tirage (ADR-0003).
  CONSTRAINT fk_mission_proposition FOREIGN KEY (tirage_id, defi_id) REFERENCES tirage_proposition (tirage_id, defi_id) ON DELETE CASCADE,
  CONSTRAINT fk_mission_emetteur FOREIGN KEY (emetteur_id) REFERENCES joueur (id),
  CONSTRAINT fk_mission_destinataire FOREIGN KEY (destinataire_id) REFERENCES joueur (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
