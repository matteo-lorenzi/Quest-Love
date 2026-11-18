# Quest & Love

Jeu quotidien à deux : chaque jour, chaque joueur attribue un défi à son partenaire. Ce glossaire fixe un mot par concept pour la documentation et le code. Le cadrage unifié fait foi sur les règles.

## Joueurs

**Joueur** :
Personne inscrite à l'application, avec ou sans duo. Se connecte avec son identifiant, apparaît à son partenaire sous son pseudo.
_Éviter_ : utilisateur, membre, compte (le compte désigne seulement les identifiants)

**Identifiant** :
Nom unique et privé avec lequel le joueur se connecte. Jamais affiché au partenaire.
_Éviter_ : login, nom d'utilisateur

**Pseudo** :
Prénom ou surnom libre sous lequel le joueur apparaît à son partenaire. Plusieurs joueurs peuvent porter le même.
_Éviter_ : nom affiché, surnom

**Duo** :
Deux joueurs appairés qui jouent ensemble. Un joueur appartient à au plus un duo.
_Éviter_ : couple (réservé à la cible et à la recherche utilisateur), équipe, binôme (réservé aux auteurs du projet)

**Émetteur** :
Joueur qui choisit le défi attribué à son partenaire pour la journée.
_Éviter_ : expéditeur, donneur

**Destinataire** :
Joueur qui reçoit la mission du jour et doit la réaliser.
_Éviter_ : receveur, cible

## Appairage

**Appairage** :
Formation d'un duo : un joueur sans duo saisit le code d'invitation d'un autre joueur sans duo.
_Éviter_ : jumelage, association

**Code d'invitation** :
Code de 8 caractères propre à un joueur sans duo, qu'il donne à son partenaire. À usage unique, valable 60 minutes, remplacé quand le joueur en demande un nouveau. Il disparaît quand le duo se forme.
_Éviter_ : jeton, lien d'invitation (le lien transporte le code, il n'est pas le code)

**Dissolution** :
Fin d'un duo décidée par l'un de ses deux joueurs, à tout moment. Les données de jeu du duo sont supprimées sans retour ; les deux joueurs redeviennent sans duo, à 5 PV, et peuvent former un nouveau duo. Le partenaire en est informé à sa prochaine connexion.
_Éviter_ : rupture, séparation, suppression du duo (« Quitter mon duo » est le libellé de l'écran)

**Salle d'attente** :
Écran B1 d'un joueur sans duo : il y voit son code d'invitation et peut saisir celui de son partenaire.
_Éviter_ : lobby, file d'attente

## Comptes

**Caractère** :
Unité de longueur des règles des comptes : un point de code Unicode, compté après normalisation NFC. Un « é » vaut un caractère, qu'il soit saisi d'un bloc ou en deux temps (lettre puis accent).
_Éviter_ : lettre, symbole

**Mot de passe courant** :
Mot de passe qui figure dans la liste des plus fréquents, issue de fuites publiques. Refusé à l'inscription même s'il respecte toutes les autres règles. La comparaison ignore majuscules et accents.
_Éviter_ : mot de passe faible, liste noire

## Rythme quotidien

**Journée de jeu** :
Période de jeu identifiée par une date, de 00 h à minuit, bornée par la bascule de 12 h.

**Journée blanche** :
Journée de jeu sans mission ni perte de PV, pour les deux joueurs : l'un a répondu non ou n'a pas répondu avant 12 h, ou les deux ont dit oui mais l'un n'a pas choisi avant 12 h.
_Éviter_ : jour off, pause

**Participation** :
Réponse définitive d'un joueur à « On joue aujourd'hui ? », donnée avant 12 h. Une seule par joueur et par journée de jeu ; la journée n'est jouée que si les deux répondent oui.
_Éviter_ : vote, inscription (à la journée)

**Tirage** :
Ligne qui consigne, pour une journée, l'émetteur, le destinataire et les trois propositions qui lui sont faites ; il en choisit une. « Tirer » est le verbe : l'application tire trois défis au hasard et les inscrit au tirage, une seule fois par émetteur et par journée.
_Éviter_ : pioche, main

**Proposition** :
Un des trois défis offerts par un tirage. Liaison entre un tirage et un défi : un défi peut figurer dans de nombreux tirages, un tirage en porte exactement trois.
_Éviter_ : option, carte

## Jeu

**Défi** :
Entrée de la bibliothèque : une action rédigée, réalisable en une journée, rattachée à une catégorie. Modèle partagé et réutilisable : la mission en est l'instance.

**Catégorie** :
Regroupement de la bibliothèque, 4 à 5 au total, déduit des défis écrits. Un défi appartient à une seule catégorie.
_Éviter_ : type, thème

**Mission** :
Défi attribué à un destinataire pour une journée de jeu donnée. Terme interne : les joueurs ne voient que le mot « défi ».
_Éviter_ : défi du jour, quête

**Attribution** :
Création de la mission : l'émetteur retient, avant 12 h, l'une des trois propositions de son tirage. L'application n'attribue jamais de défi à sa place.
_Éviter_ : affectation, envoi

**Révélation** :
Moment où une mission devient visible pour son destinataire : dès que les deux joueurs ont choisi. Sans les deux choix à 12 h, il n'y a pas de révélation : la mission est annulée.
_Éviter_ : notification, annonce

**Découverte** :
Première consultation, par son destinataire, d'une mission déjà révélée. Une mission peut expirer sans avoir été découverte.
_Éviter_ : lecture, ouverture

**Validation** :
Déclaration du destinataire, après confirmation, qu'il a réalisé sa mission. Fait passer la mission de en cours à validée.
_Éviter_ : réussite, preuve

**Expiration** :
Passage d'une mission en cours à expirée quand minuit est passé sans validation, constaté au rattrapage ; le destinataire perd 1 PV. Une mission peut expirer sans avoir été découverte.
_Éviter_ : échec, péremption

**Annulation** :
Passage à 12 h d'une mission non révélée à annulée, parce que l'autre joueur n'a pas choisi : la journée est blanche. Sans effet sur les PV ; le défi n'est pas compté comme reçu et peut être reproposé dès le lendemain.
_Éviter_ : abandon, suppression

**Point de vie (PV)** :
Unité du capital de chaque joueur, 5 au départ, perdue quand une mission n'est pas réalisée à temps.
_Éviter_ : vie, cœur, score

**Gage** :
Action libre rédigée par le partenaire, que doit réaliser le joueur tombé à 0 PV (lot 2).
_Éviter_ : punition, sanction

## Technique

**Tentative** :
Ligne qui compte un essai de connexion, de code ou d'inscription, sous une clé hachée, pour limiter les essais. Purgée au bout de 30 minutes ; jamais liée à un joueur.
_Éviter_ : essai (le mot désigne l'acte, pas la ligne), échec, log
