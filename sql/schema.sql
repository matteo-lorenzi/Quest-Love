-- Schéma de la base — aucun écran — lot 1 — commun. Minimal pour le noyau (#2), A3 (#3) et B1 (#9), complété par ticket jusqu'à la validation (S8).
-- Conforme à 04-technique/modele-donnees.md § 2. Rechargé par php sql/reinitialiser.php.

SET NAMES utf8mb4;

-- Ordre : joueur référence duo, donc il est supprimé avant lui et créé après.
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
