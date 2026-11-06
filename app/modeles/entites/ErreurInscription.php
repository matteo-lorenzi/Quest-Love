<?php
// Code d'erreur d'un champ de l'inscription — A2 Inscription — lot 1 — A. Règles : cadrage § 6.6 ; textes dans la vue a2-inscription.

enum ErreurInscription
{
    case Longueur;
    case Caracteres;
    case Pris;
    case Vide;
    case Controle;
    // Texte qui n'est pas de l'UTF-8 valide : requête forgée.
    case Encodage;
    case Majuscule;
    case Minuscule;
    case Chiffre;
    case Special;
    case EspaceBord;
    case Courant;
    case ContientIdentifiant;
    case ContientPseudo;
}
