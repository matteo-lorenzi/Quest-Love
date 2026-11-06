<?php
// Code d'invitation reçu par un lien — A2, A3 et B1 — parcours 1 (appairage) — lot 1 — commun. Garde le code en session jusqu'à B1 (doc technique § 3.6).

class CodeRecu
{
    // Clé de session du code reçu par un lien avant authentification.
    private const CLE = 'code_conserve';
    // Un code fait 8 caractères : au-delà, ce n'est pas un code, et la session n'a pas à garder n'importe quoi.
    private const LONGUEUR_MAX = 64;

    // Appelée par A2 et A3 quand elles affichent le formulaire : garde le code d'un lien d'invitation et renvoie celui en session.
    // TODO(simplif): l'entrée du lien est ?code= sur A2 et A3, faute d'A1 · invitation, à déplacer quand A1 existe ; un lien vers B1 ouvert sans session perd son code (le routeur renvoie à la connexion).
    public static function conserver(): ?string
    {
        // Un ?code= vide n'est pas un code reçu : on ne garde rien.
        $recu = is_string($_GET['code'] ?? null) ? trim($_GET['code']) : '';
        if ($recu !== '') {
            $_SESSION[self::CLE] = mb_substr($recu, 0, self::LONGUEUR_MAX);
        }
        return $_SESSION[self::CLE] ?? null;
    }

    // Appelée par B1 : le code conservé ne sert qu'une fois, il est effacé en même temps qu'il est rendu.
    public static function reprendre(): ?string
    {
        $conserve = $_SESSION[self::CLE] ?? null;
        unset($_SESSION[self::CLE]);
        return $conserve;
    }
}
