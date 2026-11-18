<?php
// Contrôleur de l'accueil — C1 — parcours 2 (journée de jeu) — lot 1 — B.

class AccueilControleur
{
    private JourneeService $journee;

    // Le routeur passe la connexion et l'heure ; le contrôleur construit lui-même ses dépôts et services (ADR-0001).
    public function __construct(private PDO $pdo, private DateTimeImmutable $maintenant)
    {
        $this->journee = new JourneeService(new ParticipationDepot($pdo));
    }

    // GET : l'état vient du service ; le contrôleur n'ajoute que le panneau de confirmation demandé par l'adresse.
    // TODO(simplif): quand les deux ont dit oui, aucun état de tirage n'est affiché, à remplacer par les états 3 et 4 avec #58.
    public function afficher(): void
    {
        $joueur = Routeur::joueurConnecte($this->pdo);
        $etat = $this->journee->etatAccueil($joueur, $this->maintenant);
        Vue::rendre('c1-accueil', 'Accueil', [
            'etat' => $etat,
            'pseudo' => $joueur->pseudo,
            'partenaire' => AppairageService::construire($this->pdo)->pseudoPartenaire($joueur),
            // Le panneau « pas aujourd'hui » s'ouvre par un lien, sans JavaScript (la CSP n'en permet aucun), et seulement tant que la question se pose.
            'confirmerNon' => $etat === EtatAccueil::Question && ($_GET['confirmer'] ?? null) === 'non',
        ]);
    }

    // POST : le jeton CSRF a déjà été vérifié par le routeur. Réponse inconnue ou seconde réponse : rien n'est écrit, l'accueil se recharge sans erreur.
    public function repondre(): void
    {
        $reponse = is_string($_POST['reponse'] ?? null) ? Reponse::tryFrom($_POST['reponse']) : null;
        if ($reponse !== null) {
            $this->journee->repondre(Routeur::joueurConnecte($this->pdo), $reponse, $this->maintenant);
        }
        Routeur::rediriger('accueil');
    }
}
