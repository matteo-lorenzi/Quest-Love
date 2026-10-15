<?php
// Contrôleur frontal — tous écrans — lot 1 — commun. Seul fichier joignable par URL ; annoté étape par étape avec les numéros du doc technique § 2.2.

// Étape 1 — amorce.
// Chargement automatique des classes, fuseau Europe/Paris, session, connexion à la base ($pdo) et jeton CSRF.
require __DIR__ . '/../noyau/amorce.php';

// Étape 1 (suite) — lecture unique de l'heure.
// C'est la seule lecture de l'horloge de l'application : routeur, contrôleurs et services reçoivent $maintenant.
// Ainsi midi, minuit et le changement d'heure se testent en passant l'heure voulue, sans attendre (§ 3.8).
$maintenant = new DateTimeImmutable('now');

// Le décalage de démonstration vient de config.php, jamais du navigateur ni de l'URL.
// Il permet de montrer en direct la bascule de 12 h ou l'expiration de minuit.
if (HORLOGE_DECALAGE !== '') {
    $maintenant = $maintenant->modify(HORLOGE_DECALAGE)
        ?: throw new RuntimeException('HORLOGE_DECALAGE invalide dans config/config.php.');
}

// Étapes 2 à 6 — le routeur résout l'écran, contrôle l'accès, vérifie le jeton et appelle le contrôleur.
Routeur::traiter($pdo, $maintenant);
