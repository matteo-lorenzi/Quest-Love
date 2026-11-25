<?php
// Tests de la vue f3-supprimer-compte — F3 Suppression de compte — parcours Profil, duo et système — lot 1 — A.

use PHPUnit\Framework\TestCase;

// Vue.php définit e() ; il n'a aucune dépendance au chargement.
require_once __DIR__ . '/../app/noyau/Vue.php';

class SupprimerCompteVueTest extends TestCase
{
    // Rend la vue comme Vue::rendre le ferait, sans gabarit d'en-tête ni de pied.
    private function rendre(?string $partenaire, bool $refus = false): string
    {
        $jeton = 'jeton-de-test';
        ob_start();
        require __DIR__ . '/../app/vues/ecrans/f3-supprimer-compte.php';
        return ob_get_clean();
    }

    // En duo : la phrase sur la dissolution porte le pseudo du partenaire, échappé.
    public function testEnDuoLaPhraseNommeLePartenaireEchappe(): void
    {
        $html = $this->rendre('Tom <b>');

        $this->assertStringContainsString('Ton duo avec Tom &lt;b&gt; sera aussi dissous.', $html);
    }

    // Sans duo : pas de phrase sur le duo.
    public function testSansDuoAucunePhraseSurLeDuo(): void
    {
        $this->assertStringNotContainsString('Ton duo avec', $this->rendre(null));
    }

    // Suppression refusée (duo reformé entre les deux transactions) : l'écran l'explique, et seulement dans ce cas.
    public function testLeRefusEstExpliqueSeulementApresUnRefus(): void
    {
        $this->assertStringContainsString('role="alert"', $this->rendre('Tom', refus: true));
        $this->assertStringNotContainsString('role="alert"', $this->rendre('Tom'));
    }

    // La confirmation est un POST avec le jeton ; « Annuler » et la flèche ← mènent au profil.
    public function testLaConfirmationEstUnPostAvecJetonEtLAnnulationMeneAuProfil(): void
    {
        $html = $this->rendre(null);

        $this->assertMatchesRegularExpression(
            '#<form method="post" action="index\.php\?ecran=supprimer-compte">\s*<input type="hidden" name="jeton" value="jeton-de-test">#',
            $html,
        );
        $this->assertStringContainsString('<a href="index.php?ecran=profil">Annuler</a>', $html);
        $this->assertStringContainsString('href="index.php?ecran=profil" aria-label="Retour"', $html);
    }
}
