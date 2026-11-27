<?php
// Contrôleur du défi reçu — C3 — parcours 2 (journée de jeu) — lot 1 — B. Sous-états « en cours », « confirmation réalisé » et « réalisé ».

class DefiRecuControleur
{
    private MissionService $missions;

    // Le routeur passe la connexion et l'heure ; le contrôleur construit lui-même ses dépôts et services (ADR-0001).
    public function __construct(private PDO $pdo, private DateTimeImmutable $maintenant)
    {
        $this->missions = MissionService::construire($pdo);
    }

    // GET : lire le défi reçu pose la découverte (doc technique § 3.4). Sans défi reçu à montrer (pas encore révélé, expiré, annulé), retour à l'accueil, qui dit où en est la journée.
    // ?confirmer=realise ouvre le panneau de confirmation par l'adresse, sans JavaScript, et seulement tant que le défi est en cours.
    public function afficher(): void
    {
        $mission = $this->missions->decouvrir(Routeur::joueurConnecte($this->pdo), $this->maintenant);
        if ($mission === null) {
            Routeur::rediriger('accueil');
        }
        $enCours = $mission->statut === StatutMission::EnCours;
        Vue::rendre('c3-defi-recu', 'Défi reçu', [
            'defi' => (new DefiDepot($this->pdo))->trouverParId($mission->defiId),
            'missionId' => $mission->id,
            'enCours' => $enCours,
            'confirmerRealise' => $enCours && ($_GET['confirmer'] ?? null) === 'realise',
            'dateLimite' => $mission->dateLimite,
            // Depuis $maintenant, donc décalage de démonstration compris (doc technique § 3.8) : une soustraction pour l'affichage, pas une règle du jeu.
            'secondesRestantes' => $enCours ? max(0, $mission->dateLimite->getTimestamp() - $this->maintenant->getTimestamp()) : null,
        ]);
    }

    // POST : le jeton CSRF a déjà été vérifié par le routeur. Validation acceptée ou refusée, retour au GET, qui montre l'état réel (redirection après écriture, § 2.4).
    // Le numéro de mission vient du formulaire : le service refuse celui d'une mission dont le joueur n'est pas le destinataire.
    public function valider(): void
    {
        $missionId = filter_var($_POST['mission'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($missionId !== false) {
            $this->missions->valider(Routeur::joueurConnecte($this->pdo), $missionId, $this->maintenant);
        }
        Routeur::rediriger('defi-recu');
    }
}
