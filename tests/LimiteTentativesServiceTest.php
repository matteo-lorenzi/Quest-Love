<?php
// Tests de LimiteTentativesService — A3 Connexion et B1 Salle d'attente — parcours 1 (arrivée et appairage) — lot 1 — A.

use PHPUnit\Framework\TestCase;

class LimiteTentativesServiceTest extends TestCase
{
    private PDO $pdo;
    private LimiteTentativesService $limite;
    private DateTimeImmutable $midi;

    protected function setUp(): void
    {
        $this->pdo = Bdd::connexion();
        $this->pdo->exec('DELETE FROM tentative');
        $this->limite = new LimiteTentativesService(new TentativeDepot($this->pdo));
        $this->midi = new DateTimeImmutable('2026-10-15 12:00:00');
    }

    private function compter(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM tentative')->fetchColumn();
    }

    // Cadrage § 6.6 : les codes et la connexion ont chacun leur compteur, sinon un joueur bloquerait sa propre connexion en se trompant de code.
    public function testLesEssaisDeCodeNeBloquentPasLaConnexion(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->limite->reserver(['42'], TypeTentative::Code, $this->midi);
        }

        $this->assertEquals(new DateTimeImmutable('2026-10-15 12:15:00'), $this->limite->reserver(['42'], TypeTentative::Code, $this->midi));
        $this->assertIsArray($this->limite->reserver(['42'], TypeTentative::Connexion, $this->midi));
    }

    // Requêtes simultanées : chacune écrit son essai avant de compter, et ne compte que ceux écrits avant elle.
    // Les cinq premières passent sans qu'aucune n'ait été vérifiée ; la sixième est refusée, sans attendre leur verdict.
    public function testUnEssaiEstReserveAvantD_etreVerifie(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->assertIsArray($this->limite->reserver(['42'], TypeTentative::Code, $this->midi));
        }

        $this->assertInstanceOf(DateTimeImmutable::class, $this->limite->reserver(['42'], TypeTentative::Code, $this->midi));
    }

    // Un essai refusé pendant l'attente n'est pas enregistré, sinon chaque nouvel essai la prolongerait.
    public function testUnEssaiRefuseNeLaisseAucuneTrace(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->limite->reserver(['42'], TypeTentative::Code, $this->midi);
        }
        $this->limite->reserver(['42'], TypeTentative::Code, $this->midi);

        $this->assertSame(5, $this->compter());
    }

    // Un essai réussi ne compte pas : le libérer rend sa place.
    public function testUnEssaiLibereRendSaPlace(): void
    {
        $reservations = [];
        for ($i = 0; $i < 5; $i++) {
            $reservations[] = $this->limite->reserver(['42'], TypeTentative::Code, $this->midi);
        }
        $this->limite->liberer($reservations[2]);

        $this->assertIsArray($this->limite->reserver(['42'], TypeTentative::Code, $this->midi));
    }

    // Deux clés (joueur et session) : l'une bloquée suffit, et l'heure annoncée est la plus tardive.
    public function testUneCleBloqueeSuffitEtL_heureAnnonceeEstLaPlusTardive(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->limite->reserver(['joueur'], TypeTentative::Code, $this->midi);
            $this->limite->reserver(['session'], TypeTentative::Code, $this->midi->modify('+5 minutes'));
        }

        $attente = $this->limite->reserver(['joueur', 'session', 'neuve'], TypeTentative::Code, $this->midi->modify('+6 minutes'));

        $this->assertEquals(new DateTimeImmutable('2026-10-15 12:20:00'), $attente);
    }
}
