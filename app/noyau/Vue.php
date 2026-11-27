<?php
// Rendu des vues et échappement de sortie — tous écrans — lot 1 — commun.

class Vue
{
    public static function rendre(string $vue, string $titre, array $donnees = []): void
    {
        // Chaque vue dispose du jeton pour ses formulaires, sans appeler Securite elle-même.
        $jeton = Securite::jeton();
        // EXTR_SKIP : une donnée ne peut pas écraser $vue, $titre ou $jeton.
        extract($donnees, EXTR_SKIP);
        $dossier = dirname(__DIR__) . '/vues';
        // Tampon de sortie : une exception en plein rendu n'a rien envoyé, le gestionnaire d'amorce.php (ou le catch d'ErreurHttp de index.php) vide ce tampon et pose encore le code d'erreur.
        ob_start();
        require "$dossier/gabarits/entete.php";
        require "$dossier/ecrans/$vue.php";
        require "$dossier/gabarits/pied.php";
    }

    // Ferme tous les tampons ouverts (celui de rendre, celui de php.ini) : une page d'erreur s'affiche seule, sans morceau de la page interrompue.
    public static function viderTampons(): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }
}

// Seule fonction d'échappement de l'application : tout texte libre passe par elle à la sortie (doc technique § 2.4).
function e(?string $texte): string
{
    return htmlspecialchars($texte ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
