<?php
// Connexion à la base — tous écrans — lot 1 — commun.

class Bdd
{
    public static function connexion(): PDO
    {
        return new PDO(
            'mysql:host=' . BDD_HOTE . ';dbname=' . BDD_NOM . ';charset=utf8mb4',
            BDD_UTILISATEUR,
            BDD_MOT_DE_PASSE,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // Vraies requêtes préparées côté serveur : PDO n'interpole jamais une valeur dans le SQL.
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }
}
