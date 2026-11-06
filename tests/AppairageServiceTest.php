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

    // B1 est rechargé souvent : tant que le code est valable, le joueur retrouve le même, celui qu'il a peut-être déjà donné.
    public function testUnCodeEncoreValableEstRenduTelQuel(): void
    {
        $premier = $this->service->code($this->creerJoueur('Lou'), $this->midi);

        $second = $this->service->code($premier, $this->midi->modify('+59 minutes'));

        $this->assertSame($premier->codeInvitation, $second->codeInvitation);
        $this->assertEquals(new DateTimeImmutable('2026-10-15 13:00:00'), $second->dateExpirationCode);
    }

    // Un code expiré ne sert plus à rien : B1 en donne un nouveau, valable à partir de maintenant.
    public function testUnCodeExpireEstRemplace(): void
    {
        $premier = $this->service->code($this->creerJoueur('Lou'), $this->midi);

        $second = $this->service->code($premier, new DateTimeImmutable('2026-10-15 13:00:00'));

        $this->assertNotSame($premier->codeInvitation, $second->codeInvitation);
        $this->assertEquals(new DateTimeImmutable('2026-10-15 14:00:00'), $second->dateExpirationCode);
    }

    // Lien « nouveau code » : le code change même s'il était encore valable, et l'échéance repart.
    public function testRegenererRemplaceUnCodeEncoreValable(): void
    {
        $premier = $this->service->code($this->creerJoueur('Lou'), $this->midi);

        $second = $this->service->regenerer($premier, $this->midi->modify('+10 minutes'));

        $this->assertNotSame($premier->codeInvitation, $second->codeInvitation);
        $this->assertEquals(new DateTimeImmutable('2026-10-15 13:10:00'), $second->dateExpirationCode);
    }

    // Doc technique § 3.6 : le duo naît quand le deuxième joueur saisit le code, avec ses deux joueurs ; les codes disparaissent.
    public function testUnCodeValableFormeLeDuoDesDeuxJoueurs(): void
    {
        $proprietaire = $this->service->code($this->creerJoueur('Lou'), $this->midi);
        $invite = $this->creerJoueur('Mia');

        $duo = $this->service->rejoindre($invite, $proprietaire->codeInvitation, 'session-mia', $this->midi->modify('+5 minutes'));

        $this->assertInstanceOf(Duo::class, $duo);
        $depot = new JoueurDepot($this->pdo);
        foreach ([$proprietaire, $invite] as $joueur) {
            $relu = $depot->trouverParId($joueur->id);
            $this->assertSame($duo->id, $relu->duoId);
            $this->assertNull($relu->codeInvitation);
        }
    }
}
