<?php
// Statut d'une mission — C1 à C3 et D1 — Journée de jeu — lot 1 — B. Valeurs de la colonne mission.statut ; transitions : modèle de données § 3.

enum StatutMission: string
{
    case AttribueNonRevele = 'attribue_non_revele';
    case EnCours = 'en_cours';
    case Valide = 'valide';
    case Expire = 'expire';
    // Journée blanche : midi passé sans le choix de l'autre joueur. Terminal, sans effet sur les points de vie.
    case Annule = 'annule';
}
