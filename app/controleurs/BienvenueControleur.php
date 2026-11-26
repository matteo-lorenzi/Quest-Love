<?php
// Contrôleur de l'écran d'entrée — A1 — parcours 1 (appairage) — lot 1 — A.

class BienvenueControleur
{
    // Le routeur passe la connexion et l'heure ; A1 lit la base seulement pour renvoyer un joueur connecté (ADR-0001).
    public function __construct(private PDO $pdo, private DateTimeImmutable $maintenant) {}

    // GET seulement : A1 n'écrit rien et n'a pas de formulaire.
    public function afficher(): void
    {
        // Le code d'un lien d'invitation est gardé d'abord, avant toute redirection : un joueur connecté sans duo le retrouve prérempli en B1 (cadrage § 6.3).
        // Il n'est pas vérifié ici : la vérification est en B1 (cadrage § 6.6).
        $codeRecu = CodeRecu::conserver();

        // Un joueur connecté ne voit jamais A1 : il est renvoyé vers l'écran de son étape d'accès, par la règle unique du routeur.
        if (isset($_SESSION['joueur_id'])) {
            $etape = AppairageService::construire($this->pdo)->etapeAcces(Routeur::joueurConnecte($this->pdo));
            Routeur::rediriger(Routeur::ecranDeLEtape($etape));
        }

        Vue::rendre('a1-bienvenue', 'Bienvenue', ['codeRecu' => $codeRecu]);
    }
}
