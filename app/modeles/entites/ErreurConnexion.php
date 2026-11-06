<?php
// Code d'erreur de la connexion — A3 Connexion — lot 1 — A. Textes dans la vue a3-connexion.

enum ErreurConnexion
{
    // Champ vide : erreur de saisie, pas un essai de mot de passe.
    case Vide;
    // Identifiant ou mot de passe faux : un seul message pour les deux, pour ne pas révéler quel champ est faux.
    case Incorrect;
}
