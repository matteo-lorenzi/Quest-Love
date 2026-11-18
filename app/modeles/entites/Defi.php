<?php
// Entité Défi — C2 Tirage et C3 Défi reçu — Journée de jeu — lot 1 — B. Lecture seule : ne lit ni n'écrit en base (ADR-0001).

final readonly class Defi
{
    public function __construct(
        public int $id,
        public string $titre,
        public string $description,
        public int $categorieId,
        // Jointe à la lecture : les écrans l'affichent sur la carte du défi.
        public string $libelleCategorie,
    ) {}
}
