<?php
// Contrôleur des événements — D1, D3, D5 (écran evenement) — parcours 2 (journée de jeu) — lot 1 — B.

class EvenementControleur
{
    // Le routeur passe la connexion et l'heure ; le contrôleur construit lui-même ses dépôts et services (ADR-0001).
    public function __construct(private PDO $pdo, private DateTimeImmutable $maintenant) {}

    // GET : dépile un événement de la file de session et l'affiche, comme un message lu une seule fois (§ 2.4).
    // Tant que la file n'est pas vide, le routeur ramène ici quel que soit l'écran visé par l'action.
    // File vide (adresse ouverte à la main, onglet rechargé) : l'écran de l'étape d'accès du joueur.
    public function afficher(): void
    {
        $file = $_SESSION['evenements'] ?? [];
        $valeur = array_shift($file);
        $_SESSION['evenements'] = $file;
        $evenement = is_string($valeur) ? Evenement::tryFrom($valeur) : null;
        if ($evenement === null) {
            $joueur = Routeur::joueurConnecte($this->pdo);
            Routeur::rediriger(Routeur::ecranDeLEtape(AppairageService::construire($this->pdo)->etapeAcces($joueur)));
        }
        // Titre, texte, écran de l'action et son libellé. D3 : le joueur est sans duo, l'action mène à la salle d'attente.
        [$titre, $texte, $ecranAction, $libelleAction] = match ($evenement) {
            Evenement::DuoDissous => [
                'Duo dissous',
                '[TEXTE À RÉDIGER : ton partenaire a quitté le duo ; tu repars à 5 PV et peux former un nouveau duo]',
                'salle-attente',
                '[TEXTE À RÉDIGER : trouver un nouveau partenaire]',
            ],
        };
        Vue::rendre('d-evenement', $titre, [
            'texte' => $texte,
            'ecranAction' => $ecranAction,
            'libelleAction' => $libelleAction,
        ]);
    }
}
