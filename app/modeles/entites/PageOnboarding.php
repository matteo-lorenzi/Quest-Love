<?php
// Page d'onboarding à montrer — B2 Onboarding des règles — parcours 1 (appairage) — lot 1 — A. Lecture seule : ce que B2 doit faire à l'affichage (ADR-0001).

final readonly class PageOnboarding
{
    public function __construct(
        // Non nulle : B2 renvoie vers l'écran de cette étape d'accès (le contrôleur traduit en adresse).
        public ?EtapeAcces $redirection,
        // Variante d'attente : le joueur a fini avant son partenaire.
        public bool $attente,
        // Pseudo du partenaire pour la variante d'attente ; nul sinon.
        public ?string $partenaire,
        // Règle montrée, son rang (à partir de 1) et le nombre de règles ; nuls hors page de règle.
        public ?array $regle,
        public ?int $etape,
        public ?int $total,
    ) {}
}
