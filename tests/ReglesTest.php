<?php
// Tests de Regles::ecranDeRetour — B3 Règles du jeu — parcours 1 (appairage) — lot 1 — A.

use PHPUnit\Framework\TestCase;

class ReglesTest extends TestCase
{
    // Retour de B3 vers B2 : même étape (parcours Appairage, B2 étape 2 → B3 → B2 étape 2).
    public function testLeRetourVersOnboardingGardeLEtape(): void
    {
        $this->assertSame('ecran=onboarding&etape=2', Regles::ecranDeRetour('onboarding', '2'));
        $this->assertSame('ecran=onboarding&etape=attente', Regles::ecranDeRetour('onboarding', 'attente'));
    }

    // Étape absente ou invalide : on rend l'écran d'onboarding sans étape (B2 affiche alors la première règle).
    public function testUneEtapeInvalideRevientAOnboardingSansEtape(): void
    {
        $this->assertSame('ecran=onboarding', Regles::ecranDeRetour('onboarding', null));
        $this->assertSame('ecran=onboarding', Regles::ecranDeRetour('onboarding', '2&ecran=x'));
    }

    // Retour vers le profil (F1) et l'accueil (C1) : l'étape ne sert qu'à l'onboarding.
    public function testLeRetourVersProfilEtAccueil(): void
    {
        $this->assertSame('ecran=profil', Regles::ecranDeRetour('profil', '3'));
        $this->assertSame('ecran=accueil', Regles::ecranDeRetour('accueil', null));
    }

    // Liste fermée : toute autre valeur renvoie à l'accueil, jamais un écran choisi par l'adresse.
    public function testUneOrigineInconnueRenvoieALAccueil(): void
    {
        $this->assertSame('ecran=accueil', Regles::ecranDeRetour('evil', null));
        $this->assertSame('ecran=accueil', Regles::ecranDeRetour(null, null));
        $this->assertSame('ecran=accueil', Regles::ecranDeRetour(['onboarding'], null));
    }
}
