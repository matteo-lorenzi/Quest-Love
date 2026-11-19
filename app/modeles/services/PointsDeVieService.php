<?php
// Service des points de vie — D1 Défi expiré — parcours 2 (journée de jeu) — lot 1 — B. Seul à faire écrire la colonne pv (doc technique § 3.5).

class PointsDeVieService
{
    // Pénalité d'une expiration, tenue à jour depuis le barème du cadrage § 4.4 : seule définition du code.
    public const PENALITE = 1;

    public function __construct(private JoueurDepot $joueurs) {}

    // Appelé par l'expiration d'une mission seulement, dans la transaction du rattrapage, sur une ligne déjà verrouillée (§ 3.2) :
    // le pv est relu ici, et non pris sur un objet Joueur, pour que plusieurs expirations du même rattrapage se cumulent. Plancher à 0.
    // Rend le nouveau capital.
    public function perdre(int $joueurId): int
    {
        $pv = max(0, $this->joueurs->trouverParId($joueurId)->pv - self::PENALITE);
        $this->joueurs->changerPv($joueurId, $pv);
        return $pv;
    }
}
