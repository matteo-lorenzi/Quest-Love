<?php
// Tests de ReglesControleur::origine et ::requeteDeRetour — B3 Règles du jeu — parcours 1 (appairage) — lot 1 — A.

use PHPUnit\Framework\TestCase;

// Le contrôleur n'est pas dans l'autochargement des tests (couche modèle seulement) ; son fichier n'a aucune dépendance au chargement.
require_once __DIR__ . '/../app/controleurs/ReglesControleur.php';

class ReglesTest extends TestCase
{
    // Le contrôleur valide l'origine contre la liste fermée.
    public function testLOrigineEstValideeContreLaListeFermee(): void
    {
        foreach (['onboarding', 'profil', 'accueil'] as $origine) {
            $this->assertSame($origine, ReglesControleur::origine($origine));
        }
        $this->assertSame('accueil', ReglesControleur::origine('evil'));
        $this->assertSame('accueil', ReglesControleur::origine(null));
        $this->assertSame('accueil', ReglesControleur::origine(['onboarding']));
    }

    // Les textes restent la source unique de B2 et de B3.
    public function testLesTroisReglesSontLues(): void
    {
        $this->assertCount(3, Regles::toutes());
    }

    // Retour de B3 vers B2 : même étape (parcours Appairage, B2 étape 2 → B3 → B2 étape 2).
    public function testLeRetourVersOnboardingGardeLEtape(): void
    {
        $this->assertSame('ecran=onboarding&etape=2', ReglesControleur::requeteDeRetour('onboarding', '2'));
        $this->assertSame('ecran=onboarding&etape=attente', ReglesControleur::requeteDeRetour('onboarding', 'attente'));
    }

    // Étape absente ou invalide : on rend l'écran d'onboarding sans étape (B2 affiche alors la première règle).
    public function testUneEtapeInvalideRevientAOnboardingSansEtape(): void
    {
        $this->assertSame('ecran=onboarding', ReglesControleur::requeteDeRetour('onboarding', null));
        $this->assertSame('ecran=onboarding', ReglesControleur::requeteDeRetour('onboarding', '2&ecran=x'));
    }

    // Retour vers le profil (F1) et l'accueil (C1) : l'étape ne sert qu'à l'onboarding.
    public function testLeRetourVersProfilEtAccueil(): void
    {
        $this->assertSame('ecran=profil', ReglesControleur::requeteDeRetour('profil', '3'));
        $this->assertSame('ecran=accueil', ReglesControleur::requeteDeRetour('accueil', null));
    }

    // Liste fermée : toute autre valeur renvoie à l'accueil, jamais un écran choisi par l'adresse.
    public function testUneOrigineInconnueRenvoieALAccueil(): void
    {
        $this->assertSame('ecran=accueil', ReglesControleur::requeteDeRetour('evil', null));
        $this->assertSame('ecran=accueil', ReglesControleur::requeteDeRetour(null, null));
        $this->assertSame('ecran=accueil', ReglesControleur::requeteDeRetour(['onboarding'], null));
    }
}
