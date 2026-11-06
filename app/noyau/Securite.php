<?php
// Sécurité de session et de réponse — tous écrans — lot 1 — commun : jeton CSRF, en-têtes HTTP de toute réponse, expiration de la session inactive. La limitation des tentatives demande du SQL : elle vit dans LimiteTentativesService.

class Securite
{
    // Inactivité tolérée d'un joueur connecté, en secondes (2 h).
    private const DELAI_INACTIVITE = 7200;

    // En-têtes de toute réponse, y compris les erreurs de l'amorce (F2 est irréversible : aucun site tiers ne doit pouvoir l'encadrer).
    // La CSP n'interdit que ce que l'application n'utilise pas (script, objet, ressource externe) : styles en ligne et formulaires restent permis.
    // $hsts : true en HTTPS hors poste de développement (estLocal). Sans sous-domaines : azrael.sha.univ-poitiers.fr est partagé avec d'autres comptes.
    public static function entetes(bool $hsts = false): array
    {
        $entetes = [
            'X-Frame-Options' => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'same-origin',
            'Content-Security-Policy' => "default-src 'self'; style-src 'self' 'unsafe-inline'; frame-ancestors 'none'; form-action 'self'; base-uri 'self'; object-src 'none'",
        ];
        if ($hsts) {
            $entetes['Strict-Transport-Security'] = 'max-age=31536000';
        }
        return $entetes;
    }

    // Poste de développement (WAMP en HTTP) : le cookie Secure et HSTS n'y valent pas, car le navigateur ne renverrait jamais le cookie.
    // Tout autre hôte est la production. TODO(simplif): la liste d'hôtes locaux est écrite en dur, à passer en configuration si un autre hôte de développement apparaît.
    public static function estLocal(string $hote): bool
    {
        return preg_match('/^(localhost|127\.0\.0\.1|\[::1\]|[a-z0-9-]+\.(local|localhost|test))(:\d+)?\z/i', $hote) === 1;
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
