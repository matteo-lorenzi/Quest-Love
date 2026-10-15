# Quest & Love — consignes pour les agents

Cadrage de référence : `01-cadrage/cadrage-unifie.md` (fait foi en cas d'écart).

## Standards de code

Règles : `04-technique/doc-technique.md` § 2.4, seule source ; architecture : `docs/adr/0001-controleur-frontal-mvc.md`. Contrôle mécanique (syntaxe, horloge, lignes d'en-tête) : `bash scripts/verifier.sh`, relancé par la CI.

- Tickets et tests citent l'écran et le parcours couverts.

## Commits, PR et tickets

Rédigés au nom du binôme, en français : un titre, le détail du changement, puis `Closes #N` s'il y a lieu, et rien d'autre. Garde-fou : aucune attribution à un outil d'IA, ni ligne `Co-Authored-By: Claude…` ou `Claude-Session:`, ni pied de page « Generated with / by Claude Code », ni lien `claude.ai/code`. Vérifié par `.githooks/commit-msg` et par la CI sur les PR, tickets et commentaires.

## Agent skills

### Issue tracker

Tickets et specs dans les GitHub Issues de `matteo-lorenzi/Quest-Love`, via la CLI `gh`. Voir `docs/agents/issue-tracker.md`.

### Triage labels

Cinq étiquettes par défaut : `needs-triage`, `needs-info`, `ready-for-agent`, `ready-for-human`, `wontfix`. Voir `docs/agents/triage-labels.md`.

### Domain docs

Single-context : `GLOSSARY.md` et `docs/adr/` à la racine. Voir `docs/agents/domain.md`.
