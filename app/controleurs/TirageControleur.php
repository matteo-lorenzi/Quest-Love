<?php
// Contrôleur du tirage — C2 — parcours 2 (journée de jeu) — lot 1 — B. Étapes « éventail » et « carte piochée », puis le choix du défi.

class TirageControleur
{
    // C2 ouvert passé 12 h par celui qui n'a pas choisi, y compris après un choix refusé : la journée est passée.
    private const MIDI_PASSE = 'Il est midi passé : le défi de ton partenaire se choisit avant 12 h, ce n\'est plus possible aujourd\'hui.';

    private DefiDepot $defis;
    private TirageService $tirage;

    // Le routeur passe la connexion et l'heure ; le contrôleur construit lui-même ses dépôts et services (ADR-0001).
    public function __construct(private PDO $pdo, private DateTimeImmutable $maintenant)
    {
        $this->defis = new DefiDepot($pdo);
        $journee = new JourneeService(new ParticipationDepot($pdo), new DuoDepot($pdo), new MissionDepot($pdo));
        $this->tirage = new TirageService($pdo, $journee, new TirageDepot($pdo), $this->defis, new JoueurDepot($pdo), new MissionDepot($pdo));
    }

    // GET : le tirage du jour, créé à la première visite puis relu. Hors état 3 (pas encore les deux oui, choix déjà fait), retour à l'accueil, qui dit où en est la journée.
    // ?carte=1 à 3 : la carte touchée se retourne ; en toucher une autre la remplace, sans action « reposer » (cadrage § 8.5). Lire une carte n'écrit rien : un lien suffit.
    public function afficher(): void
    {
        $joueur = Routeur::joueurConnecte($this->pdo);
        $tirage = $this->tirageOuSortie($joueur);
        if ($tirage === null) {
            return;
        }
        $carte = filter_var($_GET['carte'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 3]]);
        $this->rendre($tirage, $carte === false ? null : $carte, null);
    }

    // POST : le jeton CSRF a déjà été vérifié par le routeur. Le formulaire envoie le rang de la carte piochée, jamais un numéro de défi : seul un défi du tirage peut être choisi.
    // Choix refusé (12 h passée), carte invalide ou plus d'éventail à montrer : retour à C2, dont le GET dit que la journée est passée ou renvoie à l'accueil
    // (redirection après écriture, § 2.4). Choix accepté ou déjà fait : l'accueil dit « défi envoyé ».
    public function choisir(): void
    {
        $joueur = Routeur::joueurConnecte($this->pdo);
        $tirage = $this->tirage->tirer($joueur, $this->maintenant);
        $carte = filter_var($_POST['carte'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 3]]);
        if (!$tirage instanceof Tirage || $carte === false || !$this->tirage->choisir($joueur, $tirage->defiIds[$carte - 1], $this->maintenant)) {
            Routeur::rediriger('tirage');
        }
        Routeur::rediriger('accueil');
    }

    // Le tirage à montrer, ou null quand la réponse est déjà partie : journée blanche faute de choix, C2 dit en texte à celui qui n'a pas choisi que le choix
    // n'est plus possible (sans renvoi vers l'accueil, qui ramènerait ici) ; celui qui avait choisi retourne à l'accueil, dont la journée blanche explique la situation ;
    // hors état 3, retour à l'accueil.
    private function tirageOuSortie(Joueur $joueur): ?Tirage
    {
        $tirage = $this->tirage->tirer($joueur, $this->maintenant);
        if ($tirage === EtatAccueil::JourneeBlancheSansChoix && !$this->tirage->aChoisi($joueur, $this->maintenant)) {
            $this->rendre(null, null, self::MIDI_PASSE);
            return null;
        }
        if ($tirage instanceof EtatAccueil) {
            Routeur::rediriger('accueil');
        }
        return $tirage;
    }

    // $tirage : null quand le choix n'est plus possible, seul $message est alors montré ; $retournee : rang de la carte retournée, ou null.
    private function rendre(?Tirage $tirage, ?int $retournee, ?string $message): void
    {
        Vue::rendre('c2-tirage', 'Tirage', [
            'defis' => $tirage === null ? [] : array_map(fn (int $id) => $this->defis->trouverParId($id), $tirage->defiIds),
            'retournee' => $retournee,
            'message' => $message,
        ]);
    }
}
