<?php
// Sortie par erreur HTTP — tous écrans — lot 1 — commun. Levée par le routeur (404, 403, 405), attrapée par public/index.php seul, qui affiche G1 (ADR-0004).

class ErreurHttp extends Exception
{
    // $statut : le code HTTP (404, 403, 405). Un nom propre : Exception::$code est un entier non typé en lecture seule.
    public function __construct(public readonly int $statut)
    {
        parent::__construct("Erreur HTTP $statut.", $statut);
    }
}
