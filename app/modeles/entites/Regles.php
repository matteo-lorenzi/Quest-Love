<?php
// Textes des règles du jeu — B2 Onboarding (pas à pas) et B3 Règles complètes (liste) — parcours 1 (appairage) — lot 1 — A. Source unique des textes.

class Regles
{
    // Une règle par entrée, dans l'ordre de l'onboarding (cadrage § 8.5). Tutoiement et voix de maître du jeu (§ 8.2).
    // Pas de gage au lot 1. Les textes sont à relire en S6 avec les autres textes d'interface.
    private const TEXTES = [
        ['titre' => 'Un défi pour ton partenaire', 'texte' => 'Chaque jour, trois défis s\'étalent en éventail. Touche une carte pour la découvrir, puis choisis celle que ton partenaire devra relever. Ton choix est définitif.'],
        ['titre' => 'Midi, le grand tournant', 'texte' => 'Réponds « oui » avant 12 h et choisis ton défi avant 12 h. Un « non », ou pas de réponse à midi, et la journée est blanche : aucun défi, aucune perte.'],
        ['titre' => 'Le cœur et minuit', 'texte' => 'Tu pars avec 5 points de vie. À minuit, un défi non réalisé s\'envole, et un cœur avec lui.'],
    ];

    // Écrans d'où B3 s'ouvre : liste fermée, le retour ne suit jamais un écran choisi librement dans l'adresse.
    // TODO(simplif): le profil (F1) n'existe pas, son lien « Règles complètes » (origine=profil) reste à poser avec lui.
    private const ORIGINES = ['onboarding', 'profil', 'accueil'];

    // Requête (sans « index.php? ») du retour de B3 vers son origine. Origine inconnue : accueil.
    // Avec B2 pour origine, l'étape (chiffres ou « attente ») est reprise ; étape invalide : B2 sans étape, donc première règle.
    public static function ecranDeRetour(mixed $origine, mixed $etape): string
    {
        if (!is_string($origine) || !in_array($origine, self::ORIGINES, true)) {
            return 'ecran=accueil';
        }
        if ($origine === 'onboarding' && is_string($etape) && preg_match('/^([0-9]+|attente)$/D', $etape)) {
            return "ecran=onboarding&etape=$etape";
        }
        return "ecran=$origine";
    }

    /** @return list<array{titre: string, texte: string}> */
    public static function toutes(): array
    {
        return self::TEXTES;
    }
}
