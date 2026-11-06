<?php
// Tests de LimiteTentatives — A3 Connexion et B1 Salle d'attente — parcours 1 (arrivée et appairage) — lot 1 — A.

use PHPUnit\Framework\TestCase;

class LimiteTentativesTest extends TestCase
{
    private PDO $pdo;
    private LimiteTentatives $limite;
    private DateTimeImmutable $midi;

    protected function setUp(): void
    {
        $this->pdo = Bdd::connexion();
        $this->pdo->exec('DELETE FROM tentative');
        $this->limite = new LimiteTentatives(new TentativeDepot($this->pdo));
        $this->midi = new DateTimeImmutable('2026-10-15 12:00:00');
    }

    // Cadrage § 6.6 : les codes et la connexion ont chacun leur compteur, sinon un joueur bloquerait sa propre connexion en se trompant de code.
    public function testLesEchecsDeCodeNeBloquentPasLaConnexion(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->limite->echouer('42', TypeTentative::Code, $this->midi);
        }

        $this->assertEquals(new DateTimeImmutable('2026-10-15 12:15:00'), $this->limite->attente('42', TypeTentative::Code, $this->midi));
        $this->assertNull($this->limite->attente('42', TypeTentative::Connexion, $this->midi));
    }
}
