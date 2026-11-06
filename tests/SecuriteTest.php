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

    // La CSP ne casse ni les styles en ligne ni les formulaires envoyés vers le site lui-même.
    public function testCspLaisseStylesEnLigneEtFormulaires(): void
    {
        $csp = Securite::entetes()['Content-Security-Policy'];
        $this->assertStringContainsString("style-src 'self' 'unsafe-inline'", $csp);
        $this->assertStringContainsString("form-action 'self'", $csp);
        $this->assertStringNotContainsString("script-src 'unsafe", $csp);
    }
}
