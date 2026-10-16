-- Schéma de la base — aucun écran — lot 1 — commun. Minimal pour le noyau (#2) et A3 (#3), complété par ticket jusqu'à la validation (S8).
-- Conforme à 04-technique/modele-donnees.md § 2. Rechargé par php sql/reinitialiser.php.

SET NAMES utf8mb4;

DROP TABLE IF EXISTS tentative;
DROP TABLE IF EXISTS joueur;

CREATE TABLE joueur (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  -- L'interclassement unicode_ci rend l'unicité insensible aux majuscules et aux accents : « Lou » et « lou », « Léa » et « Lea » ne peuvent coexister.
  identifiant VARCHAR(30) NOT NULL,
  -- Prénom ou surnom affiché au partenaire, volontairement non unique.
  pseudo VARCHAR(30) NOT NULL,
  mdp_hash VARCHAR(255) NOT NULL,
  -- TODO(simplif): pas de clé étrangère vers duo tant que la table n'existe pas, à ajouter avec AppairageService.
  duo_id INT UNSIGNED NULL,
  pv TINYINT UNSIGNED NOT NULL DEFAULT 5,
  date_creation DATETIME NOT NULL,
  UNIQUE KEY uk_joueur_identifiant (identifiant),
  KEY ix_joueur_duo (duo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tentative (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cle VARCHAR(64) NOT NULL,
  type ENUM('connexion', 'code') NOT NULL,
  date DATETIME NOT NULL,
  KEY ix_tentative_cle_type_date (cle, type, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
