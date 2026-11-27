<?php
// Tests de LegalControleur::page et de la vue a4-legal — A4 Pages légales — parcours 1 (appairage) — lot 1 — A.

use PHPUnit\Framework\TestCase;

// Le contrôleur et Vue.php (qui définit e()) ne sont pas dans l'autochargement des tests ; aucun n'a de dépendance au chargement.
require_once __DIR__ . '/../app/controleurs/LegalControleur.php';
require_once __DIR__ . '/../app/noyau/Vue.php';

class LegalTest extends TestCase
{
    // Liste fermée : chaque page est reconnue, tout le reste devient les mentions légales.
    public function testLaPageEstValideeContreLaListe(): void
    {
        foreach (array_keys(LegalControleur::PAGES) as $page) {
            $this->assertSame($page, LegalControleur::page($page));
        }
        $this->assertSame('mentions', LegalControleur::page(null));
        $this->assertSame('mentions', LegalControleur::page('evil'));
        $this->assertSame('mentions', LegalControleur::page(['conditions']));
    }

    // Route publique, GET seulement : le visiteur de A1 et A2 y arrive sans compte.
    public function testLaRouteEstPubliqueEnGetSeulement(): void
    {
        [$classe, $methodeGet, $methodePost, $acces] = Routeur::ECRANS['legal'];
        $this->assertSame(['LegalControleur', 'afficher', null], [$classe, $methodeGet, $methodePost]);
        $this->assertNull(Routeur::redirectionAcces($acces, false, null));
    }

    // Chaque page : son titre, ses rubriques du cadrage § 10, la page courante marquée, des liens vers les trois pages, sans vouvoiement.
    public function testChaquePageAfficheSesRubriques(): void
    {
        $attendu = [
            'mentions' => ['Éditeur', 'Directeur de publication', 'Hébergeur'],
            'confidentialite' => ['Les données', 'Pourquoi', 'Combien de temps', 'Tes droits', 'Contact', 'journaux du serveur', 'sauvegardes de l&#039;hébergeur'],
            'conditions' => ['Les règles du jeu', 'Pas de modération', 'Ta responsabilité'],
        ];
        foreach ($attendu as $page => $mots) {
            $html = self::rendre($page);
            $this->assertSame(1, substr_count($html, '<h1'), $page);
            $this->assertStringContainsString(e(LegalControleur::PAGES[$page]) . '</h1>', $html);
            $this->assertStringContainsString('page=' . $page . '" aria-current="page"', $html);
            $this->assertSame(1, substr_count($html, 'aria-current'), $page);
            $this->assertSame(3, substr_count($html, 'href="index.php?ecran=legal&amp;page='), $page);
            foreach ($mots as $mot) {
                $this->assertStringContainsString($mot, $html, "$page : $mot");
            }
            $this->assertDoesNotMatchRegularExpression('/\b(vous|votre|vos)\b/u', mb_strtolower($html), $page);
        }
    }

    // Rend la vue comme Vue::rendre le ferait, sans gabarit.
    private static function rendre(string $page): string
    {
        $pages = LegalControleur::PAGES;
        $titre = $pages[$page];
        ob_start();
        require __DIR__ . '/../app/vues/ecrans/a4-legal.php';
        return ob_get_clean();
    }
}
