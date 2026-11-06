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
Journée de jeu sans mission ni perte de PV, parce qu'un joueur a répondu non ou n'a pas répondu avant 12 h.
_Éviter_ : jour off, pause

**Tirage** :
Les trois défis proposés à un émetteur pour une journée, parmi lesquels il en choisit un.
_Éviter_ : pioche, main

## Jeu

**Défi** :
Entrée de la bibliothèque : une action rédigée, réalisable en une journée, rattachée à une catégorie.

**Mission** :
Défi attribué à un destinataire pour une journée de jeu donnée. Terme interne : les joueurs ne voient que le mot « défi ».
_Éviter_ : défi du jour, quête

**Révélation** :
Moment où une mission devient visible pour son destinataire : dès que les deux joueurs ont choisi, ou à 12 h au plus tard.
_Éviter_ : notification, annonce

**Découverte** :
Première consultation, par son destinataire, d'une mission déjà révélée. Une mission peut expirer sans avoir été découverte.
_Éviter_ : lecture, ouverture

**Point de vie (PV)** :
Unité du capital de chaque joueur, 5 au départ, perdue quand une mission n'est pas réalisée à temps.
_Éviter_ : vie, cœur, score

**Gage** :
Action libre rédigée par le partenaire, que doit réaliser le joueur tombé à 0 PV (lot 2).
_Éviter_ : punition, sanction
