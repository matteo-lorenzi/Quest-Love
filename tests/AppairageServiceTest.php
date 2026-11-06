<?php
// Tests de AppairageService — B1 Salle d'attente — parcours 1 (arrivée et appairage) — lot 1 — A.

use PHPUnit\Framework\TestCase;

class AppairageServiceTest extends TestCase
{
    private PDO $pdo;
    private AppairageService $service;
    private DateTimeImmutable $midi;

    protected function setUp(): void
    {
        // Chaque test repart sans joueur, sans duo ni tentative. Les joueurs sont créés par chaque test, sans duo.
        $this->pdo = Bdd::connexion();
        $this->pdo->exec('DELETE FROM tentative');
        $this->pdo->exec('DELETE FROM joueur');
        $this->pdo->exec('DELETE FROM duo');

        $this->service = new AppairageService($this->pdo, new JoueurDepot($this->pdo), new DuoDepot($this->pdo), new TentativeDepot($this->pdo));
        $this->midi = new DateTimeImmutable('2026-10-15 12:00:00');
    }

    private function creerJoueur(string $identifiant): Joueur
    {
        $depot = new JoueurDepot($this->pdo);
        return $depot->trouverParId($depot->ajouter($identifiant, $identifiant, 'empreinte', $this->midi));
    }

    // Cadrage § 7 : le code compte 8 caractères parmi 32 (ni I, ni O, ni 0, ni 1) et vaut 60 minutes.
    public function testUnJoueurSansCodeEnRecoitUnValableSoixanteMinutes(): void
    {
        $joueur = $this->service->code($this->creerJoueur('Lou'), $this->midi);

        $this->assertMatchesRegularExpression('/^[A-HJ-NP-Z2-9]{8}$/D', $joueur->codeInvitation);
        $this->assertEquals(new DateTimeImmutable('2026-10-15 13:00:00'), $joueur->dateExpirationCode);
        $this->assertNull($joueur->duoId);
    }
}
