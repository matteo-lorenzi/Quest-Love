<?php
// Écran d'où F2 Dissolution du duo s'ouvre — parcours 1 (appairage) — lot 1 — A. La valeur est le nom de l'écran dans l'adresse.
// TODO(simplif): le profil (F1) n'existe pas, son origine s'ajoutera ici avec lui.

enum OrigineQuitterDuo: string
{
    case SalleAttente = 'salle-attente';
    case Onboarding = 'onboarding';
}
