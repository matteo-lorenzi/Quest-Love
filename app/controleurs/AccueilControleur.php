<?php
// Contrôleur de l'accueil — C1 — parcours 2 (journée de jeu) — lot 1 — B.

class AccueilControleur
{
    private JourneeService $journee;

    // Le routeur passe la connexion et l'heure ; le contrôleur construit lui-même ses dépôts et services (ADR-0001).
    public function __construct(private PDO $pdo, private DateTimeImmutable $maintenant)
    {
        $this->journee = new JourneeService(new ParticipationDepot($pdo), new DuoDepot($pdo), new MissionDepot($pdo));
    }

    // GET : l'état vient du service ; le contrôleur n'ajoute que le panneau de confirmation demandé par l'adresse.
    public function afficher(): void
    {
        $this->rendre(Routeur::joueurConnecte($this->pdo), null);
    }

    // POST : le jeton CSRF a déjà été vérifié par le routeur. Réponse inconnue ou seconde réponse : rien n'est écrit, l'accueil se recharge sans erreur.
    // Réponse jamais donnée arrivée à 12 h ou après : refusée, l'accueil s'affiche dans son état réel avec le message, sans redirection qui le perdrait.
    // Celui qui avait déjà répondu et renvoie après 12 h n'est pas refusé : simple retour à l'accueil.
    public function repondre(): void
    {
        $joueur = Routeur::joueurConnecte($this->pdo);
        $reponse = is_string($_POST['reponse'] ?? null) ? Reponse::tryFrom($_POST['reponse']) : null;
        if ($reponse !== null && !$this->journee->repondre($joueur, $reponse, $this->maintenant)) {
            $this->rendre($joueur, 'Il est midi passé : ta réponse n\'a pas été prise en compte. Les réponses se donnent avant 12 h.');
            return;
        }
        Routeur::rediriger('accueil');
    }

    // L'état vient du service ; $message (ou null) explique une réponse refusée.
    private function rendre(Joueur $joueur, ?string $message): void
    {
        $etat = $this->journee->etatAccueil($joueur, $this->maintenant);
        Vue::rendre('c1-accueil', 'Accueil', [
            'etat' => $etat,
            'pseudo' => $joueur->pseudo,
            'partenaire' => AppairageService::construire($this->pdo)->pseudoPartenaire($joueur),
            // Le panneau « pas aujourd'hui » s'ouvre par un lien, sans JavaScript (la CSP n'en permet aucun), et seulement tant que la question se pose.
            'confirmerNon' => $etat === EtatAccueil::Question && ($_GET['confirmer'] ?? null) === 'non',
            'message' => $message,
        ]);
    }
}
