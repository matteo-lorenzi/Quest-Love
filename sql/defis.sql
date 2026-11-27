-- Bibliothèque de défis — C2 Tirage et C3 Défi reçu — Journée de jeu — lot 1 — B. Environ 50 défis en quatre catégories figées (cadrage § 4.6, § 8.4).
-- Chargé après sql/schema.sql par php sql/reinitialiser.php. Registre du cadrage § 4.6 : drôle, quotidien, faisable en une journée, sans dépense ni contenu intime.
-- Écrit du point de vue de celui qui reçoit le défi (cadrage § 8.4). Les défis 1 à 15 gardent leur numéro : le jeu de démonstration y renvoie.

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
  ('Bulletin météo', 'Présente à ton partenaire la météo de demain à la manière d''un présentateur télé très enthousiaste.', 4),
  ('Petit-déjeuner d''avance', 'Prépare ce soir le petit-déjeuner de demain, pour que ton partenaire n''ait plus qu''à s''asseoir.', 1),
  ('Message de midi', 'Envoie à ton partenaire, à l''heure du déjeuner, un message qui lui rappelle un détail de votre matinée.', 1),
  ('Liste des petits riens', 'Note cinq petites choses que ton partenaire fait pour toi sans que personne ne les remarque, et lis-lui la liste ce soir.', 1),
  ('Merci précis', 'Remercie ton partenaire pour une chose précise qu''il a faite cette semaine, en lui expliquant ce qu''elle a changé pour toi.', 1),
  ('Place réservée', 'Prépare à ton partenaire son coin préféré du canapé : coussin, plaid et télécommande à portée de main.', 1),
  ('Question inédite', 'Pose ce soir à ton partenaire une question que tu ne lui as jamais posée, et écoute la réponse jusqu''au bout.', 1),
  ('Écran éteint', 'Pendant le repas de ce soir, laisse ton téléphone dans une autre pièce et consacre toute ton attention à ton partenaire.', 1),
  ('Détail remarqué', 'Repère aujourd''hui un détail nouveau chez ton partenaire (une coiffure, un vêtement, une humeur) et dis-le-lui.', 1),
  ('Boisson surprise', 'Prépare la boisson préférée de ton partenaire et apporte-la-lui au moment où il s''y attend le moins.', 1),
  ('Mot imposé', 'Choisis ce matin un mot improbable avec ton partenaire : chacun doit le placer dans une conversation avant ce soir.', 2),
  ('Voyage imaginaire', 'Raconte à ton partenaire, en trois minutes, un voyage imaginaire que vous feriez ensemble, de la valise au retour.', 2),
  ('Deux vérités, un mensonge', 'Raconte à ton partenaire trois anecdotes de ton enfance, dont une inventée ; il doit trouver laquelle.', 2),
  ('Surnom du jour', 'Trouve un surnom ridicule à ton partenaire et appelle-le ainsi toute la journée, quoi qu''il arrive.', 2),
  ('Palmarès à deux', 'Établis avec ton partenaire le classement de vos trois meilleurs souvenirs communs, en vous mettant d''accord sur l''ordre.', 2),
  ('Jeu maison', 'Invente avec ton partenaire un jeu fait de ce que vous avez sous la main, et jouez-en une partie ce soir.', 2),
  ('Pari sans enjeu', 'Lance à ton partenaire un pari sur un petit événement de la journée ; le perdant fait la vaisselle ce soir.', 2),
  ('Fou rire garanti', 'Raconte à ton partenaire la situation la plus gênante que tu aies vécue, et laisse-le en rire autant qu''il veut.', 2),
  ('Marche du soir', 'Emmène ton partenaire faire une promenade de vingt minutes après le dîner, sans destination précise.', 3),
  ('Objet réparé', 'Répare ou recolle un objet de la maison qui attend depuis longtemps, puis montre-le à ton partenaire.', 3),
  ('Danse en cuisine', 'Mets une chanson entraînante et invite ton partenaire à danser avec toi dans la cuisine, ne serait-ce que trente secondes.', 3),
  ('Pique-nique au salon', 'Sers le dîner de ce soir en pique-nique sur le sol du salon, nappe comprise.', 3),
  ('Photo mystère', 'Prends en photo un détail de ton trajet du jour et fais deviner à ton partenaire où tu l''as pris.', 3),
  ('Vaisselle en chanson', 'Fais la vaisselle ce soir en chantant au moins une chanson en entier, que ton partenaire le veuille ou non.', 3),
  ('Lit d''hôtel', 'Fais le lit au carré, comme dans un grand hôtel, et laisse ton partenaire le découvrir.', 3),
  ('Planche à deux', 'Lance un concours de gainage à ton partenaire : tenez la planche côte à côte le plus longtemps possible.', 3),
  ('Chaud ou froid', 'Cache un objet que ton partenaire utilise tous les jours et guide-le jusqu''à lui en ne disant que « chaud » ou « froid ».', 3),
  ('Lettre pour dans un an', 'Écris à ton partenaire une courte lettre à n''ouvrir que dans un an, et confie-la-lui.', 4),
  ('Critique gastronomique', 'Commente le prochain repas devant ton partenaire comme un critique gastronomique très exigeant.', 4),
  ('Journée mimée', 'Mime ta journée à ton partenaire en une minute, sans un mot ; il doit deviner chaque moment.', 4),
  ('Doublage en direct', 'Coupe le son d''une scène de film ou de série et double les personnages en direct pour ton partenaire.', 4),
  ('Haïku domestique', 'Écris un haïku sur un objet banal de la maison et récite-le à ton partenaire avec le plus grand sérieux.', 4),
  ('Documentaire animalier', 'Commente pendant deux minutes ce que fait ton partenaire, à la manière d''un documentaire animalier.', 4),
  ('Conte du soir', 'Invente une histoire dont ton partenaire est le héros et raconte-la-lui ce soir.', 4),
  ('Remise de prix', 'Décerne à ton partenaire un prix imaginaire de ton invention et prononce le discours qui va avec.', 4),
  ('Accent du dîner', 'Parle avec l''accent de ton choix pendant tout le dîner, sans jamais t''en expliquer.', 4);
