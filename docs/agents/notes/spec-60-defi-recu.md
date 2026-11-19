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
- Démonstration : duo 9, Iris (114) et Yann (115), missions du jour révélées (tirages 5 et 6). Yann : défi validé, C3 « réalisé ». Iris : défi en cours, déjà découvert depuis #77 (C1 état 6) ; elle peut le valider. Ne vaut que le jour du rechargement (CURDATE()), à toute heure. La découverte au premier affichage de C3 ne se montre plus en démonstration (Lina et Sam, duo 7, passent par l'état 5 sans découverte).
- Tests : `MissionServiceTest` crée son défi (`setUpBeforeClass`, catégorie « Catégorie du test de mission ») et révèle par `MissionDepot::reveler`, sans passer par le rattrapage.

## Ticket #77 — C1 état 6 et statut du défi envoyé

- `EtatAccueil::DefiEnCours` (état 6) et `EtatAccueil::DefiRealise` (sa variante, ex-état 7). `JourneeService::etatAccueil`, sur la mission reçue révélée : `valide` → variante ; `date_decouverte` posée → état 6 ; sinon état 5. Lecture seule : C1 n'appelle jamais `MissionService::decouvrir` (récit 9).
- `JourneeService::statutDefiEnvoye(Joueur, $maintenant): ?StatutMission` : statut de la mission du jour émise par le joueur, une fois révélée ; null avant. `AccueilControleur` le passe à la vue (`$defiEnvoye`).
- Socle C1 : la colonne du partenaire porte ce statut dès l'état 5 (« Défi en cours », « Défi réalisé », « Défi expiré », sinon « Défi envoyé »). État 6 : lien « Voir mon défi » ; variante : lien secondaire « Revoir mon défi ». La carte du défi n'est pas affichée sur C1 : `carte-defi.php` reste sans statut.
- Tests : `MissionServiceTest` (état 5 → 6 → variante à travers `decouvrir` et `valider` ; statut envoyé null, en cours, réalisé). `journeeDesDeuxOui()` y répond oui pour les deux.

## Pour #61 (expiration)

- `etatAccueil` porte un `TODO(simplif)` : une mission reçue expirée après découverte reste en état 6 (non découverte : état 5). L'état de C1 après expiration est à décider là.

## Pièges

- Toute écriture sur une mission révélée verrouille le duo par `verrouillerAvecPartenaire` (notes de la spec #59) : l'expiration de #61 se sérialise ainsi avec la validation.
- Essai HTTP : le mot de passe de démonstration se poste dans le champ `mdp` (pas `mot_de_passe`).
