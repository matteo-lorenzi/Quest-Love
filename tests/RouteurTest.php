<?php
// Tests de Routeur::redirectionAcces — préconditions d'accès (étape 5, doc technique § 3.6) — parcours 1 (appairage) — lot 1 — commun.

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

// Le routeur n'est pas dans l'autochargement des tests (couche modèle seulement) ; son fichier n'a aucune dépendance au chargement.
require_once __DIR__ . '/../app/noyau/Routeur.php';

class RouteurTest extends TestCase
{
    public static function cas(): array
    {
        return [
            'public, visiteur' => [NiveauAcces::Public, false, null, null],
            'connecte, visiteur' => [NiveauAcces::Connecte, false, null, 'connexion'],
            'duo, visiteur' => [NiveauAcces::Duo, false, null, 'connexion'],
            'jeu, visiteur' => [NiveauAcces::Jeu, false, null, 'connexion'],
            'connecte, sans duo' => [NiveauAcces::Connecte, true, EtapeAcces::SansDuo, null],
            'duo, sans duo' => [NiveauAcces::Duo, true, EtapeAcces::SansDuo, 'salle-attente'],
            'duo, compte disparu' => [NiveauAcces::Duo, true, null, 'salle-attente'],
            'duo, onboarding a faire' => [NiveauAcces::Duo, true, EtapeAcces::OnboardingAFaire, null],
            'duo, attente' => [NiveauAcces::Duo, true, EtapeAcces::Attente, null],
            'jeu, sans duo' => [NiveauAcces::Jeu, true, EtapeAcces::SansDuo, 'salle-attente'],
            'jeu, onboarding a faire' => [NiveauAcces::Jeu, true, EtapeAcces::OnboardingAFaire, 'onboarding'],
            'jeu, attente du partenaire' => [NiveauAcces::Jeu, true, EtapeAcces::Attente, 'onboarding'],
            'jeu, acces au jeu' => [NiveauAcces::Jeu, true, EtapeAcces::AccesAuJeu, null],
        ];
    }

    // Le verrou 'jeu' (§ 3.6) : un écran de jeu n'est ouvert qu'aux deux joueurs ayant fini l'onboarding, quel que soit le chemin d'arrivée.
    #[DataProvider('cas')]
    public function testRedirectionAcces(NiveauAcces $acces, bool $connecte, ?EtapeAcces $etape, ?string $attendu): void
    {
        $this->assertSame($attendu, Routeur::redirectionAcces($acces, $connecte, $etape));
    }
}
