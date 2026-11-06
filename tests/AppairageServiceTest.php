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
            'texte non UTF-8' => ["ABCDEF\xFF\xFE"],
            // mb_strtoupper en ferait « SSABCDEF », un code valable : seules les lettres ASCII se mettent en majuscules.
            'lettre dont la majuscule compte deux caractères' => ['ßABCDEF'],
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

    // Cadrage § 6.6 : comptage par joueur ET par session. Changer de session ne libère pas le joueur, changer de joueur ne libère pas la session.
    public function testChangerDeSessionOuDeJoueurNeRemetPasLeCompteurAZero(): void
    {
        $lou = $this->service->code($this->creerJoueur('Lou'), $this->midi);
        $mia = $this->creerJoueur('Mia');
        $zoe = $this->creerJoueur('Zoe');
        for ($i = 0; $i < 5; $i++) {
            $this->service->rejoindre($mia, '23456789', 'session-mia', $this->midi);
        }
        $apres = $this->midi->modify('+1 minute');

        $this->assertInstanceOf(DateTimeImmutable::class, $this->service->rejoindre($mia, $lou->codeInvitation, 'autre-session', $apres));
        $this->assertInstanceOf(DateTimeImmutable::class, $this->service->rejoindre($zoe, $lou->codeInvitation, 'session-mia', $apres));
    }

    // Double clic ou deux appareils : la deuxième requête a lu l'invité sans duo, mais le duo s'est formé avant son verrou.
    // Ce n'est pas une erreur de saisie : elle ne compte pas dans la limite de 5.
    public function testUnInviteDejaEnDuoSousVerrouEstRefuseSansCompterDEchec(): void
    {
        $lou = $this->service->code($this->creerJoueur('Lou'), $this->midi);
        $mia = $this->service->code($this->creerJoueur('Mia'), $this->midi);
        $tom = $this->creerJoueur('Tom');
        $this->service->rejoindre($tom, $lou->codeInvitation, 'session-tom', $this->midi);

        $refus = $this->service->rejoindre($tom, $mia->codeInvitation, 'session-tom', $this->midi);

        $this->assertSame(RefusCode::DejaEnDuo, $refus);
        $this->assertSame(0, $this->compterEchecs((string) $tom->id));
    }

    // Course : l'invité entre en duo pendant que le propriétaire régénère. Le code ne doit pas être recréé sur un joueur déjà en duo.
    public function testUnCodeNeEstJamaisEcritSurUnJoueurDejaEnDuo(): void
    {
        $lou = $this->service->code($this->creerJoueur('Lou'), $this->midi);
        $this->service->rejoindre($this->creerJoueur('Tom'), $lou->codeInvitation, 'session-tom', $this->midi);

        $relu = $this->service->regenerer($lou, $this->midi);

        $this->assertNull($relu->codeInvitation);
        $this->assertNotNull($relu->duoId);
    }

    // Deux appareils ouvrent B1 en même temps, sans code : le second ne doit pas écraser celui que le premier affiche,
    // sinon le partenaire saisirait un code qui n'existe plus (cadrage § 6.7).
    public function testDeuxPremiersAffichagesSimultanesMontrentLeMemeCode(): void
    {
        $lou = $this->creerJoueur('Lou');

        $premier = $this->service->code($lou, $this->midi);
        $second = $this->service->code($lou, $this->midi);

        $this->assertSame($premier->codeInvitation, $second->codeInvitation);
    }

    // Duo formé par saisie de code : renvoie [propriétaire, invité] relus, onboarding à refaire pour les deux.
    private function formerDuo(string $a = 'Lou', string $b = 'Mia', ?DateTimeImmutable $quand = null): array
    {
        $quand ??= $this->midi;
        $proprietaire = $this->service->code($this->creerJoueur($a), $quand);
        $invite = $this->creerJoueur($b);
        $this->service->rejoindre($invite, $proprietaire->codeInvitation, 'session-' . $b, $quand);
        return [$this->relire($proprietaire), $this->relire($invite)];
    }

    private function relire(Joueur $joueur): Joueur
    {
        return (new JoueurDepot($this->pdo))->trouverParId($joueur->id);
    }

    private function dateActivation(Joueur $joueur): ?DateTimeImmutable
    {
        return (new DuoDepot($this->pdo))->trouverParId($joueur->duoId)->dateActivation;
    }

    // Cadrage § 8.5 : la formation remet l'onboarding à 0 pour les deux joueurs et laisse l'activation nulle.
    public function testLaFormationLaisseLOnboardingAFaire(): void
    {
        [$lou, $mia] = $this->formerDuo();

        $this->assertFalse($lou->onboardingVu);
        $this->assertFalse($mia->onboardingVu);
        $this->assertNull($this->dateActivation($lou));
    }

    // « Passer » et le bouton du dernier écran appellent la même opération ; le premier à finir attend l'autre.
    public function testLePremierQuiTermineAttendLePartenaire(): void
    {
        [$lou, $mia] = $this->formerDuo();

        $this->service->terminerOnboarding($lou, $this->midi->modify('+1 minute'));

        $this->assertTrue($this->relire($lou)->onboardingVu);
        $this->assertFalse($this->relire($mia)->onboardingVu);
        $this->assertNull($this->dateActivation($lou));
    }

    // Le deuxième à finir active le duo à l'heure de l'horloge injectée.
    public function testLeDeuxiemeQuiTermineActiveLeDuo(): void
    {
        [$lou, $mia] = $this->formerDuo();
        $this->service->terminerOnboarding($lou, $this->midi->modify('+1 minute'));

        $this->service->terminerOnboarding($mia, $this->midi->modify('+2 minutes'));

        $this->assertEquals($this->midi->modify('+2 minutes'), $this->dateActivation($lou));
    }

    // Cadrage § 4.2 : un duo dont les deux joueurs finissent après 12 h est activé après 12 h, même formé le matin.
    public function testUnDuoActiveApresMidiPorteUneActivationPosterieureAMidi(): void
    {
        [$lou, $mia] = $this->formerDuo('Lou', 'Mia', new DateTimeImmutable('2026-10-15 09:00:00'));
        $this->service->terminerOnboarding($lou, new DateTimeImmutable('2026-10-15 11:50:00'));

        $this->service->terminerOnboarding($mia, new DateTimeImmutable('2026-10-15 12:10:00'));

        $this->assertGreaterThan(new DateTimeImmutable('2026-10-15 12:00:00'), $this->dateActivation($lou));
    }

    // Deux appareils : terminer deux fois ne change rien, ni l'état ni la date d'activation.
    public function testTerminerDeuxFoisNeChangeRien(): void
    {
        [$lou, $mia] = $this->formerDuo();
        $this->service->terminerOnboarding($lou, $this->midi->modify('+1 minute'));
        $this->service->terminerOnboarding($lou, $this->midi->modify('+2 minutes'));
        $this->assertNull($this->dateActivation($lou));

        $this->service->terminerOnboarding($mia, $this->midi->modify('+3 minutes'));
        $this->service->terminerOnboarding($mia, $this->midi->modify('+4 minutes'));
        $this->service->terminerOnboarding($lou, $this->midi->modify('+5 minutes'));

        $this->assertEquals($this->midi->modify('+3 minutes'), $this->dateActivation($lou));
    }

    // Doc technique § 3.6 : l'étape d'accès dans chaque combinaison de colonnes.
    public function testLEtapeDAccesSuitLesColonnes(): void
    {
        $this->assertSame(EtapeAcces::SansDuo, $this->service->etapeAcces($this->creerJoueur('Zoe')));

        [$lou, $mia] = $this->formerDuo();
        $this->assertSame(EtapeAcces::OnboardingAFaire, $this->service->etapeAcces($lou));
        $this->assertSame(EtapeAcces::OnboardingAFaire, $this->service->etapeAcces($mia));

        $this->service->terminerOnboarding($lou, $this->midi);
        $this->assertSame(EtapeAcces::Attente, $this->service->etapeAcces($this->relire($lou)));
        $this->assertSame(EtapeAcces::OnboardingAFaire, $this->service->etapeAcces($this->relire($mia)));

        $this->service->terminerOnboarding($mia, $this->midi);
        $this->assertSame(EtapeAcces::AccesAuJeu, $this->service->etapeAcces($this->relire($lou)));
        $this->assertSame(EtapeAcces::AccesAuJeu, $this->service->etapeAcces($this->relire($mia)));
    }

    // F2 : la dissolution remet les deux joueurs sans duo, à 5 PV et à l'onboarding ; seul le partenaire est à prévenir, et le duo disparaît.
    public function testDissoudreRemetLesDeuxJoueursAZeroEtPrevientLePartenaire(): void
    {
        [$lou, $mia] = $this->formerDuo();
        $this->service->terminerOnboarding($lou, $this->midi->modify('+1 minute'));
        $this->service->terminerOnboarding($mia, $this->midi->modify('+2 minutes'));
        $this->pdo->exec('UPDATE joueur SET pv = 2');

        $this->assertTrue($this->service->dissoudre($this->relire($lou)));

        foreach ([$lou, $mia] as $joueur) {
            $relu = $this->relire($joueur);
            $this->assertNull($relu->duoId);
            $this->assertSame(5, $relu->pv);
            $this->assertFalse($relu->onboardingVu);
        }
        $this->assertFalse($this->relire($lou)->dissolutionASignaler);
        $this->assertTrue($this->relire($mia)->dissolutionASignaler);
        $this->assertNull((new DuoDepot($this->pdo))->trouverParId($lou->duoId));
    }

    // Un joueur sans duo, ou relu sans duo alors que son objet est périmé (duo encore renseigné), ne déclenche rien et ne pose aucun marqueur.
    public function testDissoudreSansDuoALaRelectureNeFaitRien(): void
    {
        [$lou, $mia] = $this->formerDuo();
        $this->service->dissoudre($lou);
        $this->pdo->exec('UPDATE joueur SET dissolution_a_signaler = 0');

        $this->assertFalse($this->service->dissoudre($lou));
        $this->assertFalse($this->service->dissoudre($mia));
        $this->assertFalse($this->service->dissoudre($this->creerJoueur('Zoe')));
        $this->assertFalse($this->relire($mia)->dissolutionASignaler);
    }

    // Après dissolution, un nouvel appairage reforme un duo et remet l'onboarding à zéro.
    public function testUnNouvelAppairageApresDissolutionReformeUnDuo(): void
    {
        [$lou, $mia] = $this->formerDuo();
        $this->service->terminerOnboarding($lou, $this->midi);
        $this->service->dissoudre($lou);
        $code = $this->service->code($this->relire($lou), $this->midi->modify('+1 hour'));

        $duo = $this->service->rejoindre($this->relire($mia), $code->codeInvitation, 'session-mia', $this->midi->modify('+1 hour'));

        $this->assertInstanceOf(Duo::class, $duo);
        $this->assertSame($duo->id, $this->relire($lou)->duoId);
        $this->assertFalse($this->relire($lou)->onboardingVu);
        $this->assertFalse($this->relire($mia)->onboardingVu);
    }

    // Un nouvel appairage après dissolution remet tout à 0 : l'onboarding est refait, même pour un joueur qui l'avait fini.
    public function testUnNouvelAppairageApresDissolutionRemetToutAZero(): void
    {
        [$lou, $mia] = $this->formerDuo();
        $this->service->terminerOnboarding($lou, $this->midi);
        $this->service->terminerOnboarding($mia, $this->midi);
        // Dissolution : la suppression du duo remet les joueurs sans duo (ON DELETE SET NULL), onboarding_vu reste à 1.
        $this->pdo->exec('DELETE FROM duo');

        $zoe = $this->service->code($this->creerJoueur('Zoe'), $this->midi);
        $this->service->rejoindre($this->relire($lou), $zoe->codeInvitation, 'session-lou', $this->midi);

        $lou = $this->relire($lou);
        $this->assertFalse($lou->onboardingVu);
        $this->assertFalse($this->relire($zoe)->onboardingVu);
        $this->assertSame(EtapeAcces::OnboardingAFaire, $this->service->etapeAcces($lou));
        $this->assertNull($this->dateActivation($lou));
    }

    // Sans duo, il n'y a rien à terminer : aucune écriture, aucun duo créé ni activé.
    public function testTerminerSansDuoNeChangeRien(): void
    {
        $zoe = $this->creerJoueur('Zoe');

        $this->service->terminerOnboarding($zoe, $this->midi);

        $this->assertFalse($this->relire($zoe)->onboardingVu);
        $this->assertSame(0, $this->compterDuos());
    }

    // Un duo avec un seul joueur est une base incohérente : l'étape échoue franchement au lieu d'attendre indéfiniment.
    public function testUnDuoSansPartenaireEchoueAuLieuDAttendre(): void
    {
        [$lou, $mia] = $this->formerDuo();
        $this->service->terminerOnboarding($lou, $this->midi);
        $this->pdo->prepare('DELETE FROM joueur WHERE id = ?')->execute([$mia->id]);

        $this->expectException(LogicException::class);
        $this->service->etapeAcces($this->relire($lou));
    }

    // Terminer face à un duo incohérent marque le joueur, mais n'active rien et ne plante pas.
    public function testTerminerSansPartenaireNActivePasLeDuo(): void
    {
        [$lou, $mia] = $this->formerDuo();
        $this->pdo->prepare('DELETE FROM joueur WHERE id = ?')->execute([$mia->id]);

        $this->service->terminerOnboarding($lou, $this->midi);

        $this->assertTrue($this->relire($lou)->onboardingVu);
        $this->assertNull($this->dateActivation($lou));
    }

    // Le partenaire est relu sous verrou et doit être du même duo : un partenaire déjà passé dans un autre duo (onboarding fini) n'active pas celui-ci.
    public function testUnPartenaireDUnAutreDuoNActivePasLeDuo(): void
    {
        [$lou, $mia] = $this->formerDuo();
        $this->service->terminerOnboarding($mia, $this->midi);
        $autreDuo = (new DuoDepot($this->pdo))->creer($this->midi);
        $this->pdo->prepare('UPDATE joueur SET duo_id = ? WHERE id = ?')->execute([$autreDuo, $mia->id]);

        $this->service->terminerOnboarding($lou, $this->midi->modify('+1 minute'));

        $this->assertNull($this->dateActivation($this->relire($lou)));
    }

    // Un joueur relu avant la dissolution (objet périmé, duo encore renseigné) ne marque rien : la ligne verrouillée fait foi.
    public function testUnJoueurPerimeSansDuoEnBaseNeChangeRien(): void
    {
        [$lou] = $this->formerDuo();
        $this->pdo->exec('DELETE FROM duo');

        $this->service->terminerOnboarding($lou, $this->midi);

        $this->assertFalse($this->relire($lou)->onboardingVu);
    }

    private function compterEchecs(string $cle): int
    {
        $requete = $this->pdo->prepare("SELECT COUNT(*) FROM tentative WHERE type = 'code' AND cle = ?");
        $requete->execute([$cle]);
        return (int) $requete->fetchColumn();
    }
}
