<?php
// Jeton CSRF — tous écrans — lot 1 — commun. La limitation des tentatives demande du SQL : elle vit dans CompteService (#3).

class Securite
{
    // En-têtes de toute réponse, y compris les erreurs de l'amorce (F2 est irréversible : aucun site tiers ne doit pouvoir l'encadrer).
    // La CSP n'interdit que ce que l'application n'utilise pas (script, objet, ressource externe) : styles en ligne et formulaires restent permis.
    public static function entetes(): array
    {
        return [
            'X-Frame-Options' => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'same-origin',
            'Content-Security-Policy' => "default-src 'self'; style-src 'self' 'unsafe-inline'; frame-ancestors 'none'; form-action 'self'; base-uri 'self'; object-src 'none'",
        ];
    }

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
