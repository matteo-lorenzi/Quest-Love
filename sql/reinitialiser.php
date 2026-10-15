<?php
// Reconstruction de la base locale — aucun écran — lot 1 — commun. Usage : php sql/reinitialiser.php (doc technique § 6.3).

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Ligne de commande uniquement.\n");
}

require __DIR__ . '/../app/config/config.php';

// Garde-fou : une erreur de configuration ne doit jamais pouvoir effacer la base d'Azrael.
if (!in_array(BDD_HOTE, ['localhost', '127.0.0.1'], true)) {
    fwrite(STDERR, 'Refus : l\'hôte ' . BDD_HOTE . " n'est pas local.\n");
    exit(1);
}

require __DIR__ . '/../app/noyau/Bdd.php';
$pdo = Bdd::connexion();
// Un fichier .sql enchaîne plusieurs instructions, ce que seul le mode émulé accepte ; ici aucune donnée ne vient d'un joueur.
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

foreach (['schema.sql', 'defis.sql', 'demonstration.sql'] as $fichier) {
    $chemin = __DIR__ . '/' . $fichier;
    if (!is_file($chemin)) {
        echo "$fichier absent, ignoré.\n";
        continue;
    }
    // Parcourir chaque résultat fait remonter l'erreur d'une instruction tardive, qu'exec() laisserait passer en silence.
    $resultat = $pdo->query(file_get_contents($chemin));
    while ($resultat->nextRowset()) {
    }
    echo "$fichier chargé.\n";
}
