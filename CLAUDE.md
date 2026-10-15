@AGENTS.md

## Contraintes académiques non négociables

Validées avec l'enseignant référent ; aucune autre règle n'est imposée.

**Couches et patterns** (ADR-0001, doc technique § 2.1 et § 2.4)
- Contrôleur frontal unique : `app/public/index.php`.
- Contrôleurs fins : lire la requête, appeler un service, rediriger ou rendre la vue.
- Modèle en trois rôles : entités en lecture seule, dépôts (seul endroit du SQL, requêtes préparées), services (règles du jeu et transactions).
- Un écran, un contrôleur, une vue.
- Ni framework, ni ORM, ni moteur de gabarits, ni bibliothèque, ni gestionnaire de dépendances.

**Commentaires** (doc technique § 2.4)
- Chaque fichier commence par une ligne : rôle, code d'écran, lot, périmètre.
- On commente le pourquoi d'une décision non évidente, jamais le quoi.
- `public/index.php`, `noyau/Routeur.php` et l'écran de référence A3 (`ConnexionControleur`, sa vue, `CompteService`) sont annotés étape par étape, avec les numéros du § 2.2.

Ponytail ne supprime, ne fusionne ni ne contourne jamais ces couches ni ces commentaires, même pour raccourcir un diff.

## Ponytail

- Niveau `full` uniquement (fixé dans `.claude/settings.json`). Le mode `ultra` est interdit sur ce projet.
- Un raccourci assumé se marque `TODO(simplif): <la limite>, <quand l'améliorer>`, jamais `shortcut:`. Registre : `/ponytail-debt TODO(simplif)`.

## graphify

- Skill : `.claude/skills/graphify/SKILL.md`, déclenché par `/graphify`.
- Le graphe se construit uniquement par `graphify extract . --code-only`. Jamais `/graphify .` sans `--code-only` : son extraction sémantique envoie la doc à un LLM.
- Si `graphify-out/graph.json` existe, lancer `graphify query "<question>"` avant de fouiller le code.
- Après une modification du code, `graphify update .` (analyse locale, aucun appel à un LLM). `graphify-out/` n'est jamais versionné.
