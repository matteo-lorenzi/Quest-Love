<?php
// Tests de la vue f1-profil — F1 Profil — parcours Profil, duo et système — lot 1 — A.

use PHPUnit\Framework\TestCase;

// Vue.php définit e() ; les deux contrôleurs donnent le paramètre d'origine des liens vers B3 et F2. Aucun n'a de dépendance au chargement.
require_once __DIR__ . '/../app/noyau/Vue.php';
require_once __DIR__ . '/../app/controleurs/ReglesControleur.php';
require_once __DIR__ . '/../app/controleurs/QuitterDuoControleur.php';

class ProfilVueTest extends TestCase
{
    // Rend la vue comme Vue::rendre le ferait, sans gabarit d'en-tête ni de pied : variante « duo activé ».
    private function rendre(): string
    {
        $vue = 'f1-profil';
        $pseudo = 'Léa <b>';
        $identifiant = 'lea.martin&co';
        $carteDuo = true;
        $partenaire = 'Tom';
        $barre = true;
        $retour = null;
        $jeton = 'jeton-de-test';
        ob_start();
        require __DIR__ . '/../app/vues/ecrans/f1-profil.php';
        return ob_get_clean();
    }

    // Le joueur relit son pseudo et son identifiant de connexion, échappés et entiers (la coupe « … » est celle de la feuille de style).
    public function testLePseudoEtLIdentifiantSontAffichesEchappes(): void
    {
        $html = $this->rendre();
        $this->assertStringContainsString('Léa &lt;b&gt;', $html);
        $this->assertStringContainsString('lea.martin&amp;co', $html);
        $this->assertStringContainsString('Tu es en duo avec Tom', $html);
    }

    // La déconnexion est un formulaire POST vers deconnexion qui porte le jeton : un aperçu de lien ne déconnecte pas.
    public function testLaDeconnexionEstUnPostAvecJeton(): void
    {
        $html = $this->rendre();
        $this->assertMatchesRegularExpression(
            '#<form method="post" action="index\.php\?ecran=deconnexion">\s*<input type="hidden" name="jeton" value="jeton-de-test">#',
            $html,
        );
    }

    // Duo activé : la barre basse porte l'onglet Profil, actif ; « Règles du jeu » et « Quitter mon duo » reviennent au profil.
    public function testLaBarreEtLesLiensReviennentAuProfil(): void
    {
        $html = $this->rendre();
        $this->assertStringContainsString('href="index.php?ecran=profil" aria-current="page"', $html);
        $this->assertStringContainsString('href="index.php?ecran=regles&amp;origine=profil"', $html);
        $this->assertStringContainsString('href="index.php?ecran=quitter-duo&amp;origine=profil"', $html);
    }
}
