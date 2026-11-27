---
name: cloture-ticket
description: Clôture d'un ticket Quest & Love, à lancer par l'agent avant la revue de code : vérifications, notes d'implémentation, doc technique, revue, commit sans attribution.
---

# /cloture-ticket

Fin d'un ticket, lancée avant la revue de code pour que la doc soit à jour quand le relecteur lit le diff. L'agent la lance seul, sans attendre de validation. Ne rien supposer : chaque point se vérifie, et un point non vérifié se dit dans le compte rendu.

## 1. Vérifications mécaniques

1. `bash scripts/verifier.sh` : doit finir par « tout est conforme ».
2. `bash scripts/tester.sh` : tests de la couche modèle (prépare `config.php`, le phar et la base).
3. Si le ticket touche un écran : le vérifier dans le navigateur, procédure dans `docs/agents/verifier-un-ecran.md`.
4. Si `app/` a changé : `graphify update .` (analyse locale, jamais `/graphify .`).

## 2. Contraintes du projet

- Couches MVC (ADR-0001) et commentaires (doc technique § 2.4) intacts : en-tête de fichier, commentaire du *pourquoi*, fichiers annotés étape par étape non allégés. Pour un contrôle indépendant du diff, lancer le sous-agent `revue-couches-mvc`.
- Pas de `TODO(simplif)` sans sa limite ni son échéance.
- Nouvelle règle reprise du cadrage : elle cite sa section (« cadrage § 4.6 »), elle ne la reformule pas.

## 3. Documentation

1. Note de la spec dans `docs/agents/notes/` : ajouter ce qui servira au ticket suivant, corriger ce que ce ticket a rendu faux.
2. Section de `04-technique/doc-technique.md` que le ticket ou sa revue rend fausse : la corriger.
3. `GOTCHAS.md` du dossier modifié : ajouter le piège découvert, s'il y en a un, avec renvoi à la règle (il ne la remplace pas).
4. Tickets et tests citent l'écran et le parcours couverts.

## 4. Revue

Lancer le sous-agent `revue-couches-mvc` sur le diff complet, doc comprise. Lui passer : le numéro du ticket, le résultat du § 1 (`verifier.sh`, `tester.sh`, vérification dans le navigateur) et le chemin de la note de spec. Corriger ce qu'il retient, puis relancer le § 1. Le skill `/mattpocock-skills:implement` ne se lance qu'à la main : sa revue s'ajoute à celle-ci quand il a été utilisé.

## 5. Commit, PR et ticket

- Committer sur la branche du ticket, sans attendre de validation. Pousser et ouvrir la PR seulement à la demande.
- En français, au nom du binôme : un titre, le détail du changement, puis `Closes #N` s'il y a lieu, et rien d'autre.
- Aucune attribution à un outil d'IA : ni `Co-Authored-By: Claude…`, ni `Claude-Session:`, ni « Generated with Claude Code », ni lien `claude.ai/code`. `.githooks/commit-msg` et la CI le refusent.
- Une PR par spec, fusionnée dans `main` avant la spec suivante.

## 6. Compte rendu

Terminer par trois lignes : ce qui est vérifié, ce qui ne l'est pas, ce qui reste à faire (ou « rien »).
