<?php
// Contrôleur de l'onboarding des règles — B2 — parcours 1 (appairage) — lot 1 — A.

class OnboardingControleur
{
    // Le routeur passe la connexion et l'heure ; le contrôleur construit lui-même ce dont il a besoin (ADR-0001).
    public function __construct(private PDO $pdo, private DateTimeImmutable $maintenant) {}

    // GET : une règle par écran, choisie par &etape=.
    public function afficher(): void
    {
        $joueur = $this->joueurConnecte();
        // Les deux joueurs ont fini : B2 n'est plus un écran de passage.
        if ($this->service()->etapeAcces($joueur) === EtapeAcces::AccesAuJeu) {
            Routeur::rediriger('accueil');
        }
        $regles = Regles::toutes();
        // Étape absente ou invalide : première règle ; au-delà de la dernière : accueil.
        $etape = is_string($_GET['etape'] ?? null) && preg_match('/^[0-9]+$/D', $_GET['etape']) ? max(1, (int) $_GET['etape']) : 1;
        if ($etape > count($regles)) {
            Routeur::rediriger('accueil');
        }
        // Pas de barre basse sur cet écran : elle ouvrirait un chemin vers C1 sans terminer l'onboarding (cadrage § 8.5).
        Vue::rendre('b2-onboarding', 'Les règles du jeu', [
            'regle' => $regles[$etape - 1],
            'etape' => $etape,
            'total' => count($regles),
        ]);
    }

    // POST : « Passer » et le bouton du dernier écran ont le même effet. Le jeton CSRF a déjà été vérifié par le routeur.
    public function terminer(): void
    {
        $this->service()->terminerOnboarding($this->joueurConnecte(), $this->maintenant);
        Routeur::rediriger('accueil');
    }

    private function service(): AppairageService
    {
        return new AppairageService($this->pdo, new JoueurDepot($this->pdo), new DuoDepot($this->pdo), new TentativeDepot($this->pdo));
    }

    // Le joueur de la session, relu en base. Un compte supprimé entre-temps ramène à la connexion.
    private function joueurConnecte(): Joueur
    {
        $joueur = (new JoueurDepot($this->pdo))->trouverParId($_SESSION['joueur_id']);
        if ($joueur === null) {
            $_SESSION = [];
            Routeur::rediriger('connexion');
        }
        return $joueur;
    }
}
