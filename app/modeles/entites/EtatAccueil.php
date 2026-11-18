<?php
// État de l'accueil — C1 Accueil — parcours 2 (journée de jeu) — lot 1 — B. Déduit par JourneeService, sans écriture ; chaque cas est une variante du socle C1.

enum EtatAccueil
{
    // État 1 : « On joue aujourd'hui ? », le joueur n'a pas encore répondu.
    case Question;
    // État 2 : il a dit oui, son partenaire n'a pas encore répondu.
    case AttentePartenaire;
    // État 3 (« À toi de piocher ») : les deux ont dit oui, le joueur choisit le défi de son partenaire (C2).
    case ATirer;
    // État 4 : défi envoyé, en attente de révélation ; dure jusqu'à la révélation (#59).
    case DefiEnvoye;
    // Journée blanche : l'un des deux a dit non, ou n'avait pas répondu à 12 h. Déduite, jamais écrite.
    case JourneeBlanche;
    // Duo activé à 12 h ou après : pas de jeu aujourd'hui, le premier jour de jeu est demain (jusqu'à minuit).
    case PremierJourDemain;
}
