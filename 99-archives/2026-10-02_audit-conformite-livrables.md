# Audit de conformité des livrables — Quest & Love

> Audit réalisé le 02/10/2026, en lecture seule, sur le dossier `Quest-Love` (dernier commit `bcbf1a6` du 20/09/2026).
> Périmètre validé par Matt : `01-cadrage/doc-fonctionnelle.md` tient le rôle de « documentation technique » au sens des attendus, avec les annexes qu'il mobilise (wireframes, parcours, personas, inventaire des écrans). `01-cadrage/exemple-doc-fonctionnelle.md` est l'exemple fourni par l'enseignant : il est exclu de l'audit et sert seulement de comparaison de structure.
> Statuts : CONFORME · PARTIEL · ABSENT · NON VÉRIFIABLE. Référence en cas d'écart : `01-cadrage/cadrage-unifie.md` (V2.9), conformément au README.

## 1. Verdict global

1. **Non conforme en l'état.** Le document audité est une trame. Il se déclare lui-même « Statut : trame » et contient 106 marqueurs de travail restant, dont 72 « [à rédiger] » et 19 « [maquette(s)] ».
2. **Bilan des 12 points : 6 CONFORME, 4 PARTIEL, 2 ABSENT, 0 NON VÉRIFIABLE.** Les manques sont la date de mise à jour (A3) et les URL (A5). Le pitch (A4), les personas (B2) et la maquette fonctionnelle (D1, D2) sont partiels.
3. **Les sections B et C sont couvertes.** Les wireframes sont annotés, mais ils correspondent à l'état antérieur au 18/09 (34 cadres au lieu des 27 actuels) et ne sont ni liés ni intégrés au document.

## 2. Tableau de conformité

| Point | Statut | Preuve | Écart |
|---|---|---|---|
| A1 Titre | CONFORME | `doc-fonctionnelle.md` l. 1 : « # Projet _Quest & Love_ » | Identique au cadrage § 1 (« Intitulé : Quest & Love ») et au README. |
| A2 Membres | CONFORME | `doc-fonctionnelle.md` l. 8 : « Auteurs : Sheyrel, Matteo » | Identique au README (« Binôme : Sheyrel, Matteo »), à `doc-technique.md` l. 24 et à `proto-personas.md`. Prénoms seuls. Le cadrage § 2 ne nomme personne (« Personne A / B »). |
| A3 Date de mise à jour | ABSENT | Aucune date ni version propre au document. La seule date (l. 4) est celle de la référence : « Référence : cadrage unifié V2.9 (18/09/2026) » | À titre de comparaison, `cadrage-unifie.md` et `doc-technique.md` affichent « Version x — date ». |
| A4 Pitch | PARTIEL | l. 12 : « [à rédiger — reprendre et adapter le pitch du cadrage § 1] », suivi d'une phrase l. 13 : « Application web qui transforme le quotidien d'un couple en jeu : défi du jour tiré au sort, attribué au partenaire, points de vie, gages. » | Marqueur présent. Le pitch du cadrage § 1 compte quatre phrases. `doc-technique.md` § 1 a un pitch rédigé, qui diverge du cadrage (voir E5). |
| A5 Adresse (URL) | ABSENT | l. 15 : « Application : `[URL Azrael à compléter]` » ; l. 17 : « Code source : `[URL debug.php à compléter]` » | Les URL indiquées par Matt (`…/~mlorenzi/` et `…/~mlorenzi/debug.php`) ne figurent dans **aucun** fichier du dossier. Mêmes gabarits vides dans le README (l. 37-38) et dans `doc-technique.md` (l. 36-38). Accessibilité non testée. |
| B1 Scénario | CONFORME | l. 52-65, « ### Scénario d'utilisation », 10 étapes numérotées, sans marqueur. Ex. l. 61 : « Le **défi** est révélé à son destinataire (les deux ont choisi, ou 12 h) » | Cohérent avec le cadrage § 4.2 (révélation, bornes 00 h-12 h). Complété en annexe par `02-recherche-utilisateur/parcours-utilisateur.md` (journey map en 4 étapes, sans marqueur). |
| B2 Personas (optionnel) | PARTIEL, non bloquant | l. 48-49 : « (persona Sylvianne) `[à rédiger]` », « (persona Joul) `[à rédiger]` ». Les personas sont rédigées dans `02-recherche-utilisateur/proto-personas.md` (sections 1 et 2) | Aucun lien depuis le document vers `proto-personas.md`. Ce fichier se termine par « _Proto-personas — à confirmer par la recherche utilisateur_ ». |
| C1 MVP | CONFORME | l. 21-36, « ### _Minimum viable product_ (MVP) », liste en 4 groupes, sans marqueur | La liste du MVP ne cite ni « connexion » ni « accueil face-à-face », qui figurent pourtant au lot 1 du même document (l. 251, 255) et du cadrage § 11 (voir E6). |
| C2 Principales fonctionnalités | CONFORME | Listes du MVP (l. 21-36) et des lots 1 à 4 (l. 247-280), sans marqueur | La section « Principales caractéristiques » contient aussi des sous-parties avec marqueurs (l. 40-49, 69-86), mais elles ne relèvent pas de la liste des fonctionnalités. |
| C3 Découpage en lots | CONFORME | l. 247-280 : « #### Lot 1 : duo et défi du jour » à « #### Lot 4 : perspectives (non développé) », sans marqueur | Contenu identique au tableau du cadrage § 11, et cohérent avec `doc-technique.md` § 5. |
| D1 Wireframe | PARTIEL | Le dossier `03-conception/wireframes/` contient 35 PNG (34 cadres et la légende), tous datés du 17/09 à 10:10. Dans le document, 18 « `[maquette]` » et 1 « `[maquettes]` » (l. 163-221), et aucun lien vers une image (aucune syntaxe `](`) | Les captures sont **périmées** : l'inventaire v2.2 annonce « 27 cadres zonés sur 27 (34 avant la reprise du 18/09) ». Sept cadres en trop (voir § 3). La l. 154 indique « Schéma des enchaînements entre écrans `[à produire]` », alors que `03-conception/parcours/navigation-globale.png` existe. |
| D2 Indications | PARTIEL | Légende (`00-legende-et-conventions/01_legende-et-conventions.png`) : « Flèche « → ID » · écran de destination de l'action ». Les 34 cadres portent titre, sous-titre et annotations fléchées, par exemple A2 : « Créer mon compte → B2 si code valide, sinon B1 » | Les annotations suivent l'ancienne convention, pas celle du cadrage V2.9 § 8.5 (« Étiquette in situ… sans flèche », « Note d'écran… préfixée « Variante · » », « au-delà de huit annotations… regrouper »). C1 · état 1 porte 14 annotations. Il reste des renvois vers l'« état 7 » supprimé et un doublon dans la légende (voir § 3). Les descriptions textuelles des écrans sont « `[à rédiger]` » (l. 163-221). |

## 3. Écarts entre documents

| # | Écart | Sources |
|---|---|---|
| E1 | Les wireframes ne correspondent pas à l'inventaire. 34 PNG contre 27 cadres. Les 27 cadres actuels ont tous une capture, mais 7 captures en plus correspondent exactement aux 7 cadres fusionnés en V2.9 : `02_A1_accueil-public_invitation`, `02_C1_etat1_confirmation-pas-aujourdhui`, `11_C1_etat7_defi-realise`, `13_C3_defi-recu_confirmation`, `14_C3_defi-recu_realise`, `16_C1_premier-jour-demain`, `06_G2_erreur-serveur`. | `inventaire-ecrans.md` l. 78 ; `cadrage-unifie.md` « Version 2.9 » ; README, ligne `wireframes/` |
| E2 | Les intitulés des cadres diffèrent. PNG « G1 · Page introuvable » contre inventaire « G1 · Écrans d'erreur ». PNG « C1 · Accueil — cas : journée blanche » contre inventaire « C1 · Cas : aucun jeu aujourd'hui ». | Planche des wireframes ; `inventaire-ecrans.md` l. 37, 55 |
| E3 | Des renvois pointent vers l'état 7, supprimé le 18/09. Cadre C3 · confirmation : « Confirmer → C3 · réalisé ou C1 · état 7 ». Cadre C1 · état 6 : « Défi réalisé → confirmation (C3), puis état 7 ». PDF page 4 : « C1 · état 7 : défi réalisé, statut du défi envoyé ». | Wireframes `13_C3_…`, `10_C1_…` ; `Quest&Love-Parcours-Utilisateurs.pdf` |
| E4 | La légende contient deux fois la ligne « Cadre de référence », avec deux listes différentes : « A1 · par défaut, A2, B1, C1 · état 1, C2 · éventail, C3 · en cours, D2, F1 · avec duo, F2, G1 » puis « (C1 · état 1, C2 · éventail, C3 · en cours, D2) ». | `01_legende-et-conventions.png` |
| E5 | Le pitch de `doc-technique.md` § 1 dit « À midi, les deux défis se révèlent en même temps », alors que le cadrage § 4.2 dit « au premier des deux événements : les deux ont choisi, ou il est 12 h ». Le cadrage fait foi. | `doc-technique.md` l. 28-34 ; `cadrage-unifie.md` § 4.2 |
| E6 | Le MVP (l. 21-36) diffère du lot 1 (l. 249-257) dans le même document : il omet « connexion » et « accueil face-à-face », et ajoute « Profil » et « 0 PV : retour à 5 ». | `doc-fonctionnelle.md` |
| E7 | Le PDF s'intitule « Navigation globale & mode démo », alors que le mode démo a été supprimé du lot 1 en V2.5. Ce PDF contient la navigation et les parcours 1 à 3, sans lien avec la recherche utilisateur. Il est rangé dans `02-recherche-utilisateur/` et le README ne le mentionne pas. | `Quest&Love-Parcours-Utilisateurs.pdf` page 1 ; `cadrage-unifie.md` « Version 2.5 » |
| E8 | Un seul lien Figma existe dans tout le dossier : le zonage, au README l. 36. Aucun lien vers les parcours 1, 2 et 3. | `grep figma.com` sur le dossier |
| E9 | Les versions de référence sont cohérentes : « V2.9 (18/09/2026) » et « inventaire v2.2 » sont identiques dans le README, le cadrage, `doc-fonctionnelle.md` l. 4 et `inventaire-ecrans.md`. Aucun écart. | — |

## 4. Contre-vérification (étape 4)

- **Correction en cours d'audit** : l'inventaire initial annonçait 31 écrans dans `wireframes/` par erreur d'addition. Le recomptage donne 34 écrans et 1 légende, ce qui est cohérent avec le README.
- A1, A2, B1, C3 : aucun contre-exemple trouvé (pas de marqueur dans la section, valeurs identiques entre documents).
- C1 : le statut CONFORME est maintenu. L'écart E6 relève de la cohérence, pas d'un marqueur ou d'une section vide.
- C2 : le statut CONFORME est maintenu. Les marqueurs de la section « Principales caractéristiques » (l. 40-49, 69-86) concernent « Ce que l'application n'est pas », les profils et le guide utilisateur, pas la liste des fonctionnalités.
- Aucun statut n'a été modifié à cette étape.

## 5. Questions restées ouvertes

1. Les URL Azrael indiquées par Matt n'ont pas été testées. Leur accessibilité reste à vérifier après insertion.
2. Les liens Figma des parcours 1, 2 et 3 n'ont été fournis nulle part. Il faut savoir lesquels insérer.
3. Faut-il intégrer les maquettes dans `doc-fonctionnelle.md` (images, comme dans l'exemple) ou un renvoi vers `03-conception/` suffit-il ? Les attendus ne le précisent pas.
4. `exemple-doc-fonctionnelle.md` doit-il rester dans le dossier remis ?
5. `04-technique/doc-technique.md` fait-il aussi partie du rendu ? Il n'a pas été audité contre les attendus A à D.
6. Les prénoms seuls suffisent-ils pour A2 ? L'exemple donne nom complet et courriel, mais ce n'est pas imposé.

## 6. Plan d'actions

### Bloquant pour le rendu

| # | Action | Fichier | Écart | Effort |
|---|---|---|---|---|
| 1 | Renseigner l'URL de l'application et du code source, puis tester l'accès. Reporter les mêmes URL dans le README (l. 37-38) et dans `doc-technique.md` (l. 36-38). | `01-cadrage/doc-fonctionnelle.md` l. 15, 17 | A5 | Faible |
| 2 | Ajouter une date de mise à jour et une version du document. | `01-cadrage/doc-fonctionnelle.md` (en-tête) | A3 | Faible |
| 3 | Finaliser le pitch et retirer le marqueur, en restant aligné sur le cadrage § 1 et § 4.2. | `01-cadrage/doc-fonctionnelle.md` l. 12-13 | A4, E5 | Faible |
| 4 | Réexporter les 27 cadres actuels du Figma (annotations V2.9). Archiver les 35 captures actuelles dans `99-archives/` avec le préfixe `AAAA-MM-JJ_` (convention du README). | `03-conception/wireframes/` | D1, D2, E1, E2, E3, E4 | Moyen |
| 5 | Relier les maquettes au document : remplacer les 19 marqueurs « [maquette(s)] » par les images ou par des renvois. | `01-cadrage/doc-fonctionnelle.md` l. 163-221 | D1 | Moyen |

### À corriger

| # | Action | Fichier | Écart | Effort |
|---|---|---|---|---|
| 6 | Rédiger les indications par écran (éléments, actions, destinations, erreurs) marquées « [à rédiger] ». | `01-cadrage/doc-fonctionnelle.md` l. 163-221 | D2 | Élevé |
| 7 | Renseigner la carte de navigation en s'appuyant sur `navigation-globale.png` et les liens Figma des parcours. | `01-cadrage/doc-fonctionnelle.md` l. 154 | D1, E8 | Faible |
| 8 | Mettre à jour les parcours (état 7, titre « mode démo ») et réexporter le PNG et le PDF. | `03-conception/parcours/`, `02-recherche-utilisateur/Quest&Love-Parcours-Utilisateurs.pdf` | E3, E7 | Moyen |
| 9 | Aligner la liste du MVP et le lot 1. | `01-cadrage/doc-fonctionnelle.md` l. 21-36, 249-257 | E6 | Faible |
| 10 | Corriger le pitch sur la révélation. | `04-technique/doc-technique.md` l. 28-34 | E5 | Faible |
| 11 | Traiter les autres marqueurs du document (règles, guide, données personnelles, idées). Ils ne relèvent pas des attendus A à D, mais la trame en compte 106 au total. | `01-cadrage/doc-fonctionnelle.md` | Verdict, ligne 1 | Élevé |

### Amélioration

| # | Action | Fichier | Écart | Effort |
|---|---|---|---|---|
| 12 | Compléter les profils et renvoyer vers les personas. | `01-cadrage/doc-fonctionnelle.md` l. 48-49 | B2 | Faible |
| 13 | Référencer le PDF dans le README et envisager de le déplacer vers `03-conception/`. Ajouter les liens Figma des parcours. | `README.md` | E7, E8 | Faible |
| 14 | Décider du sort de l'exemple dans le dossier remis. | `01-cadrage/exemple-doc-fonctionnelle.md` | Question 4 | Faible |

## 7. Fichiers lus pendant l'audit

`README.md`, `.gitignore`, `.gitattributes`, `01-cadrage/doc-fonctionnelle.md` (intégral), `01-cadrage/cadrage-unifie.md` (§ 1, 2, 4.1-4.2, 5, 8.5, 11, 17), `01-cadrage/exemple-doc-fonctionnelle.md` (en-tête, titres, maquettes), `02-recherche-utilisateur/proto-personas.md`, `parcours-utilisateur.md`, `Quest&Love-Parcours-Utilisateurs.pdf` (texte des 4 pages), `03-conception/inventaire-ecrans.md`, les 35 PNG de `wireframes/` (en planche, avec 3 cadres et la légende en taille réelle), `parcours/navigation-globale.png`, `parcours/parcours-2_journee-de-jeu.png`, `99-archives/2026-09-15_wireframes-v0-lo-fi.png`, `04-technique/doc-technique.md` (§ 1 et 5, titres), `04-technique/modele-donnees.md` (en-tête, titres).
Non lus en détail : `lean-canvas.json`, les `.json` de `02-recherche-utilisateur/`, `parcours-1_…png` et `parcours-3_…png` (leur contenu a été vérifié par le texte du PDF). Aucun de ces fichiers ne porte un attendu A à D.
