<?php
// Tests de CompteService::connecter — A3 Connexion — parcours 1 (arrivée et appairage) — lot 1 — A.

use PHPUnit\Framework\TestCase;

class CompteServiceTest extends TestCase
{
    private const MDP = 'Lou-test-2026!';

    private PDO $pdo;
    private CompteService $service;
    private DateTimeImmutable $midi;

    protected function setUp(): void
    {
        // Chaque test repart sans joueur ni tentative, avec un seul joueur connu : Lou, sans duo.
        $this->pdo = Bdd::connexion();
        $this->pdo->exec('DELETE FROM tentative');
        $this->pdo->exec('DELETE FROM joueur');
        $this->pdo->prepare(
            'INSERT INTO joueur (identifiant, pseudo, mdp_hash, date_creation) VALUES (?, ?, ?, ?)'
        )->execute(['Lou', 'Lou', password_hash(self::MDP, PASSWORD_DEFAULT), '2026-10-01 09:00:00']);

        $this->service = new CompteService(new JoueurDepot($this->pdo), new TentativeDepot($this->pdo));
        $this->midi = new DateTimeImmutable('2026-10-15 12:00:00');
    }

    public function testConnexionReussieRenvoieLeJoueur(): void
    {
        $resultat = $this->service->connecter('Lou', self::MDP, 'session-a', $this->midi);

        $this->assertInstanceOf(Joueur::class, $resultat);
        $this->assertSame('Lou', $resultat->identifiant);
        $this->assertNull($resultat->duoId);
    }

    public function testIdentifiantInconnuEtMauvaisMotDePasseDonnentLeMemeRefus(): void
    {
        $this->assertNull($this->service->connecter('Inconnu', self::MDP, 'session-a', $this->midi));
        $this->assertNull($this->service->connecter('Lou', 'mauvais', 'session-a', $this->midi));
    }

    public function testIdentifiantReconnuSansTenirCompteDesMajuscules(): void
    {
        $this->assertInstanceOf(Joueur::class, $this->service->connecter('LOU', self::MDP, 'session-a', $this->midi));
    }

    public function testCinqEchecsEnQuinzeMinutesDeclenchentUneAttenteDeQuinzeMinutes(): void
    {
        $this->echouer(5, 'Lou', 'session-a');

        $resultat = $this->service->connecter('Lou', 'mauvais', 'session-a', new DateTimeImmutable('2026-10-15 12:05:00'));

        // Le 5ᵉ échec date de 12 h 04 : réessai possible à 12 h 19 (cadrage § 6.6).
        $this->assertEquals(new DateTimeImmutable('2026-10-15 12:19:00'), $resultat);
    }

    public function testPendantLAttenteMemeLeBonMotDePasseEstRefuse(): void
    {
        $this->echouer(5, 'Lou', 'session-a');

        // 12 h 17 : l'attente annoncée court jusqu'à 12 h 19.
        $resultat = $this->service->connecter('Lou', self::MDP, 'session-a', new DateTimeImmutable('2026-10-15 12:17:00'));

        $this->assertEquals(new DateTimeImmutable('2026-10-15 12:19:00'), $resultat);
    }

    public function testApresLAttenteLaConnexionRedevientPossible(): void
    {
        $this->echouer(5, 'Lou', 'session-a');
        // Cinq essais pendant l'attente : s'ils comptaient, ils la prolongeraient jusqu'à 12 h 29.
        for ($minute = 10; $minute <= 14; $minute++) {
            $this->service->connecter('Lou', 'mauvais', 'session-a', new DateTimeImmutable("2026-10-15 12:$minute:00"));
        }

        $resultat = $this->service->connecter('Lou', self::MDP, 'session-a', new DateTimeImmutable('2026-10-15 12:19:00'));

        $this->assertInstanceOf(Joueur::class, $resultat);
    }

    public function testLeCompteurParSessionBloqueMemeEnChangeantDIdentifiant(): void
    {
        foreach (['Ana', 'Ben', 'Cle', 'Dan', 'Eva'] as $i => $identifiant) {
            $this->service->connecter($identifiant, 'mauvais', 'session-a', $this->midi->modify("+$i minutes"));
        }

        $resultat = $this->service->connecter('Lou', self::MDP, 'session-a', new DateTimeImmutable('2026-10-15 12:05:00'));

        $this->assertEquals(new DateTimeImmutable('2026-10-15 12:19:00'), $resultat);
    }

    public function testLesMajusculesNeSeparentPasLeCompteurDUnIdentifiant(): void
    {
        // Une session par essai : seul le compteur de l'identifiant peut bloquer.
        foreach (['Lou', 'lou', 'LOU', 'lOu', 'loU'] as $i => $identifiant) {
            $this->service->connecter($identifiant, 'mauvais', "session-$i", $this->midi->modify("+$i minutes"));
        }

        $resultat = $this->service->connecter('Lou', self::MDP, 'session-neuve', new DateTimeImmutable('2026-10-15 12:05:00'));

        $this->assertEquals(new DateTimeImmutable('2026-10-15 12:19:00'), $resultat);
    }

    // Un échec par minute à partir de midi : 12 h 00, 12 h 01…
    private function echouer(int $nombre, string $identifiant, string $cleSession): void
    {
        for ($i = 0; $i < $nombre; $i++) {
            $this->service->connecter($identifiant, 'mauvais', $cleSession, $this->midi->modify("+$i minutes"));
        }
    }
}
