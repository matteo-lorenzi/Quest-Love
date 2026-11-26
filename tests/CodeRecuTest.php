<?php
// Tests de CodeRecu::conserver : garde du code d'invitation reçu par un lien — A1 Écran d'entrée — parcours 1 (appairage) — lot 1 — commun.

use PHPUnit\Framework\TestCase;

// CodeRecu n'est pas dans l'autochargement des tests (couche modèle seulement) ; son fichier n'a aucune dépendance au chargement.
require_once __DIR__ . '/../app/noyau/CodeRecu.php';

class CodeRecuTest extends TestCase
{
    protected function setUp(): void
    {
        $_GET = [];
        $_SESSION = [];
    }

    public function testUnCodeNonVideEstGardeEtRenduAuxAppelsSuivants(): void
    {
        $_GET['code'] = ' ABCD-EFGH ';
        $this->assertSame('ABCD-EFGH', CodeRecu::conserver());

        $_GET = [];
        $this->assertSame('ABCD-EFGH', CodeRecu::conserver());
    }

    public function testUnCodeVideOuIllisibleEstIgnore(): void
    {
        foreach (['', '   ', ['x']] as $recu) {
            $_GET['code'] = $recu;
            $this->assertNull(CodeRecu::conserver());
            $this->assertSame([], $_SESSION);
        }
    }

    public function testUnCodeVideNeRemplacePasLeCodeGarde(): void
    {
        $_GET['code'] = 'ABCD-EFGH';
        CodeRecu::conserver();
        $_GET['code'] = '';
        $this->assertSame('ABCD-EFGH', CodeRecu::conserver());
    }

    public function testLeCodeEstTronqueASaLongueurMaximale(): void
    {
        $_GET['code'] = str_repeat('a', 200);
        $this->assertSame(str_repeat('a', 64), CodeRecu::conserver());
    }
}
