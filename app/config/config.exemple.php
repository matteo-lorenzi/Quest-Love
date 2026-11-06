<?php
// Configuration d'exemple — aucun écran — lot 1 — commun. À copier en config.php, jamais versionné (doc technique § 6.1).

// Valeurs de WAMP (développement). Sur Azrael (doc technique § 6.1) : hôte '127.0.0.1;port=3307' (le port se glisse dans le DSN),
// base 'weuxNN_XXX_quest_love' créée dans phpMyAdmin, et le compte 'weuxNN_XXX' fourni par l'école, seul compte disponible.
// Hors Azrael, ne pas garder `root` sans mot de passe : créer un utilisateur dédié aux seuls droits SELECT, INSERT, UPDATE et DELETE
// sur la base (pas de DROP ni de CREATE ; le schéma se charge avec un compte plus fort).
// `sql/demonstration.sql` et ses comptes de démonstration ne se chargent jamais sur Azrael.
const BDD_HOTE = 'localhost';
const BDD_NOM = 'quest_love';
const BDD_UTILISATEUR = 'root';
const BDD_MOT_DE_PASSE = '';

// Décalage de démonstration appliqué à l'horloge par index.php, par exemple '+11 hours 55 minutes' (doc technique § 3.8).
// Vide en fonctionnement normal.
const HORLOGE_DECALAGE = '';
