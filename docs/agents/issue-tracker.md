# Gestion des tickets : GitHub

Les tickets et les specs de ce dépôt sont des GitHub Issues (`matteo-lorenzi/Quest-Love`). Toutes les opérations passent par la CLI `gh`.

## Conventions

- **Créer un ticket** : `gh issue create --title "..." --body "..."`. Utiliser un heredoc pour un corps sur plusieurs lignes.
- **Lire un ticket** : `gh issue view <numéro> --json number,title,body,labels,comments`.
- **Lister les tickets** : `gh issue list --state open --json number,title,body,labels,comments --jq '[.[] | {number, title, body, labels: [.labels[].name], comments: [.comments[].body]}]'`, avec les filtres `--label` et `--state` adaptés.
- **Rattacher un ticket à un parent (sous-ticket)** : `gh issue create --parent <parent> ...`, ou `gh issue edit <parent> --add-sub-issue <enfant>` après coup (`gh` 2.94+). `gh` plus ancien : `gh api --method POST repos/<owner>/<repo>/issues/<parent>/sub_issues -F sub_issue_id=<id-base-enfant>` (identifiant de base de données, comme dans **Blocage** ci-dessous). Sans sous-tickets, écrire `Part of #<parent>` en tête du corps de l'enfant.
- **Commenter** : `gh issue comment <numéro> --body "..."`
- **Ajouter / retirer une étiquette** : `gh issue edit <numéro> --add-label "..."` / `--remove-label "..."`
- **Fermer** : `gh issue close <numéro> --comment "..."`

Le dépôt se déduit de `git remote -v` ; `gh` le fait seul dans un clone.

## Pull requests comme canal de demande

**PR comme canal de demande : non.** _(Passer à `oui` si ce dépôt traite les PR externes comme des demandes de fonctionnalité ; `/triage` lit ce drapeau.)_

Sur `oui`, les PR suivent les mêmes étiquettes et états que les tickets, avec les équivalents `gh pr` :

- **Lire une PR** : `gh pr view <numéro> --comments` et `gh pr diff <numéro>` pour le diff.
- **Lister les PR externes à trier** : `gh api --paginate 'repos/{owner}/{repo}/pulls?state=open' --jq '.[] | select(.author_association | IN("OWNER","MEMBER","COLLABORATOR") | not) | {number, title, author: .user.login, author_association, labels: [.labels[].name]}'`.
- **Commenter / étiqueter / fermer** : `gh pr comment`, `gh pr edit --add-label`/`--remove-label`, `gh pr close`.

GitHub partage une seule numérotation entre tickets et PR : un `#42` seul peut être l'un ou l'autre. Essayer `gh pr view 42`, puis `gh issue view 42`.

## Quand un skill dit « publier dans le gestionnaire de tickets »

Créer une GitHub Issue.

## Quand un skill dit « récupérer le ticket concerné »

Le lire comme dans **Lire un ticket** ci-dessus.

## Opérations de cartographie (wayfinding)

Utilisées par `/wayfinder`. La **carte** est un ticket unique, dont les tickets **enfants** sont les sous-tickets.

- **Carte** : un ticket étiqueté `wayfinder:map`, qui porte le corps Notes / Décisions prises / Zones floues. `gh issue create --label wayfinder:map`.
- **Ticket enfant** : un ticket lié à la carte comme sous-ticket GitHub (voir **Rattacher un ticket à un parent**). Sans sous-tickets, ajouter l'enfant à une liste de tâches dans le corps de la carte et écrire `Part of #<carte>` en tête du corps de l'enfant. Étiquettes : `wayfinder:<type>` (`research`/`prototype`/`grilling`/`task`). Une fois pris, le ticket est assigné au développeur qui le mène.
- **Blocage** : les **dépendances natives** de GitHub, représentation canonique visible dans l'interface. Ajouter une arête avec `gh api --method POST repos/<owner>/<repo>/issues/<enfant>/dependencies/blocked_by -F issue_id=<id-base-bloquant>`, où `<id-base-bloquant>` est l'**identifiant de base de données** numérique du bloquant (`gh api repos/<owner>/<repo>/issues/<n> --jq .id`, _pas_ le `#numéro` ni le `node_id`). GitHub expose `issue_dependencies_summary.blocked_by` (bloquants encore ouverts seulement). À défaut de dépendances, écrire une ligne `Blocked by: #<n>, #<n>` en tête du corps de l'enfant. Un ticket est débloqué quand tous ses bloquants sont fermés.
- **Frontière** : lister les enfants ouverts de la carte (`gh issue list --state open`, limité aux sous-tickets / à la liste de tâches de la carte), écarter ceux qui ont un bloquant ouvert (`issue_dependencies_summary.blocked_by > 0`, ou un ticket ouvert dans la ligne `Blocked by`) ou un assigné ; le premier dans l'ordre de la carte l'emporte.
- **Prendre** : `gh issue edit <n> --add-assignee @me`, première écriture de la session.
- **Résoudre** : `gh issue comment <n> --body "<réponse>"`, puis `gh issue close <n>`, puis ajouter un pointeur de contexte (résumé + lien) aux Décisions prises de la carte.
