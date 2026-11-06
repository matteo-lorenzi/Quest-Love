<?php
// Jeton CSRF — tous écrans — lot 1 — commun. La limitation des tentatives demande du SQL : elle vit dans CompteService (#3).

class Securite
{
    // Inactivité tolérée d'un joueur connecté, en secondes (2 h).
    private const DELAI_INACTIVITE = 7200;

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

    // À la connexion et à l'inscription : un jeton connu avant l'authentification (page vue, fixation) ne sert plus après.
    public static function renouvelerJeton(): void
    {
        $_SESSION['jeton'] = bin2hex(random_bytes(32));
    }

    // Un joueur connecté sans requête depuis plus de 2 h perd sa session ; true si elle a expiré. $maintenant : horodatage Unix (§ 2.4, l'horloge est lue dans index.php).
    // Un visiteur non connecté n'expire pas : sa session ne protège rien.
    public static function expirerSiInactive(int $maintenant): bool
    {
        $derniere = $_SESSION['activite'] ?? $maintenant;
        $expiree = isset($_SESSION['joueur_id']) && $maintenant - $derniere > self::DELAI_INACTIVITE;
        if ($expiree) {
            $_SESSION = [];
            // Hors requête web (tests) il n'y a pas de session PHP à renouveler.
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_regenerate_id(true);
            }
        } else {
            $_SESSION['activite'] = $maintenant;
        }
        return $expiree;
    }

    // mixed : la valeur vient du formulaire et peut être absente ou un tableau.
    public static function verifier(mixed $jeton): bool
    {
        // hash_equals compare en temps constant : la durée de la comparaison ne révèle rien du jeton.
        return is_string($jeton) && hash_equals(self::jeton(), $jeton);
    }
}
