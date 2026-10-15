<?php
// Amorce de toute requête — tous écrans — lot 1 — commun. Incluse par public/index.php seulement.

$racine = dirname(__DIR__);

// config.php n'est pas versionné : à la première installation, un message clair vaut mieux qu'une erreur fatale.
if (!is_file($racine . '/config/config.php')) {
    http_response_code(500);
    exit('Configuration absente : copier config/config.exemple.php en config/config.php.');
}
require $racine . '/config/config.php';
// Inclus à la main : Vue.php définit aussi la fonction e(), qu'un chargeur de classes ne sait pas trouver.
require $racine . '/noyau/Vue.php';

spl_autoload_register(function (string $classe) use ($racine): void {
    foreach (['noyau', 'controleurs', 'modeles/entites', 'modeles/depots', 'modeles/services'] as $dossier) {
        $fichier = "$racine/$dossier/$classe.php";
        if (is_file($fichier)) {
            require $fichier;
            return;
        }
    }
});

date_default_timezone_set('Europe/Paris');

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    // Secure seulement en HTTPS : WAMP sert en HTTP, où un cookie Secure ne serait jamais renvoyé et la session perdue.
    // TODO(simplif): le § 3.7 veut Secure sans condition, à forcer une fois le HTTPS d'Azrael confirmé.
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_start();

$pdo = Bdd::connexion();
Securite::jeton();
