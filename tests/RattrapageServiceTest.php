<?php
// Tests de RattrapageService — D3 Duo dissous (écran evenement) — parcours 2 (journée de jeu) — lot 1 — B.

use PHPUnit\Framework\TestCase;

class RattrapageServiceTest extends TestCase
{
    private PDO $pdo;
    private AppairageService $appairage;
    private RattrapageService $rattrapage;
    private DateTimeImmutable $midi;

    protected function setUp(): void
    {
        $this->pdo = Bdd::connexion();
        viderLaBase($this->pdo);
        $this->appairage = AppairageService::construire($this->pdo);
        $this->rattrapage = new RattrapageService($this->pdo, new JoueurDepot($this->pdo));
        $this->midi = new DateTimeImmutable('2026-10-15 12:00:00');
    }

    // Lou dissout le duo : Mia, la partenaire, porte le marqueur que D3 doit lire. Renvoie [Lou, Mia] relus.
    private function duoDissousParLou(): array
    {
        $depot = new JoueurDepot($this->pdo);
        $lou = $this->appairage->code($depot->trouverParId($depot->ajouter('Lou', 'Lou', 'empreinte', $this->midi)), $this->midi);
        $mia = $depot->trouverParId($depot->ajouter('Mia', 'Mia', 'empreinte', $this->midi));
        $this->appairage->rejoindre($mia, $lou->codeInvitation, 'session-mia', $this->midi);
        $this->appairage->dissoudre($depot->trouverParId($lou->id));
        return [$depot->trouverParId($lou->id), $depot->trouverParId($mia->id)];
    }

    // Spec #59, dissolution signalée une seule fois : le joueur marqué reçoit D3, et le marqueur repasse à 0.
    public function testUnJoueurMarqueRecoitD3EtLeMarqueurRepasseAZero(): void
    {
        [, $mia] = $this->duoDissousParLou();

        $evenements = $this->rattrapage->rattraper($mia, $this->midi);

        $this->assertSame([Evenement::DuoDissous], $evenements);
        $this->assertFalse((new JoueurDepot($this->pdo))->trouverParId($mia->id)->dissolutionASignaler);
    }

    // Deux appareils, ou un objet Joueur lu avant le premier rattrapage : la ligne verrouillée fait foi, D3 ne sort qu'une fois.
    public function testDeuxRattrapagesSuccessifsNeProduisentPasDeuxD3(): void
    {
        [, $mia] = $this->duoDissousParLou();

        $this->rattrapage->rattraper($mia, $this->midi);

        $this->assertSame([], $this->rattrapage->rattraper($mia, $this->midi));
    }

    // Celui qui a dissous n'est pas prévenu : il sait ce qu'il a fait (cadrage § 10).
    public function testCeluiQuiADissousNeRecoitRien(): void
    {
        [$lou] = $this->duoDissousParLou();

        $this->assertSame([], $this->rattrapage->rattraper($lou, $this->midi));
    }
}
