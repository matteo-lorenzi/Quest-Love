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
    // État 5 : les deux ont choisi, la mission du joueur est révélée ; l'accueil l'annonce sans la dévoiler (la découverte est sur C3).
    case DefiRevele;
    // État 6 : défi reçu découvert sur C3 et encore en cours ; lien vers C3, statut du défi envoyé.
    case DefiEnCours;
    // Variante de l'état 6 (ex-état 7) : défi reçu réalisé, statut du défi envoyé (cadrage § 4.6).
    case DefiRealise;
    // Journée blanche : l'un des deux a dit non, ou n'avait pas répondu à 12 h. Déduite, jamais écrite.
    case JourneeBlanche;
    // Journée blanche « l'un de vous n'a pas choisi » : les deux oui, mais 12 h passée sans les deux choix (mission annulée par le rattrapage). Pour les deux joueurs.
    case JourneeBlancheSansChoix;
    // Duo activé à 12 h ou après : pas de jeu aujourd'hui, le premier jour de jeu est demain (jusqu'à minuit).
    case PremierJourDemain;
}
