<?php
// Tests de QuitterDuoControleur::origine — F2 Dissolution du duo — parcours 1 (appairage) — lot 1 — A.

use PHPUnit\Framework\TestCase;

// Le contrôleur n'est pas dans l'autochargement des tests (couche modèle seulement) ; son fichier n'a aucune dépendance au chargement.
require_once __DIR__ . '/../app/controleurs/QuitterDuoControleur.php';

class QuitterDuoTest extends TestCase
{
    // L'origine de F2 est validée contre l'énumération : chaque cas est reconnu, tout le reste est refusé (le contrôleur retombe alors sur l'étape d'accès).
    public function testLOrigineEstValideeContreLEnumeration(): void
    {
        $this->assertSame(OrigineQuitterDuo::SalleAttente, QuitterDuoControleur::origine('salle-attente'));
        $this->assertSame(OrigineQuitterDuo::Onboarding, QuitterDuoControleur::origine('onboarding'));
        $this->assertNull(QuitterDuoControleur::origine('evil'));
        $this->assertNull(QuitterDuoControleur::origine(null));
        $this->assertNull(QuitterDuoControleur::origine(['onboarding']));
    }
}
