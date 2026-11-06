<?php
// Entité Joueur — A3 et tous les écrans connectés — lot 1 — A. Lecture seule : ne lit ni n'écrit en base (ADR-0001).

final readonly class Joueur
{
    public function __construct(
        public int $id,
        public string $identifiant,
        public string $pseudo,
        // Empreinte password_hash, jamais affichée : seul CompteService la lit.
        public string $mdpHash,
        // Code d'invitation et son échéance : nuls sans code, et une fois le duo formé (ADR-0002).
        public ?string $codeInvitation,
        public ?DateTimeImmutable $dateExpirationCode,
        public ?int $duoId,
        public int $pv,
        public DateTimeImmutable $dateCreation,
    ) {}
}
