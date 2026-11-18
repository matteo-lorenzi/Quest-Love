<?php
// Contrôleur du tirage — C2 — parcours 2 (journée de jeu) — lot 1 — B. Étapes « éventail » et « carte piochée », puis le choix du défi.

class TirageControleur
{
    private DefiDepot $defis;
    private JourneeService $journee;
    private TirageService $tirage;

    // Le routeur passe la connexion et l'heure ; le contrôleur construit lui-même ses dépôts et services (ADR-0001).
    public function __construct(private PDO $pdo, private DateTimeImmutable $maintenant)
    {
        $this->defis = new DefiDepot($pdo);
        $this->journee = new JourneeService(new ParticipationDepot($pdo), new DuoDepot($pdo), new MissionDepot($pdo));
        $this->tirage = new TirageService($pdo, $this->journee, new TirageDepot($pdo), $this->defis, new JoueurDepot($pdo), new MissionDepot($pdo));
    }

    // GET : le tirage du jour, créé à la première visite puis relu. Sans tirage possible (pas encore les deux oui, ou 12 h passée), ou choix déjà fait, retour à l'accueil, qui dit où en est la journée.
    // ?carte=1 à 3 : la carte touchée se retourne ; en toucher une autre la remplace, sans action « reposer » (cadrage § 8.5). Lire une carte n'écrit rien : un lien suffit.
    public function afficher(): void
    {
        $joueur = Routeur::joueurConnecte($this->pdo);
        $tirage = $this->tirage->tirer($joueur, $this->maintenant);
        if ($tirage === null || $this->journee->etatAccueil($joueur, $this->maintenant) === EtatAccueil::DefiEnvoye) {
            Routeur::rediriger('accueil');
        }
        $carte = filter_var($_GET['carte'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 3]]);
        $this->rendre($tirage, $carte === false ? null : $carte, null);
    }

    // POST : le jeton CSRF a déjà été vérifié par le routeur. Le formulaire envoie le rang de la carte piochée, jamais un numéro de défi : seul un défi du tirage peut être choisi.
    // Choix arrivé à 12 h ou après : refusé, C2 s'affiche avec le message, sans redirection qui le perdrait. Choix accepté ou déjà fait : l'accueil dit « défi envoyé ».
    public function choisir(): void
    {
        $joueur = Routeur::joueurConnecte($this->pdo);
        $tirage = $this->tirage->tirer($joueur, $this->maintenant);
        if ($tirage === null) {
            Routeur::rediriger('accueil');
        }
        $carte = filter_var($_POST['carte'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 3]]);
        if ($carte === false) {
            Routeur::rediriger('tirage');
        }
        if (!$this->tirage->choisir($joueur, $tirage->defiIds[$carte - 1], $this->maintenant)) {
            $this->rendre($tirage, $carte, 'Il est midi passé : ton choix n\'a pas été pris en compte. Le défi se choisit avant 12 h.');
            return;
        }
        Routeur::rediriger('accueil');
    }

    // $piochee : rang de la carte retournée, ou null ; $message (ou null) explique un choix refusé.
    private function rendre(Tirage $tirage, ?int $piochee, ?string $message): void
    {
        Vue::rendre('c2-tirage', 'Tirage', [
            'defis' => array_map(fn (int $id) => $this->defis->trouverParId($id), $tirage->defiIds),
            'piochee' => $piochee,
            'message' => $message,
        ]);
    }
}
