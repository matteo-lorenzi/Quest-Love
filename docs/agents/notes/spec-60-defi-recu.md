# Notes d'implémentation — spec #60, C3 Défi reçu et C1 état 6

Notes des implémenteurs des tickets de la spec #60, sur la branche d'intégration `defi-recu`.

## Ticket #76 — C3, découverte et validation

- `MissionService::construire(PDO)` (comme `RattrapageService`) : le contrôleur et les tests l'appellent.
- `MissionService::decouvrir(Joueur, $maintenant): ?Mission` : la mission reçue du jour (`trouverParDuoEtJour` du duo du joueur, destinataire = joueur, statut `en_cours` ou `valide`), après avoir posé `date_decouverte` (`MissionDepot::decouvrir` n'écrit que si elle est nulle). Null : rien à montrer (non révélée, expirée, annulée, émetteur). Seul le GET de C3 l'appelle : jamais pour lire l'état de C1.
- `MissionService::valider(Joueur, int $missionId, $maintenant): void` : transaction, `verrouillerAvecPartenaire`, mission relue sous verrou. N'écrit que si le joueur frais en est le destinataire dans son duo actuel, statut `en_cours` et `$maintenant < dateLimite` (page restée ouverte après minuit). Aucun retour : le contrôleur redirige toujours vers `defi-recu`, dont le GET montre l'état réel.
- `DefiRecuControleur` : GET `afficher` (sans mission reçue : redirection vers l'accueil ; `?confirmer=realise` ouvre le panneau, seulement en cours) ; POST `valider` reçoit `mission` (numéro) du panneau, le service refuse celui d'un autre.
- Vue `ecrans/c3-defi-recu.php` : `carte-defi`, puis « en cours » (lien vers la confirmation) ou « réalisé » ; panneau `panneau-confirmation` partagé avec C1. Pas de `navigation.php`, aucune mention de contestabilité ; un lien « Retour à l'accueil » comme C2. Pas de CSS dédié.
- Routeur : `defi-recu` en accès Jeu.
- C1 état 5 (`socle-c1.php`) : lien « Voir mon défi » vers C3.
- Démonstration : duo 9, Iris (114) et Yann (115), missions du jour révélées (tirages 5 et 6). Yann : défi validé, C3 « réalisé ». Iris : défi en cours non découvert ; elle peut le valider. Ne vaut que le jour du rechargement (CURDATE()), à toute heure.
- Tests : `MissionServiceTest` crée son défi (`setUpBeforeClass`, catégorie « Catégorie du test de mission ») et révèle par `MissionDepot::reveler`, sans passer par le rattrapage.

## Pour #77 (C1 état 6)

- Après validation, l'accueil reste à `EtatAccueil::DefiRevele` (état 5) : `JourneeService::etatAccueil` ne regarde que `date_revelation`. L'état 6 (défi reçu en cours) et sa variante ex-état 7 (défi réalisé, statut du défi envoyé) se déduisent du `statut` des deux missions du jour (`trouverParDuoEtJour` les rend déjà, reçue et envoyée).
- Le lien « Voir mon défi » est sur la variante état 5 du socle : à garder sur l'état 6.
- Ne pas appeler `MissionService::decouvrir` depuis C1 (récit 9) : la lecture de l'accueil passe par `JourneeService`.
- `carte-defi.php` n'affiche pas encore de statut (doc technique § 4.2) : à ajouter si l'état 6 montre la carte avec son statut.

## Pièges

- Toute écriture sur une mission révélée verrouille le duo par `verrouillerAvecPartenaire` (notes de la spec #59) : l'expiration de #61 se sérialise ainsi avec la validation.
- Essai HTTP : le mot de passe de démonstration se poste dans le champ `mdp` (pas `mot_de_passe`).
