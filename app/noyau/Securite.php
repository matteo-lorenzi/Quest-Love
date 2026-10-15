<?php
// Jeton CSRF — tous écrans — lot 1 — commun. La limitation des tentatives demande du SQL : elle vit dans CompteService (#3).

class Securite
{
    public static function jeton(): string
    {
        if (empty($_SESSION['jeton'])) {
            $_SESSION['jeton'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['jeton'];
    }

    // mixed : la valeur vient du formulaire et peut être absente ou un tableau.
    public static function verifier(mixed $jeton): bool
    {
        // hash_equals compare en temps constant : la durée de la comparaison ne révèle rien du jeton.
        return is_string($jeton) && hash_equals(self::jeton(), $jeton);
    }
}
