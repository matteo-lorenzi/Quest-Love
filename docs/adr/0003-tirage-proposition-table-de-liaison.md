# `tirage_proposition` reste une table de liaison, le tirage n'entre pas dans `defi`

L'enseignant référent, en relisant le modèle de données, a demandé de supprimer la table intermédiaire entre tirage et défis et de mettre l'id du tirage dans le défi. Les deux se heurtent à la nature des entités : `defi` est une bibliothèque partagée d'environ 50 entrées (cadrage § 4.6), réutilisée par de nombreux tirages ; la `mission` est l'instance, le défi est le modèle. Un `tirage_id` dans `defi` ne retiendrait qu'un seul tirage et écraserait les précédents. Entre un tirage (3 propositions exactement) et un défi (0 à n tirages), l'association est de type n-n : elle ne se range pas dans l'une des deux tables sans perdre de l'information.

La table `tirage_proposition` est donc conservée. La mission lui est reliée par une clé étrangère composite `(tirage_id, defi_id)` : le défi retenu, ou tiré au hasard à 12 h (cadrage § 4.2), est forcément l'une des trois propositions de son tirage (modèle de données v1.7).

## Considered Options

- **`tirage_id` dans `defi`** : rejetée. Un défi servirait à un seul tirage ; la bibliothèque ne pourrait plus être réutilisée, et l'exclusion « non reproposé pendant 3 jours » (cadrage § 4.6) n'aurait plus d'historique à lire.
- **`defi_1_id`, `defi_2_id`, `defi_3_id` dans `tirage`** : rejetée. Groupe répétitif ; la requête d'exclusion des 3 jours demande trois `OR` ou un `UNION` ; l'absence de doublon parmi les trois ne se garantit que par un `CHECK`, alors qu'elle tient aujourd'hui à l'unicité `(tirage_id, defi_id)`. Plan B, à ne reprendre que si l'enseignant maintient sa demande.
- **Table `tirage_proposition` conservée** : retenue. Association n-n réelle (tirage 3,3 ↔ défi 0,n), unicité par contrainte, requête d'exclusion en une jointure.

## Consequences

- `mission(tirage_id, defi_id)` référence `tirage_proposition(tirage_id, defi_id)`, suppression en cascade : le défi d'une mission ne peut pas sortir des trois propositions de son tirage. L'unicité `(tirage_id, defi_id)` exigée par cette clé existe déjà.
- La règle « exactement trois propositions » n'est pas posée en base. L'application insère les trois lignes dans la transaction qui crée le tirage ; un test le vérifie (cadrage § 6.2).
- Les copies voulues (`mission.duo_id`, `emetteur_id`, `destinataire_id`, `date_limite` ; `tirage.destinataire_id`) restent, chacune marquée `TODO(simplif)` : la divergence entre copie et source est possible, à revoir si un bug apparaît.
- Aucun SQL ni PHP ne change : `tirage_proposition` n'existe encore que dans la documentation.

## Argument à donner à l'enseignant

Le défi est une bibliothèque partagée : le même défi sert à de nombreux tirages, donc l'id du tirage ne peut pas y vivre. La table `tirage_proposition` n'est pas un intermédiaire superflu, c'est l'association n-n entre un tirage (3 défis) et un défi (0 à n tirages), et la clé composite de la mission garantit que le défi attribué vient bien des trois proposés.
