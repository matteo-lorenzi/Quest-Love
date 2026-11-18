<?php
// État de l'accueil — C1 Accueil — parcours 2 (journée de jeu) — lot 1 — B. Déduit par JourneeService, sans écriture ; chaque cas est une variante du socle C1.

enum EtatAccueil
{
    // État 1 : « On joue aujourd'hui ? », le joueur n'a pas encore répondu.
    case Question;
    // État 2 : il a dit oui, son partenaire n'a pas encore répondu.
    case AttentePartenaire;
    // Les deux ont dit oui : la journée est jouée. Les états 3 et 4 (tirage, #58) la détailleront.
    case LesDeuxOntDitOui;
    // Cas « aucun jeu » : l'un des deux a dit non.
    // TODO(simplif): ni bascule de 12 h ni activation du duo prises en compte, à compléter par #69 (journée blanche, premier jour demain).
    case AucunJeu;
}
