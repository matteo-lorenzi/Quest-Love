<?php
// Écran d'où F2 Dissolution du duo s'ouvre — parcours 1 (appairage) — lot 1 — A. Liste fermée de cas ; le paramètre d'adresse de chaque cas est posé par QuitterDuoControleur::parametre.

enum OrigineQuitterDuo
{
    case SalleAttente;
    case Onboarding;
    case Accueil;
    case Profil;
}
