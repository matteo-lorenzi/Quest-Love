# Quest & Love — consignes pour les agents

Cadrage de référence : `01-cadrage/cadrage-unifie.md` (fait foi en cas d'écart).

## Standards de code

Référence complète : `04-technique/doc-technique.md` § 2.4 ; architecture : `docs/adr/0001-controleur-frontal-mvc.md`.

- MVC à contrôleur frontal : `app/public/index.php` est le seul fichier public ; contrôleurs fins, SQL dans les dépôts seulement, règles du jeu dans les services.
- Tout service dépendant de l'heure reçoit `DateTimeImmutable $maintenant` ; seul `index.php` lit l'horloge.
- Code en français sans accent (classes, méthodes, tables), vocabulaire de `GLOSSARY.md`.
- Vues préfixées du code d'écran de l'inventaire (`vues/ecrans/c1-accueil.php`) ; tickets et tests citent l'écran et le parcours couverts.
- HTML sémantique avant tout `div` ; classes CSS en BEM français (`carte-defi__titre`).
- Aucune bibliothèque tierce dans `app/`.

## Agent skills

### Issue tracker

Tickets et specs dans les GitHub Issues de `matteo-lorenzi/Quest-Love`, via la CLI `gh`. Voir `docs/agents/issue-tracker.md`.

### Triage labels

Cinq étiquettes par défaut : `needs-triage`, `needs-info`, `ready-for-agent`, `ready-for-human`, `wontfix`. Voir `docs/agents/triage-labels.md`.

### Domain docs

Single-context : `GLOSSARY.md` et `docs/adr/` à la racine. Voir `docs/agents/domain.md`.
