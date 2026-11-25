# Pièges des contrôleurs — à lire avant de modifier app/controleurs/

Rappels courts. La règle fait foi dans `04-technique/doc-technique.md` (§ 2.2, 2.3, 2.4) et `docs/adr/0001-controleur-frontal-mvc.md` ; `bash scripts/verifier.sh` attrape les cas marqués (V).

## Rôle

- Un contrôleur lit la requête, appelle un service, puis redirige ou rend sa vue. Aucune règle du jeu, aucun SQL : si l'un des deux apparaît, il appartient à un service ou à un dépôt (`app/modeles/GOTCHAS.md`).
- Un écran, un contrôleur, une vue. Un nouvel écran = une ligne dans la table du routeur (§ 2.3) avec son code d'inventaire et son niveau d'accès.
- Le routeur a déjà fait le rattrapage, le contrôle d'accès et le jeton CSRF avant d'appeler le contrôleur : ne pas les refaire (§ 2.2).

## GET et POST

- `POST` : se termine par un en-tête `Location` puis `exit`, refus compris, et ne rend jamais de vue. (V)
- Un refus ne montre rien lui-même : redirection vers l'écran dont le `GET` explique la situation. Le message passe par la session et se lit une seule fois (modèle : `ConnexionControleur`).
- `GET` : lire pour afficher, jamais écrire. Lire l'état de l'accueil n'appelle pas `MissionService::decouvrir`, qui pose une date.
- Une donnée reçue d'un formulaire ne désigne pas ce qu'elle a le droit de modifier : le service revérifie que l'objet appartient au joueur connecté.

## Construction

- Le constructeur reçoit `PDO` et `DateTimeImmutable $maintenant` du routeur. Il crée lui-même ses dépôts et services avec `new` : pas de conteneur, pas de classe mère (ADR-0001).
- Jamais de lecture de l'horloge : seul `index.php` la lit. (V)
- Les adresses d'écran (`ecran=…`) se construisent ici, dans le routeur ou dans les vues, jamais dans le modèle.

## Nommage et événements

- Français sans accent, suffixe `Controleur`, vocabulaire de `GLOSSARY.md`.
- Un nouvel événement = un cas de `Evenement` + une branche du `match` d'`EvenementControleur::afficher`.
- Les textes d'interface ne s'improvisent pas : un texte d'attente s'écrit `[TEXTE À RÉDIGER : …]` (§ 4.1).
