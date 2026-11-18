<?php
// Contrôleur du tirage — C2 — parcours 2 (journée de jeu) — lot 1 — B. Étape « éventail » ; le choix du défi arrive avec la #71.

class TirageControleur
{
    private DefiDepot $defis;
    private TirageService $tirage;

    // Le routeur passe la connexion et l'heure ; le contrôleur construit lui-même ses dépôts et services (ADR-0001).
    public function __construct(private PDO $pdo, private DateTimeImmutable $maintenant)
    {
        $this->defis = new DefiDepot($pdo);
        $journee = new JourneeService(new ParticipationDepot($pdo), new DuoDepot($pdo));
        $this->tirage = new TirageService($pdo, $journee, new TirageDepot($pdo), $this->defis, new JoueurDepot($pdo));
    }

    // GET : le tirage du jour, créé à la première visite puis relu. Sans tirage possible (pas encore les deux oui, ou 12 h passée), retour à l'accueil, qui dit où en est la journée.
    // ?carte=1 à 3 : la carte touchée se retourne ; en toucher une autre la remplace, sans action « reposer » (cadrage § 8.5). Lire une carte n'écrit rien : un lien suffit.
    // TODO(simplif): la carte retournée ne porte pas encore l'action de confirmer le choix, ajoutée avec la #71.
    public function afficher(): void
    {
        $tirage = $this->tirage->tirer(Routeur::joueurConnecte($this->pdo), $this->maintenant);
        if ($tirage === null) {
            Routeur::rediriger('accueil');
        }
        $carte = filter_var($_GET['carte'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 3]]);
        Vue::rendre('c2-tirage', 'Tirage', [
            'defis' => array_map(fn (int $id) => $this->defis->trouverParId($id), $tirage->defiIds),
            'piochee' => $carte === false ? null : $carte,
        ]);
    }
}
