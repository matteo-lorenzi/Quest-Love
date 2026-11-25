<?php
// Tests de ProfilControleur::elementsAffiches — F1 Profil — parcours Profil, duo et système — lot 1 — A.

use PHPUnit\Framework\TestCase;

// Le contrôleur n'est pas dans l'autochargement des tests (couche modèle seulement) ; son fichier n'a aucune dépendance au chargement.
require_once __DIR__ . '/../app/controleurs/ProfilControleur.php';

class ProfilTest extends TestCase
{
    // Duo activé (F1 · Profil — avec duo) : carte duo, barre basse avec l'onglet Profil, pas de flèche ←.
    public function testAccesAuJeuMontreLaCarteDuoEtLaBarreSansRetour(): void
    {
        $this->assertSame(
            ['carteDuo' => true, 'barre' => true, 'retour' => null],
            ProfilControleur::elementsAffiches(EtapeAcces::AccesAuJeu),
        );
    }
}
