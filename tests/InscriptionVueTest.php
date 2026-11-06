<?php
// Tests de la vue a2-inscription : les messages d'erreur de chaque cas d'ErreurInscription — A2 Inscription — parcours 1 (appairage) — lot 1 — A.

use PHPUnit\Framework\TestCase;

// Vue.php définit e() ; il n'a aucune dépendance au chargement.
require_once __DIR__ . '/../app/noyau/Vue.php';

class InscriptionVueTest extends TestCase
{
    private const REPLI = 'Ce champ est incorrect.';

    // Rend la vue comme Vue::rendre le ferait, sans gabarit : seul le corps de l'écran nous intéresse.
    private function rendre(array $erreurs): string
    {
        $identifiant = '';
        $pseudo = '';
        $attente = null;
        $codeRecu = null;
        $jeton = 'x';
        ob_start();
        require __DIR__ . '/../app/vues/ecrans/a2-inscription.php';
        return ob_get_clean();
    }

    // Chaque cas a un message propre dans au moins un champ ; dans les autres, le repli explicite « Ce champ est incorrect. » s'affiche.
    public function testChaqueCasDErreurAUnMessageOuLeRepli(): void
    {
        foreach (ErreurInscription::cases() as $cas) {
            $html = $this->rendre(['identifiant' => [$cas], 'pseudo' => [$cas], 'mdp' => [$cas]]);
            $this->assertSame(3, substr_count($html, 'class="formulaire__erreur"'), $cas->name);
            $this->assertLessThan(3, substr_count($html, self::REPLI), "$cas->name n'a de message dans aucun champ");
        }
    }

    // Une chaîne restée en session au lieu d'un cas ne plante pas la vue : message du même nom, ou repli.
    public function testUneChaineEnSessionNePlantePas(): void
    {
        $html = $this->rendre(['identifiant' => ['Longueur'], 'pseudo' => ['inconnu']]);

        $this->assertStringContainsString('doit faire de', $html);
        $this->assertStringContainsString(self::REPLI, $html);
    }
}
