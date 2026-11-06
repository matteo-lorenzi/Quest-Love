<?php
// Contrôleur de la dissolution du duo — F2 — parcours 1 (appairage) — lot 1 — A.

class QuitterDuoControleur
{
    // Écrans d'où F2 s'ouvre : liste fermée, l'annulation ne suit jamais un écran choisi librement dans l'adresse.
    // TODO(simplif): le profil (F1) n'existe pas, son origine (profil) s'ajoutera ici avec lui.
    private const ORIGINES = ['salle-attente', 'onboarding'];

    // Le routeur passe la connexion et l'heure ; le contrôleur construit lui-même ce dont il a besoin (ADR-0001).
    public function __construct(private PDO $pdo, private DateTimeImmutable $maintenant) {}

    // GET : l'avertissement d'irréversibilité, la confirmation et l'annulation vers l'écran d'origine.
    public function afficher(): void
    {
        $joueur = $this->joueurConnecte();
        // Origine absente ou inconnue : l'écran de l'étape d'accès du joueur (relue en base).
        // TODO(simplif): pour un joueur en accès au jeu l'origine vaut « accueil », absent de la table du routeur : Annuler et ← mènent à G1, à corriger quand C1 existe.
        $origine = $_GET['origine'] ?? null;
        if (!is_string($origine) || !in_array($origine, self::ORIGINES, true)) {
            $origine = Routeur::ecranDeLEtape(AppairageService::construire($this->pdo)->etapeAcces($joueur));
        }
        Vue::rendre('f2-quitter-duo', 'Quitter mon duo', [
            'retour' => $origine,
            'partenaire' => (new JoueurDepot($this->pdo))->trouverPartenaire($joueur)?->pseudo,
        ]);
    }

    // POST : le jeton CSRF a déjà été vérifié par le routeur. Le résultat (dissous ou déjà dissous) ne change pas l'écran suivant.
    public function dissoudre(): void
    {
        AppairageService::construire($this->pdo)->dissoudre($this->joueurConnecte());
        Routeur::rediriger('salle-attente');
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
