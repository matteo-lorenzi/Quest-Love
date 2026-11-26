<?php
// Tests de ProfilControleur::elementsAffiches — F1 Profil — parcours Profil, duo et système — lot 1 — A.

use PHPUnit\Framework\TestCase;

// Le contrôleur n'est pas dans l'autochargement des tests (couche modèle seulement) ; son fichier n'a aucune dépendance au chargement.
require_once __DIR__ . '/../app/controleurs/ProfilControleur.php';
// elementsAffiches tire l'écran de la flèche ← de Routeur::ecranDeLEtape ; le routeur n'a lui non plus aucune dépendance au chargement.
require_once __DIR__ . '/../app/noyau/Routeur.php';

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

    // Sans duo (F1 · Profil — cas : sans duo) : ni carte duo ni barre basse, flèche ← vers B1.
    public function testSansDuoNeMontreNiCarteNiBarreEtRevientALaSalleDAttente(): void
    {
        $this->assertSame(
            ['carteDuo' => false, 'barre' => false, 'retour' => 'salle-attente'],
            ProfilControleur::elementsAffiches(EtapeAcces::SansDuo),
        );
    }

    // Duo formé, pas encore activé (F1 · Profil — cas : duo sans accès au jeu) : carte duo, pas de barre, flèche ← vers B2.
    public function testOnboardingAFaireMontreLaCarteDuoSansBarreAvecRetourVersB2(): void
    {
        $this->assertSame(
            ['carteDuo' => true, 'barre' => false, 'retour' => 'onboarding'],
            ProfilControleur::elementsAffiches(EtapeAcces::OnboardingAFaire),
        );
    }

    // Même cas, joueur qui a fini l'onboarding avant son partenaire : la flèche ← mène à B2, qui montre sa variante d'attente.
    public function testAttenteMontreLaCarteDuoSansBarreAvecRetourVersB2(): void
    {
        $this->assertSame(
            ['carteDuo' => true, 'barre' => false, 'retour' => 'onboarding'],
            ProfilControleur::elementsAffiches(EtapeAcces::Attente),
        );
    }
}
