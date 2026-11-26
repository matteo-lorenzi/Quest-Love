<?php
// Tests de la vue a1-bienvenue : écran d'entrée, avec et sans code reçu — A1 Écran d'entrée — parcours 1 (appairage) — lot 1 — A.

use PHPUnit\Framework\TestCase;

// Vue.php définit e() ; il n'a aucune dépendance au chargement.
require_once __DIR__ . '/../app/noyau/Vue.php';

class BienvenueVueTest extends TestCase
{
    // Rend la vue comme Vue::rendre le ferait, sans gabarit : seul le corps de l'écran nous intéresse.
    private function rendre(?string $codeRecu): string
    {
        ob_start();
        require __DIR__ . '/../app/vues/ecrans/a1-bienvenue.php';
        return ob_get_clean();
    }

    // Visiteur sans code : un titre, les deux actions vers A2 et A3, le lien légal vers A4.
    public function testSansCodeLesDeuxActionsEtLeLienLegal(): void
    {
        $html = $this->rendre(null);

        $this->assertSame(1, substr_count($html, '<h1'));
        $this->assertStringContainsString('href="index.php?ecran=inscription"', $html);
        $this->assertStringContainsString('href="index.php?ecran=connexion"', $html);
        $this->assertStringContainsString('href="index.php?ecran=legal"', $html);
    }

    // Le ton est celui du jeu : le tutoiement, jamais le vouvoiement (cadrage § 8.1).
    public function testTutoiement(): void
    {
        foreach ([null, 'ABCD-EFGH'] as $code) {
            $html = mb_strtolower($this->rendre($code));
            $this->assertDoesNotMatchRegularExpression('/\b(vous|votre|vos)\b/u', $html);
            $this->assertMatchesRegularExpression('/\b(tu|ton|ta|t\')/u', $html);
        }
    }

    // Entrée par invitation : le titre change, les mêmes deux actions restent, le code et l'invitant ne sont pas montrés.
    public function testEntreeParInvitation(): void
    {
        $sans = $this->rendre(null);
        $avec = $this->rendre('ABCD-EFGH');

        $this->assertStringContainsString('invit', mb_strtolower($avec));
        $this->assertNotSame(self::titre($sans), self::titre($avec));
        $this->assertStringContainsString('href="index.php?ecran=inscription"', $avec);
        $this->assertStringContainsString('href="index.php?ecran=connexion"', $avec);
        $this->assertStringNotContainsString('ABCD-EFGH', $avec);
    }

    private static function titre(string $html): string
    {
        preg_match('#<h1[^>]*>(.*?)</h1>#s', $html, $m);
        return $m[1] ?? '';
    }
}
