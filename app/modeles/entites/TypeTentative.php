<?php
// Type d'une tentative échouée — A2 (inscription), A3 (connexion) et B1 (code d'invitation) — lot 1 — A. Valeurs de la colonne tentative.type.

enum TypeTentative: string
{
    case Connexion = 'connexion';
    case Code = 'code';
    case Inscription = 'inscription';
}
