<?php
// Aiguillage de la salle d'attente — B1 Salle d'attente — parcours 1 (appairage) — lot 1 — A. Lecture seule : ce que B1 doit faire à l'affichage (ADR-0001).

final readonly class AiguillageSalleAttente
{
    public function __construct(
        // Non nulle : B1 renvoie vers l'écran de cette étape d'accès (le contrôleur traduit en adresse, le modèle ne connaît pas les écrans).
        public ?EtapeAcces $redirection,
        // Vrai quand le duo s'est formé entre la lecture du joueur et la génération du code : B1 se recharge (le contrôleur redirige vers B1).
        public bool $recharger,
        // Variante « déjà en duo » : le lien d'invitation d'un joueur qui a déjà un duo montre pourquoi il ne peut pas rejoindre.
        public bool $dejaEnDuo,
        // Pseudo du partenaire pour la variante ; nul hors variante.
        public ?string $partenaire,
        // Le joueur à jour, avec son code d'invitation ; nul quand B1 redirige ou montre la variante.
        public ?Joueur $joueur,
    ) {}
}
