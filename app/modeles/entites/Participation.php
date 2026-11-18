<?php
// Entité Participation — C1 Accueil — Journée de jeu — lot 1 — B. Lecture seule : ne lit ni n'écrit en base (ADR-0001).

final readonly class Participation
{
    public function __construct(
        public int $id,
        public int $duoId,
        public int $joueurId,
        // Journée de jeu en Europe/Paris, à minuit (doc technique § 3.1).
        public DateTimeImmutable $dateJour,
        public Reponse $reponse,
        public DateTimeImmutable $dateReponse,
    ) {}
}
