# Quest & Love — Suivi des remarques de l'encadrant et du jury

> Toutes les remarques reçues de l'encadrant (Yannis Delmas) et, à terme, du jury : par courriel, en cours ou en soutenance. Chaque remarque est datée, rattachée à sa source et suivie jusqu'à sa résolution.
> Ce document ne fixe aucune règle : une remarque qui change une règle est d'abord reportée dans le cadrage, puis marquée « traitée » ici avec le renvoi.
>
> **Version 1.0 — 14 octobre 2026.** Remarques des courriels du 17/09, du 18/09, du 07/10 et du 12/10/2026.

## Comment tenir ce document

- **Le jour même** : toute remarque reçue est ajoutée, par écrit ou de mémoire après un cours (préciser « oral »).
- **Statuts** : _ouverte_ (rien n'est fait), _en cours_ (un travail ou une réponse est engagé), _traitée_ (le document ou le code qui y répond est fusionné dans `main`).
- **Réponse** : toujours un renvoi vers l'endroit qui traite la remarque (section, ADR, demande de fusion), jamais une recopie.
- Les points positifs sont gardés aussi : ils indiquent ce qu'il ne faut pas défaire et servent d'arguments en soutenance.

## Synthèse

| Statut | Nombre |
|---|---|
| Ouvertes | 3 |
| En cours | 4 |
| Traitées | 12 |

Les trois remarques ouvertes concernent la documentation fonctionnelle (07-2, 07-3, 07-4) : à traiter avant la livraison de S6.

## Courriel du 17/09/2026 — maquettes de zonage

| # | Remarque | Statut | Réponse |
|---|---|---|---|
| 17-1 | Malentendu possible sur ce qu'est une maquette fonctionnelle ; exemples fournis (gestion des variantes, indications en organigramme). | Traitée | Reprise du zonage du 18/09 : cadrage V2.9, inventaire des écrans v2.2. |
| 17-2 | Maquettes peu lisibles : beaucoup de cadres gris qui ne prennent sens qu'avec la flèche. Une seule annotation avec une petite liste suffit souvent ; un élément actif important (bouton) s'indique directement dessus. | Traitée | Reprise du zonage du 18/09 (inventaire v2.2). |
| 17-3 | Une variante ou un popover se note par une simple annotation, sans vue dédiée, surtout pour des boîtes de dialogue standard. | Traitée | 34 cadres ramenés à 27 ; variantes notées sous le cadre concerné (inventaire v2.2, doc fonctionnelle § Maquettes et écrans). |
| 17-4 | Beaucoup de situations, donc beaucoup de vues : mettre les vues principales dans le document et renvoyer aux autres par lien (Figma ou PDF), en s'assurant que l'outil de développement peut y accéder. | En cours | Doc fonctionnelle : un lien Figma par écran. Reste à vérifier l'accès de l'outil de développement au Figma [à vérifier] ; lié à 07-3 (vue synoptique). |
| 17-5 | La description technique sera volumineuse : la chapitrer, favoriser lecture et navigation, la découper si besoin. Objectif : permettre d'implémenter et de faire évoluer le produit ; viser la lisibilité et la maintenabilité. | Traitée | Doc technique chapitrée avec sommaire ; modèle de données en annexe ; décisions d'architecture dans `docs/adr/`. À surveiller : la doc technique grossit (V1.2). |

## Courriel du 18/09/2026

| # | Remarque | Statut | Réponse |
|---|---|---|---|
| 18-1 | Envoyer l'état de la documentation, notamment le scénario utilisateur, pour des conseils plus pertinents sur les maquettes. | Traitée | Documentation envoyée avant le 06/10 (réception confirmée par l'encadrant). |

## Courriel et cours du 07/10/2026 — validation de la conception

Points positifs relevés : le projet est clair ; le MVP est bien décrit et la rubrique « ce que l'application n'est pas » est appréciée ; les règles sont claires et pas trop lourdes.

| # | Remarque | Statut | Réponse |
|---|---|---|---|
| 07-1 | (Oral) Architecture MVC attendue, « pensée entité et vue ». | Traitée | ADR-0001 ; doc technique V1.1 puis V1.2, § 2. |
| 07-2 | Schéma de navigation pas toujours clair (plusieurs sorties de F1) ; le relier au scénario d'utilisation, en langage d'organigramme (Mermaid possible). | **Ouverte** | À faire : organigramme Mermaid dans la doc fonctionnelle (§ Carte de navigation), étapes du scénario d'utilisation en regard. |
| 07-3 | Maquettes lisibles dans l'ensemble, mais pas de vision synoptique. | **Ouverte** | À faire : une planche d'ensemble des 27 cadres, ou un tableau synoptique écran par écran dans la doc fonctionnelle. |
| 07-4 | Statut des maquettes un peu intermédiaire entre maquette fonctionnelle et maquette graphique. | **Ouverte** | À trancher : soit assumer la maquette fonctionnelle (retirer ce qui relève du graphisme), soit le dire explicitement dans la doc fonctionnelle. Les maquettes graphiques sont prévues en S5. |
| 07-5 | Ajouter les codes d'écran dans la répartition en lots. | Traitée | Doc fonctionnelle V1.1, § Lots ; doc technique § 5 (PR #1). |
| 07-6 | Le lot 1 est accessible ; reste à voir comment effectuer ou simuler la gestion du temps. | Traitée | Doc technique V1.2, § 3.8 (horloge unique, décalage de démonstration réglé côté serveur) ; doc fonctionnelle § Lots (PR #1). Le courriel mentionne « une utilisation de SAI » [à vérifier : sens du sigle]. |

## Courriel du 12/10/2026 — questions techniques avant le code

| # | Remarque | Statut | Réponse |
|---|---|---|---|
| 12-1 | Les MVC vont souvent avec des frameworks (ORM, gabarits) qui ne convergent pas toujours avec les bonnes pratiques du web : confirmer que le binôme assume cet écart avec les objectifs du cours. | En cours | ADR-0001, section « Un MVC sans framework » ; confirmation orale à donner au cours du 14/10. |
| 12-2 | Tests acceptés, à condition que tout soit accessible à l'encadrant. | En cours | Doc technique § 6.4. Reste à vérifier que l'encadrant a accès au dépôt GitHub [à vérifier]. |
| 12-3 | Développement local possible, mais intérêt discutable : trois bases à maintenir synchronisées. | Traitée | Doc technique § 6.3 : une seule source (`sql/`), bases locales reconstructibles en une commande, garde-fou contre l'effacement d'Azrael. |
| 12-4 | `.htaccess` pris en compte sur Azrael. | Traitée | Doc technique § 1.1, § 6.1 ; ADR-0001. |
| 12-5 | Pas d'accès SSH en l'état ; discutable s'il est justifié, mais refus maintenu si une IA lance des commandes sur des machines personnelles avec les clés d'accès. | Traitée | Doc technique § 1.1, § 6.1 : déploiement par SFTP, tests et bases en local. **Contrainte à respecter** : aucune clé d'accès à Azrael confiée à un outil d'IA. |
| 12-6 | Démarrage du code avant la validation du schéma : à discuter au cours. | En cours | Cadrage § 16, décision ouverte n° 18 ; réponse attendue au cours du 14/10. |
| 12-7 | Code aussi léger et simple que possible, pour la maintenabilité et l'évolutivité ; BEM approuvé, surtout avec un design system. | Traitée | Doc technique § 2.4 (HTML sémantique, BEM, garde-fou de simplicité), § 4.5. |

## Questions de jury anticipées

Réponses courtes à préparer pour la soutenance, chacune adossée à un document.

| Question probable | Réponse en une phrase | Où |
|---|---|---|
| Pourquoi un MVC sans framework ? | Le cours exclut toute bibliothèque ; nous avons gardé le découpage du MVC, qui rend le code testable, sans les outils qui éloignent des bonnes pratiques du web. | ADR-0001 |
| Comment démontrez-vous minuit sans attendre minuit ? | L'heure est lue une seule fois par requête ; un décalage réglé côté serveur la déplace pour la démonstration, et les tests imposent l'heure de leur choix. | Doc technique § 3.8 |
| Pourquoi pas de tâche planifiée ? | Azrael n'en propose pas ; le rattrapage au chargement de page rejoue les échéances manquées, et deviendrait un script planifié sans changer sa logique. | Doc technique § 3.2, § 7 |
| Pourquoi ces règles de mot de passe ? | Nous suivons la CNIL, référence française pour un projet soumis au RGPD, au niveau le plus exigeant ; la norme américaine NIST préfère la longueur sans règle de composition, écart connu et assumé. | Cadrage § 6.6 |
| Que se passe-t-il si un joueur oublie son mot de passe ? | Le compte est perdu, choix assumé de minimisation des données (aucune adresse électronique) ; l'avertissement à l'inscription et le copier-coller pour les gestionnaires de mots de passe en sont la contrepartie. | Cadrage § 6.6, § 10 |
| Deux téléphones sur le même compte ? | C'est le cas normal de la démonstration : unicités en base et verrou dans le rattrapage empêchent toute double pénalité. | Cadrage § 6.7 ; doc technique § 3.2 |
