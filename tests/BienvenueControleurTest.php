<?php
// Tests de BienvenueControleur et de la sortie du routeur par exception — A1 Écran d'entrée — parcours 1 (appairage) — lot 1 — A.

use PHPUnit\Framework\TestCase;

// Ces classes ne sont pas dans l'autochargement des tests (couche modèle seulement) ; leurs fichiers n'ont aucune dépendance au chargement.
require_once __DIR__ . '/../app/noyau/Redirection.php';
require_once __DIR__ . '/../app/noyau/ErreurHttp.php';
require_once __DIR__ . '/../app/noyau/Routeur.php';
require_once __DIR__ . '/../app/noyau/CodeRecu.php';
require_once __DIR__ . '/../app/controleurs/BienvenueControleur.php';

class BienvenueControleurTest extends TestCase
{
    private PDO $pdo;
    private DateTimeImmutable $midi;

    protected function setUp(): void
    {
        $this->pdo = Bdd::connexion();
        viderLaBase($this->pdo);
        $this->midi = new DateTimeImmutable('2026-10-15 12:00:00');
        $_GET = [];
        $_SESSION = [];
    }

    // Cadrage § 6.3 : un joueur connecté sans duo qui ouvre un lien d'invitation est renvoyé vers B1, et le code est déjà en session pour y être prérempli.
    // Le test lit la session après la redirection : inverser « garder » et « rediriger » dans le contrôleur le fait échouer.
    public function testUnJoueurConnecteSansDuoEstRenvoyeVersB1AvecSonCodeGarde(): void
    {
        $depot = new JoueurDepot($this->pdo);
        $_SESSION['joueur_id'] = $depot->ajouter('Lou', 'Lou', 'empreinte', $this->midi);
        $_GET['code'] = 'ABCD-EFGH';

        try {
            (new BienvenueControleur($this->pdo, $this->midi))->afficher();
            $this->fail('Le joueur connecté aurait dû être redirigé.');
        } catch (Redirection $redirection) {
            $this->assertSame('salle-attente', $redirection->ecran);
        }

        $_GET = [];
        $this->assertSame('ABCD-EFGH', CodeRecu::reprendre());
    }

    public function testRedirigerLeveUneRedirectionQuiPorteLEcranCible(): void
    {
        try {
            Routeur::rediriger('connexion');
            $this->fail('rediriger() aurait dû lever Redirection.');
        } catch (Redirection $redirection) {
            $this->assertSame('connexion', $redirection->ecran);
        }
    }

    // Un écran absent de la table : G1 en 404, porté par l'exception ; le routeur ne rend ni n'arrête rien lui-même.
    public function testUnEcranInconnuLeveUneErreurHttp404(): void
    {
        $_GET['ecran'] = 'nexiste-pas';

        try {
            Routeur::traiter($this->pdo, $this->midi);
            $this->fail('Un écran inconnu aurait dû lever ErreurHttp.');
        } catch (ErreurHttp $erreur) {
            $this->assertSame(404, $erreur->statut);
        }
    }
}
