<?php
// Événement à montrer à l'écran evenement — D1, D3, D5 — parcours 2 (journée de jeu) — lot 1 — B. Valeurs rangées dans la file de session.

enum Evenement: string
{
    // D3 : le partenaire a dissous le duo.
    case DuoDissous = 'duo-dissous';
    // D1 : un défi reçu a expiré à minuit sans être validé, 1 PV perdu. Variante quand le destinataire ne l'avait jamais ouvert (date_decouverte nulle).
    case DefiExpire = 'defi-expire';
    case DefiExpireJamaisDecouvert = 'defi-expire-jamais-decouvert';
}
