<?php
// Contrôleur d'erreur — G1 — lot 1 — commun, version minimale pour le routeur ; la suite (illustration) revient au périmètre B.

class ErreurControleur
{
    private const TEXTES = [
        403 => ['Action refusée', '[TEXTE À RÉDIGER : action refusée]'],
        404 => ['Page introuvable', '[TEXTE À RÉDIGER : page introuvable]'],
        405 => ['Action impossible', '[TEXTE À RÉDIGER : action impossible sur cette page]'],
        500 => ['Un souci de notre côté', '[TEXTE À RÉDIGER : erreur interne, réessayer plus tard]'],
    ];

    public function afficher(int $code = 404): void
    {
        http_response_code($code);
        [$titre, $texte] = self::TEXTES[$code];
        Vue::rendre('g1-erreur', $titre, ['texte' => $texte]);
    }
}
