<?php
// Tests de AppairageService — B1 Salle d'attente — parcours 1 (arrivée et appairage) — lot 1 — A.

use PHPUnit\Framework\Attributes\DataProvider;
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

    // Doc technique § 3.6 : saisir son propre code est une erreur courante, nommée comme telle, et elle compte (cadrage § 6.6).
    public function testSaisirSonPropreCodeEstRefuse(): void
    {
        $lou = $this->service->code($this->creerJoueur('Lou'), $this->midi);

        $this->assertSame(RefusCode::PropreCode, $this->service->rejoindre($lou, $lou->codeInvitation, 'session-lou', $this->midi));
        $this->assertSame(1, $this->compterEchecs((string) $lou->id));
    }

    // Un code vaut 60 minutes : à l'heure pile de l'échéance il a déjà expiré.
    public function testUnCodeExpireEstRefuse(): void
    {
        $lou = $this->service->code($this->creerJoueur('Lou'), $this->midi);
        $mia = $this->creerJoueur('Mia');

        $this->assertSame(RefusCode::Expire, $this->service->rejoindre($mia, $lou->codeInvitation, 'session-mia', new DateTimeImmutable('2026-10-15 13:00:00')));
        $this->assertSame(1, $this->compterEchecs((string) $mia->id));
        $this->assertNull((new JoueurDepot($this->pdo))->trouverParId($mia->id)->duoId);
    }

    // Doc technique § 3.6 : faux, régénéré, ou dont le propriétaire est déjà en duo, le refus est le même et compte.
    public function testUnCodeFauxRegenereOuDUnJoueurDejaEnDuoEstPasValable(): void
    {
        $depot = new JoueurDepot($this->pdo);
        $mia = $this->creerJoueur('Mia');
        $ancien = $this->service->code($this->creerJoueur('Lou'), $this->midi);
        $this->service->regenerer($ancien, $this->midi);
        $zoe = $this->service->code($this->creerJoueur('Zoe'), $this->midi);
        $this->service->rejoindre($this->creerJoueur('Tom'), $zoe->codeInvitation, 'session-tom', $this->midi);

        foreach (['23456789', $ancien->codeInvitation, $zoe->codeInvitation] as $i => $code) {
            $this->assertSame(RefusCode::PasValable, $this->service->rejoindre($mia, $code, 'session-mia', $this->midi), "cas $i");
        }
        $this->assertSame(3, $this->compterEchecs((string) $mia->id));
        $this->assertNull($depot->trouverParId($mia->id)->duoId);
    }

    public static function textesSansFormeDeCode(): array
    {
        return [
            'vide' => [''],
            'trop court' => ['ABCDEFG'],
            'trop long' => ['ABCDEFGHJ'],
            'lettre hors alphabet' => ['ABCDEFGI'],
            'chiffre hors alphabet' => ['ABCDEFG0'],
            'texte non UTF-8' => ["ABCDEFÿþ"],
        ];
    }

    // Comme pour la connexion (#8) : un texte qui ne peut pas être un code est refusé sans toucher la base, et ne compte pas.
    #[DataProvider('textesSansFormeDeCode')]
    public function testUnTexteSansFormeDeCodeEstRefuseSansCompterDEchec(string $saisie): void
    {
        $mia = $this->creerJoueur('Mia');

        $this->assertSame(RefusCode::Format, $this->service->rejoindre($mia, $saisie, 'session-mia', $this->midi));
        $this->assertSame(0, $this->compterEchecs((string) $mia->id));
    }

    public static function saisiesDuMemeCode(): array
    {
        return [
            'minuscules' => ['k7m3qx9a'],
            'tiret d\'affichage' => ['K7M3-QX9A'],
            'espaces autour et au milieu' => [' k7m3 qx9a '],
            'tiret demi-cadratin du copier-coller' => ["K7M3\u{2013}QX9A"],
        ];
    }

    // Le code s'affiche « XXXX-XXXX » mais se range sans tiret : le copier-coller et la saisie à la main doivent marcher (cadrage § 6.6).
    #[DataProvider('saisiesDuMemeCode')]
    public function testLaSaisieDuCodeIgnoreCasseEspacesEtTirets(string $saisie): void
    {
        $lou = $this->creerJoueur('Lou');
        $this->pdo->prepare("UPDATE joueur SET code_invitation = 'K7M3QX9A', date_expiration_code = '2026-10-15 13:00:00' WHERE id = ?")->execute([$lou->id]);

        $this->assertInstanceOf(Duo::class, $this->service->rejoindre($this->creerJoueur('Mia'), $saisie, 'session-mia', $this->midi));
    }

    // Cadrage § 6.6 : 5 échecs en 15 minutes, puis 15 minutes d'attente pendant lesquelles même le bon code est refusé.
    public function testCinqEchecsDeCodeDeclenchentUneAttenteMemePourLeBonCode(): void
    {
        $lou = $this->service->code($this->creerJoueur('Lou'), $this->midi);
        $mia = $this->creerJoueur('Mia');
        for ($i = 0; $i < 5; $i++) {
            $this->service->rejoindre($mia, '23456789', 'session-mia', $this->midi);
        }

        $attente = $this->service->rejoindre($mia, $lou->codeInvitation, 'session-mia', $this->midi->modify('+1 minute'));

        $this->assertEquals(new DateTimeImmutable('2026-10-15 12:15:00'), $attente);
        $this->assertNull((new JoueurDepot($this->pdo))->trouverParId($mia->id)->duoId);
    }

    // Un joueur déjà en duo n'a pas de saisie en B1 : un POST forgé est refusé sans compter, et ne touche à rien.
    public function testUnJoueurDejaEnDuoNePeutPasRejoindreUnAutreDuo(): void
    {
        $depot = new JoueurDepot($this->pdo);
        $lou = $this->service->code($this->creerJoueur('Lou'), $this->midi);
        $tom = $this->creerJoueur('Tom');
        $this->service->rejoindre($tom, $lou->codeInvitation, 'session-tom', $this->midi);
        $mia = $this->service->code($this->creerJoueur('Mia'), $this->midi);

        $refus = $this->service->rejoindre($depot->trouverParId($tom->id), $mia->codeInvitation, 'session-tom', $this->midi);

        $this->assertSame(RefusCode::DejaEnDuo, $refus);
        $this->assertSame(0, $this->compterEchecs((string) $tom->id));
        $this->assertSame($mia->codeInvitation, $depot->trouverParId($mia->id)->codeInvitation);
    }

    // Dépôt qui ment : il rend le propriétaire tel qu'il était avant que la base change, comme le ferait une lecture faite juste avant une autre requête.
    // Même technique que le dépôt aveugle de CompteServiceTest : seul le verrou pris dans la transaction peut alors rattraper l'erreur.
    private function serviceAvecProprietairePerime(Joueur $perime): AppairageService
    {
        $menteur = new class ($this->pdo, $perime) extends JoueurDepot {
            public function __construct(PDO $pdo, private Joueur $perime)
            {
                parent::__construct($pdo);
            }

            public function trouverParCode(string $code): ?Joueur
            {
                return $this->perime;
            }
        };
        return new AppairageService($this->pdo, $menteur, new DuoDepot($this->pdo), new TentativeDepot($this->pdo));
    }

    private function compterDuos(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM duo')->fetchColumn();
    }

    // Course : le propriétaire régénère son code pendant qu'un invité saisit l'ancien. La revérification sous verrou refuse, sans duo à moitié formé.
    public function testUnCodeRegenereEntreLaLectureEtLeVerrouEstRefuse(): void
    {
        $lou = $this->service->code($this->creerJoueur('Lou'), $this->midi);
        $this->service->regenerer($lou, $this->midi);
        $mia = $this->creerJoueur('Mia');

        $refus = $this->serviceAvecProprietairePerime($lou)->rejoindre($mia, $lou->codeInvitation, 'session-mia', $this->midi);

        $this->assertSame(RefusCode::PasValable, $refus);
        $this->assertSame(0, $this->compterDuos());
        $this->assertSame(1, $this->compterEchecs((string) $mia->id));
    }

    // Course : un autre invité forme le duo du propriétaire pendant la saisie. Le second invité est refusé et le duo existant reste intact.
    public function testUnProprietaireEntreEnDuoEntreLaLectureEtLeVerrouEstRefuse(): void
    {
        $lou = $this->service->code($this->creerJoueur('Lou'), $this->midi);
        $tom = $this->creerJoueur('Tom');
        $this->service->rejoindre($tom, $lou->codeInvitation, 'session-tom', $this->midi);
        $mia = $this->creerJoueur('Mia');

        $refus = $this->serviceAvecProprietairePerime($lou)->rejoindre($mia, $lou->codeInvitation, 'session-mia', $this->midi);

        $this->assertSame(RefusCode::PasValable, $refus);
        $this->assertSame(1, $this->compterDuos());
        $this->assertNull((new JoueurDepot($this->pdo))->trouverParId($mia->id)->duoId);
    }

    private function compterEchecs(string $cle): int
    {
        $requete = $this->pdo->prepare("SELECT COUNT(*) FROM tentative WHERE type = 'code' AND cle = ?");
        $requete->execute([$cle]);
        return (int) $requete->fetchColumn();
    }
}
