<?php
// Entité Duo — B1 Salle d'attente et écrans de duo — lot 1 — A. Lecture seule : ne lit ni n'écrit en base (ADR-0001).

final readonly class Duo
{
    public function __construct(
        public int $id,
        public DateTimeImmutable $dateCreation,
        // Nulle tant que les deux joueurs n'ont pas fini l'onboarding ; instant de référence de « duo formé après 12 h » (cadrage § 4.2).
        public ?DateTimeImmutable $dateActivation,
    ) {}
}
