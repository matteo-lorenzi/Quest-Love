-- Jeu PROVISOIRE de défis — C2 Tirage et C3 Défi reçu — Journée de jeu — lot 1 — B. Remplacé par la bibliothèque d'environ 50 défis (cadrage § 8.4).
-- Chargé après sql/schema.sql par php sql/reinitialiser.php. Registre du cadrage § 4.6 : drôle, quotidien, faisable en une journée, sans dépense ni contenu intime.
-- Écrit du point de vue de celui qui reçoit le défi (cadrage § 8.4). Catégories prises parmi les familles probables du § 8.4, à confirmer.

SET NAMES utf8mb4;

-- Numéros fixés : les défis ci-dessous y renvoient sans requête intermédiaire.
INSERT INTO categorie (id, libelle) VALUES
  (1, 'Attention'),
  (2, 'Complicité'),
  (3, 'Action'),
  (4, 'Expression');

INSERT INTO defi (titre, description, categorie_id) VALUES
  ('Mot caché', 'Glisse un petit mot doux à un endroit où ton partenaire le trouvera aujourd''hui, sans le prévenir.', 1),
  ('Trois compliments', 'Fais trois compliments sincères à ton partenaire avant ce soir, sur trois sujets différents.', 1),
  ('Corvée volée', 'Fais à sa place une tâche que ton partenaire repousse depuis un moment, sans rien dire.', 1),
  ('Écoute active', 'Demande à ton partenaire de te raconter le meilleur moment de sa journée et ne coupe pas la parole une seule fois.', 1),
  ('Souvenir commun', 'Retrouve une photo de vous deux d''il y a longtemps et raconte à ton partenaire ce dont tu te souviens de ce jour-là.', 2),
  ('Code secret', 'Invente un geste secret avec ton partenaire et place-le au moins deux fois dans la journée.', 2),
  ('Quiz express', 'Pose à ton partenaire cinq questions sur toi ; il doit trouver au moins trois bonnes réponses.', 2),
  ('Chanson du jour', 'Choisis une chanson qui vous ressemble et fais-la écouter à ton partenaire en lui expliquant pourquoi.', 2),
  ('Pas de côté', 'Rentre chez toi par un chemin que tu n''as jamais pris et raconte à ton partenaire ce que tu y as vu.', 3),
  ('Dix minutes de rangement', 'Range pendant dix minutes chrono un coin de la maison choisi par ton partenaire.', 3),
  ('Pause étirement', 'Entraîne ton partenaire dans cinq minutes d''étirements, en musique, au moment de ton choix.', 3),
  ('Chef d''un soir', 'Prépare le repas ce soir avec uniquement ce qu''il y a déjà dans les placards.', 3),
  ('Poème minute', 'Écris un poème de quatre vers sur une habitude de ton partenaire et lis-le-lui à voix haute.', 4),
  ('Dessin à l''aveugle', 'Dessine le portrait de ton partenaire sans regarder ta feuille et offre-lui le résultat.', 4),
  ('Bulletin météo', 'Présente à ton partenaire la météo de demain à la manière d''un présentateur télé très enthousiaste.', 4);
