<?php
// Contrôleur des règles complètes — B3 — parcours 1 (appairage) — lot 1 — A.

class ReglesControleur
{
    // Le routeur passe la connexion et l'heure ; B3 n'a besoin ni de l'un ni de l'autre (ADR-0001).
    public function __construct(private PDO $pdo, private DateTimeImmutable $maintenant) {}

    // GET : toutes les règles en liste, lues à la même source que B2. L'origine et l'étape ne servent qu'au lien de retour.
    public function afficher(): void
    {
        Vue::rendre('b3-regles', 'Les règles du jeu', [
            'regles' => Regles::toutes(),
            'retour' => Regles::ecranDeRetour($_GET['origine'] ?? null, $_GET['etape'] ?? null),
        ]);
    }
}
