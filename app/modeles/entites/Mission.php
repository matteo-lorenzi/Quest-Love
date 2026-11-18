<?php
// Entité Mission — C1 à C3 et D1 — Journée de jeu — lot 1 — B. Lecture seule : ne lit ni n'écrit en base (ADR-0001).

final readonly class Mission
{
    public function __construct(
        public int $id,
        public int $duoId,
        public int $tirageId,
        public int $defiId,
        public int $emetteurId,
        public int $destinataireId,
        public DateTimeImmutable $dateAttribution,
        public ?DateTimeImmutable $dateRevelation,
        // Nulle à l'expiration : variante « jamais découvert » de D1.
        public ?DateTimeImmutable $dateDecouverte,
        // Minuit du jour d'attribution (modèle de données § 2).
        public DateTimeImmutable $dateLimite,
        public StatutMission $statut,
        public ?DateTimeImmutable $dateValidation,
    ) {}
}
