<?php
// Niveau d'accès d'un écran dans la table du routeur — tous écrans — lot 1 — commun. Préconditions : doc technique § 3.6.

enum NiveauAcces
{
    // Ouvert à tous, connectés ou non.
    case Public;
    // Joueur connecté, duo ou non.
    case Connecte;
    // Joueur en duo, onboarding terminé ou non.
    case Duo;
    // En duo, onboarding terminé par les deux : écrans de jeu.
    case Jeu;
}
