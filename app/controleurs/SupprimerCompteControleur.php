<?php
// Contrôleur de la suppression de compte — F3 — parcours Profil, duo et système — lot 1 — A.

class SupprimerCompteControleur
{
    // Le routeur passe la connexion et l'heure ; le contrôleur construit lui-même ses dépôts et services (ADR-0001).
    // Clé de session qui dit au GET suivant que la suppression a été refusée ; lue une seule fois.
    private const REFUS = 'suppression_refusee';

    public function __construct(private PDO $pdo, private DateTimeImmutable $maintenant) {}

    // GET : l'avertissement d'irréversibilité, la phrase sur le duo s'il y en a un, la confirmation. Retour et annulation fixes vers F1, seul écran d'où F3 s'ouvre.
    public function afficher(): void
    {
        $joueur = Routeur::joueurConnecte($this->pdo);
        $refus = isset($_SESSION[self::REFUS]);
        unset($_SESSION[self::REFUS]);
        Vue::rendre('f3-supprimer-compte', 'Supprimer mon compte', [
            'partenaire' => AppairageService::construire($this->pdo)->pseudoPartenaire($joueur),
            'refus' => $refus,
        ]);
    }

    // POST : le jeton CSRF a déjà été vérifié par le routeur. L'identifiant de session sert à effacer les essais de la session, lu avant d'être régénéré.
    public function supprimer(): void
    {
        $supprime = AppairageService::construire($this->pdo)->supprimerCompte(Routeur::joueurConnecte($this->pdo), session_id(), $this->maintenant);
        // Duo reformé depuis un autre appareil entre les deux transactions : rien n'est supprimé, F3 se réaffiche et explique le refus.
        if (!$supprime) {
            $_SESSION[self::REFUS] = true;
            Routeur::rediriger('supprimer-compte');
        }
        // Comme à la déconnexion : session vidée et identifiant changé, l'ancien cookie ne désigne plus rien.
        $_SESSION = [];
        session_regenerate_id(true);
        Routeur::rediriger('bienvenue');
    }
}
