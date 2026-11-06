<?php
// Raison du refus d'un code d'invitation — B1 Salle d'attente — lot 1 — A. Messages : doc technique § 3.6, cadrage § 6.6.

enum RefusCode
{
    // Texte sans forme de code : refusé sans toucher la base, ne compte pas.
    case Format;
    // Le joueur est déjà en duo (requête forgée : la vue ne lui montre pas la saisie), ne compte pas.
    case DejaEnDuo;
    case PropreCode;
    case Expire;
    // Faux, régénéré, ou dont le propriétaire est déjà en duo : non distingués (ADR-0002).
    case PasValable;
}
