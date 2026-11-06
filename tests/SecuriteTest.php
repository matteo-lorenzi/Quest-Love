<?php
// Tests de Securite::entetes — en-têtes de sécurité de toute réponse — parcours 1 (appairage, F2 dissolution du duo) — lot 1 — commun.

use PHPUnit\Framework\TestCase;

// Securite n'est pas dans l'autochargement des tests (couche modèle seulement) ; son fichier n'a aucune dépendance au chargement.
require_once __DIR__ . '/../app/noyau/Securite.php';

class SecuriteTest extends TestCase
{
    // Les quatre en-têtes du ticket #31 sont présents : anti-cadre (deux formes), type MIME, référent.
    public function testQuatreEnTetes(): void
    {
        $entetes = Securite::entetes();
        $this->assertSame('DENY', $entetes['X-Frame-Options']);
        $this->assertSame('nosniff', $entetes['X-Content-Type-Options']);
        $this->assertSame('same-origin', $entetes['Referrer-Policy']);
        $this->assertStringContainsString("frame-ancestors 'none'", $entetes['Content-Security-Policy']);
    }

    protected function setUp(): void
    {
        $_SESSION = [];
    }

    // Ticket #34 : le jeton change après renouvellement, et l'ancien ne passe plus la vérification.
    public function testRenouvelerJetonInvalideLAncien(): void
    {
        $ancien = Securite::jeton();
        Securite::renouvelerJeton();
        $this->assertNotSame($ancien, Securite::jeton());
        $this->assertFalse(Securite::verifier($ancien));
        $this->assertTrue(Securite::verifier(Securite::jeton()));
    }

    // Ticket #34 : plus de 2 h sans requête, un joueur connecté perd sa session (le routeur le renvoie alors vers A3).
    public function testSessionInactiveDepuisPlusDeDeuxHeuresExpire(): void
    {
        $_SESSION = ['joueur_id' => 1, 'activite' => 1000];
        $this->assertTrue(Securite::expirerSiInactive(1000 + 7201));
        $this->assertArrayNotHasKey('joueur_id', $_SESSION);
    }

    // Ticket #34 : une session récente continue, et sa date d'activité avance.
    public function testSessionRecenteContinue(): void
    {
        $_SESSION = ['joueur_id' => 1, 'activite' => 1000];
        $this->assertFalse(Securite::expirerSiInactive(1000 + 7200));
        $this->assertSame(1, $_SESSION['joueur_id']);
        $this->assertSame(8200, $_SESSION['activite']);
    }

    // Un visiteur non connecté n'a rien à perdre : sa session (jeton, code reçu) reste.
    public function testVisiteurNonConnecteNExpirePas(): void
    {
        $_SESSION = ['activite' => 1000, 'jeton' => 'x'];
        $this->assertFalse(Securite::expirerSiInactive(1000 + 99999));
        $this->assertSame('x', $_SESSION['jeton']);
    }

    // La CSP ne casse ni les styles en ligne ni les formulaires envoyés vers le site lui-même.
    public function testCspLaisseStylesEnLigneEtFormulaires(): void
    {
        $csp = Securite::entetes()['Content-Security-Policy'];
        $this->assertStringContainsString("style-src 'self' 'unsafe-inline'", $csp);
        $this->assertStringContainsString("form-action 'self'", $csp);
        $this->assertStringNotContainsString("script-src 'unsafe", $csp);
    }
}
