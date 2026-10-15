<?php
// Type d'une tentative échouée — A3 (connexion) et B1 (code d'invitation) — lot 1 — A. Valeurs de la colonne tentative.type.

enum TypeTentative: string
{
    case Connexion = 'connexion';
    case Code = 'code';
}
