# Documentation du domaine

Comment les skills d'ingénierie lisent la documentation du domaine de ce dépôt lorsqu'ils explorent le code.

## Avant d'explorer, lire

- **`GLOSSARY.md`** à la racine du dépôt.
- **`docs/adr/`** : lire les ADR qui touchent la zone sur laquelle on s'apprête à travailler.
- **`01-cadrage/cadrage-unifie.md`** : source de vérité des règles du jeu. En cas d'écart avec un autre document, le cadrage fait foi.

Si l'un de ces fichiers n'existe pas, **continuer sans le signaler**. Ne pas suggérer de le créer d'avance : le skill `/domain-modeling` (atteint via `/grill-with-docs` et `/improve-codebase-architecture`) les crée au fil de l'eau, quand un terme ou une décision est effectivement tranché.

## Structure des fichiers

Dépôt single-context :

```
/
├── GLOSSARY.md
├── docs/adr/
│   ├── 0001-exemple-de-decision.md
│   └── 0002-autre-decision.md
└── (code source)
```

## Employer le vocabulaire du glossaire

Quand une production nomme un concept du domaine (titre de ticket, proposition de refactorisation, hypothèse, nom de test), employer le terme défini dans `GLOSSARY.md`. Ne pas dériver vers les synonymes que le glossaire classe en « _Éviter_ ».

Si le concept nécessaire n'est pas encore dans le glossaire, c'est un signal : soit on invente un langage que le projet n'emploie pas (à reconsidérer), soit il y a un vrai manque (à noter pour `/domain-modeling`).

## Signaler les conflits avec un ADR

Si une production contredit un ADR existant, le signaler explicitement plutôt que de passer outre en silence :

> _Contredit l'ADR-0003 (calcul au chargement de page), mais mérite d'être rouvert parce que…_
