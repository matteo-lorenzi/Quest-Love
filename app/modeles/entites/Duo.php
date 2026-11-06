<?php
// Entité Duo — B1 Salle d'attente et écrans de duo — lot 1 — A. Lecture seule : ne lit ni n'écrit en base (ADR-0001).

final readonly class Duo
{
    public function __construct(
        public int $id,
        public DateTimeImmutable $dateCreation,
    ) {}
}
