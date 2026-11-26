<?php
// Sortie par redirection — tous écrans — lot 1 — commun. Levée par Routeur::rediriger, attrapée par public/index.php seul (ADR-0004).

class Redirection extends Exception
{
    // $ecran : l'écran cible, tel qu'il s'écrit dans ?ecran= ; l'adresse complète se construit dans index.php.
    public function __construct(public readonly string $ecran)
    {
        parent::__construct("Redirection vers l'écran « $ecran ».");
    }
}
