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
            'public, visiteur' => ['public', false, null, null],
            'connecte, visiteur' => ['connecte', false, null, 'connexion'],
            'duo, visiteur' => ['duo', false, null, 'connexion'],
            'jeu, visiteur' => ['jeu', false, null, 'connexion'],
            'connecte, sans duo' => ['connecte', true, EtapeAcces::SansDuo, null],
            'duo, sans duo' => ['duo', true, EtapeAcces::SansDuo, 'salle-attente'],
            'duo, compte disparu' => ['duo', true, null, 'salle-attente'],
            'duo, onboarding a faire' => ['duo', true, EtapeAcces::OnboardingAFaire, null],
            'duo, attente' => ['duo', true, EtapeAcces::Attente, null],
            'jeu, sans duo' => ['jeu', true, EtapeAcces::SansDuo, 'salle-attente'],
            'jeu, onboarding a faire' => ['jeu', true, EtapeAcces::OnboardingAFaire, 'onboarding'],
            'jeu, attente du partenaire' => ['jeu', true, EtapeAcces::Attente, 'onboarding'],
            'jeu, acces au jeu' => ['jeu', true, EtapeAcces::AccesAuJeu, null],
        ];
    }

    // Le verrou 'jeu' (§ 3.6) : un écran de jeu n'est ouvert qu'aux deux joueurs ayant fini l'onboarding, quel que soit le chemin d'arrivée.
    #[DataProvider('cas')]
    public function testRedirectionAcces(string $acces, bool $connecte, ?EtapeAcces $etape, ?string $attendu): void
    {
        $this->assertSame($attendu, Routeur::redirectionAcces($acces, $connecte, $etape));
    }
}
