<?php
// Amorce de toute requête — tous écrans — lot 1 — commun. Incluse par public/index.php seulement.

$racine = dirname(__DIR__);

// Aucun message technique (utilisateur de la base, chemin, trace) n'atteint le navigateur, quel que soit php.ini : tout va au journal.
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// En-têtes de sécurité posés avant toute sortie, donc aussi sur le message de configuration absente ci-dessous.
require __DIR__ . '/Securite.php';
foreach (Securite::entetes() as $nom => $valeur) {
    header("$nom: $valeur");
}

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

// Mode strict et cookies seulement : la session refuse un identifiant qu'elle n'a pas émis, et aucun identifiant ne passe par l'URL.
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
// Le ramasse-miettes de PHP supprime les sessions après 24 min par défaut : sans cela, « une session récente continue » ne tiendrait pas jusqu'à 2 h (Securite::DELAI_INACTIVITE).
ini_set('session.gc_maxlifetime', '7200');
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    // Secure seulement en HTTPS : WAMP sert en HTTP, où un cookie Secure ne serait jamais renvoyé et la session perdue.
    // TODO(simplif): le § 3.7 veut Secure sans condition, à forcer une fois le HTTPS d'Azrael confirmé.
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_start();

// Exception non attrapée (base injoignable, bogue de contrôleur) : détail au journal, G1 en version 500 pour le joueur.
set_exception_handler(function (Throwable $exception): void {
    error_log((string) $exception);
    try {
        (new ErreurControleur())->afficher(500);
    } catch (Throwable) {
        http_response_code(500);
        echo 'Erreur interne.';
    }
});

$pdo = Bdd::connexion();
Securite::jeton();
