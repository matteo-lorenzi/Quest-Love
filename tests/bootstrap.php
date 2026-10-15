<?php
// Amorce des tests PHPUnit — aucun écran — lot 1 — commun (doc technique § 6.4).

// Réinitialise la base locale avec les mêmes fichiers et les mêmes garde-fous (ligne de commande, hôte local) que le § 6.3.
// Le script charge aussi config.php et la classe Bdd.
require __DIR__ . '/../sql/reinitialiser.php';

date_default_timezone_set('Europe/Paris');

// Même chargement automatique que l'application, limité à la couche modèle testée.
spl_autoload_register(function (string $classe): void {
    foreach (['modeles/entites', 'modeles/depots', 'modeles/services'] as $dossier) {
        $fichier = __DIR__ . "/../app/$dossier/$classe.php";
        if (is_file($fichier)) {
            require $fichier;
            return;
        }
    }
});
