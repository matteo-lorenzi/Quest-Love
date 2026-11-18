<?php
// Entité Tirage — C2 Tirage — Journée de jeu — lot 1 — B. Lecture seule : ne lit ni n'écrit en base (ADR-0001).

final readonly class Tirage
{
    public function __construct(
        public int $id,
        public int $duoId,
        public int $emetteurId,
        public int $destinataireId,
        public DateTimeImmutable $dateJour,
        /** @var int[] Numéros des trois défis proposés (table tirage_proposition, ADR-0003). */
        public array $defiIds,
    ) {}
}
