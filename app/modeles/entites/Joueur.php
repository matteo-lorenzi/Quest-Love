<?php
// Entité Joueur — A3 et tous les écrans connectés — lot 1 — A. Lecture seule : ne lit ni n'écrit en base (ADR-0001).

final readonly class Joueur
{
    // Points de vie au départ et après une dissolution (F2). Seule définition du code ; PointsDeVieService la reprendra à sa création. Le défaut de la colonne pv (sql/schema.sql) reste à part.
    public const PV_DEPART = 5;

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
        // Onboarding (B2) terminé ou passé avec le duo actuel ; remis à faux à chaque formation de duo.
        public bool $onboardingVu,
        // Posé sur le partenaire quand l'autre dissout le duo ; D3 le lit puis le remet à faux.
        public bool $dissolutionASignaler,
        public DateTimeImmutable $dateCreation,
    ) {}
}
