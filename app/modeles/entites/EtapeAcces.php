<?php
// Étape d'accès d'un joueur — B2 Onboarding des règles — parcours 1 (appairage) — lot 1 — A. Règle unique du routeur, d'A3 et de B1 (doc technique § 3.6).

enum EtapeAcces
{
    // Pas de duo : B1.
    case SansDuo;
    // En duo, sa colonne onboarding_vu vaut 0 : B2.
    case OnboardingAFaire;
    // Il a fini, le partenaire non : variante d'attente de B2.
    case Attente;
    // Les deux ont fini : C1 et les écrans de jeu.
    case AccesAuJeu;
}
