@AGENTS.md

## Contraintes académiques non négociables

Les couches MVC (ADR-0001) et les règles de commentaires (doc technique § 2.4, y compris les fichiers annotés étape par étape) sont validées avec l'enseignant référent ; aucune autre règle n'est imposée. Ponytail ne supprime, ne fusionne ni ne contourne jamais ces couches ni ces commentaires, même pour raccourcir un diff.

## Ponytail

- Niveau `full` uniquement (fixé dans `.claude/settings.json`). Le mode `ultra` est interdit sur ce projet.
- Un raccourci assumé se marque `TODO(simplif): <la limite>, <quand l'améliorer>`, jamais `shortcut:`. Registre : `/ponytail-debt TODO(simplif)`.

## graphify

- Skill : `.claude/skills/graphify/SKILL.md`, déclenché par `/graphify`.
- Le graphe se construit uniquement par `graphify extract . --code-only`. Jamais `/graphify .` sans `--code-only` : son extraction sémantique envoie la doc à un LLM.
- Si `graphify-out/graph.json` existe, lancer `graphify query "<question>"` avant de fouiller le code.
- Après une modification du code, `graphify update .` (analyse locale, aucun appel à un LLM). `graphify-out/` n'est jamais versionné.
