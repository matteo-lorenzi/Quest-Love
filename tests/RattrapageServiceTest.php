<?php
// Tests de RattrapageService — D3 Duo dissous (écran evenement), C1 état 5, C1 journée blanche « l'un de vous n'a pas choisi » — parcours 2 (journée de jeu) — lot 1 — B.

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RattrapageServiceTest extends TestCase
{
    private PDO $pdo;
    private AppairageService $appairage;
    private RattrapageService $rattrapage;
    private DateTimeImmutable $midi;

    private static int $categorie;
    private static int $defi;

    // Le test du schéma vide la bibliothèque : un défi à proposer, une fois pour la classe (comme TirageServiceTest).
    public static function setUpBeforeClass(): void
    {
        $pdo = Bdd::connexion();
        $pdo->exec("INSERT INTO categorie (libelle) VALUES ('Catégorie du test de rattrapage')");
        self::$categorie = (int) $pdo->lastInsertId();
        $pdo->prepare('INSERT INTO defi (titre, description, categorie_id) VALUES (?, ?, ?)')->execute(['Défi de rattrapage', 'Pour le test.', self::$categorie]);
        self::$defi = (int) $pdo->lastInsertId();
    }

    // Le libellé de catégorie est unique : la base est vidée d'abord, les propositions retiennent le défi.
    public static function tearDownAfterClass(): void
    {
        $pdo = Bdd::connexion();
        viderLaBase($pdo);
        $pdo->prepare('DELETE FROM defi WHERE id = ?')->execute([self::$defi]);
        $pdo->prepare('DELETE FROM categorie WHERE id = ?')->execute([self::$categorie]);
    }

    protected function setUp(): void
    {
        $this->pdo = Bdd::connexion();
        viderLaBase($this->pdo);
        $this->appairage = AppairageService::construire($this->pdo);
        $this->rattrapage = RattrapageService::construire($this->pdo);
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

    // Lou et Mia en duo, relus. Renvoie [Lou, Mia].
    private function duoForme(): array
    {
        $depot = new JoueurDepot($this->pdo);
        $lou = $this->appairage->code($depot->trouverParId($depot->ajouter('Lou', 'Lou', 'empreinte', $this->midi)), $this->midi);
        $mia = $depot->trouverParId($depot->ajouter('Mia', 'Mia', 'empreinte', $this->midi));
        $this->appairage->rejoindre($mia, $lou->codeInvitation, 'session-mia', $this->midi);
        return [$depot->trouverParId($lou->id), $depot->trouverParId($mia->id)];
    }

    // L'émetteur choisit le défi du destinataire à cet instant : sa mission, non révélée. Renvoie son numéro.
    // La date limite écrite ici est volontairement fausse : c'est la révélation qui la fixe.
    private function choisir(Joueur $emetteur, Joueur $destinataire, DateTimeImmutable $instant): int
    {
        $tirages = new TirageDepot($this->pdo);
        $tirages->creer($emetteur->duoId, $emetteur->id, $destinataire->id, $instant, [self::$defi]);
        return (new MissionDepot($this->pdo))->ajouter($tirages->trouverParEmetteurEtJour($emetteur->id, $instant), self::$defi, $instant, $instant);
    }

    private function mission(int $id): Mission
    {
        return (new MissionDepot($this->pdo))->trouverParId($id);
    }

    // C1 état 4, spec #59 : tant que le partenaire n'a pas choisi, rien n'est révélé, pour aucun des deux.
    public function testUnSeulChoixNeReveleRien(): void
    {
        [$lou, $mia] = $this->duoForme();
        $instant = new DateTimeImmutable('2026-10-20 09:00:00');
        $id = $this->choisir($lou, $mia, $instant);

        $this->rattrapage->rattraper($mia, $instant);
        $this->rattrapage->rattraper($lou, $instant);

        $this->assertSame(StatutMission::AttribueNonRevele, $this->mission($id)->statut);
        $this->assertNull($this->mission($id)->dateRevelation);
    }

    // Jours ordinaire, de 25 h et de 23 h : minuit vient du calendrier (doc technique § 2.4).
    public static function joursDeRevelation(): array
    {
        return [
            'jour ordinaire' => ['2026-10-20 09:00:00', '2026-10-21T00:00:00+02:00'],
            'journée de 25 h' => ['2026-10-25 09:00:00', '2026-10-26T00:00:00+01:00'],
            'journée de 23 h' => ['2027-03-28 09:00:00', '2027-03-29T00:00:00+02:00'],
        ];
    }

    // C1 état 5, spec #59 récit 1 : dès le second choix, les deux missions sont révélées, à relever avant minuit.
    #[DataProvider('joursDeRevelation')]
    public function testLeSecondChoixReveleLesDeuxMissionsJusquAMinuit(string $instant, string $minuit): void
    {
        [$lou, $mia] = $this->duoForme();
        $choix = new DateTimeImmutable($instant);
        $pourMia = $this->choisir($lou, $mia, $choix);
        $pourLou = $this->choisir($mia, $lou, $choix->modify('+10 minutes'));
        $rattrapage = $choix->modify('+20 minutes');

        $this->rattrapage->rattraper($lou, $rattrapage);

        foreach ([$pourMia, $pourLou] as $id) {
            $this->assertSame(StatutMission::EnCours, $this->mission($id)->statut);
            $this->assertEquals($rattrapage, $this->mission($id)->dateRevelation);
            $this->assertSame($minuit, $this->mission($id)->dateLimite->format(DATE_ATOM));
        }
    }

    // Spec #59 récit 5 : un second rattrapage (autre appareil) ne révèle pas une seconde fois.
    public function testDeuxRattrapagesNeRevelentQuUneFois(): void
    {
        [$lou, $mia] = $this->duoForme();
        $choix = new DateTimeImmutable('2026-10-20 09:00:00');
        $id = $this->choisir($lou, $mia, $choix);
        $this->choisir($mia, $lou, $choix);

        $this->rattrapage->rattraper($mia, $choix);
        $this->rattrapage->rattraper($lou, $choix->modify('+1 hour'));

        $this->assertEquals($choix, $this->mission($id)->dateRevelation);
    }

    // C1 état 5 : l'accueil annonce le défi sans le découvrir ; seule C3 pose date_decouverte (doc technique § 3.4).
    public function testLAccueilAnnonceLeDefiSansLeDecouvrir(): void
    {
        [$lou, $mia] = $this->duoForme();
        $choix = new DateTimeImmutable('2026-10-20 09:00:00');
        $participations = new ParticipationDepot($this->pdo);
        foreach ([$lou, $mia] as $joueur) {
            $participations->ajouter($joueur->duoId, $joueur->id, $choix, Reponse::Oui, $choix);
        }
        $id = $this->choisir($lou, $mia, $choix);
        $this->choisir($mia, $lou, $choix);
        $this->rattrapage->rattraper($mia, $choix);

        $journee = new JourneeService($participations, new DuoDepot($this->pdo), new MissionDepot($this->pdo));
        $this->assertSame(EtatAccueil::DefiRevele, $journee->etatAccueil($mia, $choix));
        $this->assertNull($this->mission($id)->dateDecouverte);
    }
    // Lou et Mia ont dit oui ce matin : l'accueil de chacun peut alors être lu.
    private function deuxOui(Joueur $lou, Joueur $mia, DateTimeImmutable $instant): JourneeService
    {
        $participations = new ParticipationDepot($this->pdo);
        foreach ([$lou, $mia] as $joueur) {
            $participations->ajouter($joueur->duoId, $joueur->id, $instant, Reponse::Oui, $instant);
        }
        return new JourneeService($participations, new DuoDepot($this->pdo), new MissionDepot($this->pdo));
    }

    // C1 « aucun jeu », spec #59 récit 4 : à 12 h, la mission de celui qui avait choisi est annulée, sans perte de PV, et l'accueil des deux dit que l'un n'a pas choisi.
    public function testUnSeulChoixAMidiAnnuleLaMissionSansPerteDePv(): void
    {
        [$lou, $mia] = $this->duoForme();
        $choix = new DateTimeImmutable('2026-10-20 09:00:00');
        $journee = $this->deuxOui($lou, $mia, $choix);
        $id = $this->choisir($lou, $mia, $choix);
        $midi = new DateTimeImmutable('2026-10-20 12:00:00');

        // Celui qui avait choisi charge la page : l'annulation doit passer la sortie rapide, même sans mission reçue.
        $this->assertSame([], $this->rattrapage->rattraper($lou, $midi));

        $this->assertSame(StatutMission::Annule, $this->mission($id)->statut);
        $joueurs = new JoueurDepot($this->pdo);
        $this->assertSame(Joueur::PV_DEPART, $joueurs->trouverParId($lou->id)->pv);
        $this->assertSame(Joueur::PV_DEPART, $joueurs->trouverParId($mia->id)->pv);
        $this->assertSame(EtatAccueil::JourneeBlancheSansChoix, $journee->etatAccueil($lou, $midi));
        $this->assertSame(EtatAccueil::JourneeBlancheSansChoix, $journee->etatAccueil($mia, $midi));
    }

    // Avant 12 h, la mission attend le choix de l'autre : rien n'est annulé.
    public function testAvantMidiLaMissionNEstPasAnnulee(): void
    {
        [$lou, $mia] = $this->duoForme();
        $id = $this->choisir($lou, $mia, new DateTimeImmutable('2026-10-20 09:00:00'));

        $this->rattrapage->rattraper($lou, new DateTimeImmutable('2026-10-20 11:59:00'));

        $this->assertSame(StatutMission::AttribueNonRevele, $this->mission($id)->statut);
    }

    // Glossaire, Annulation : le défi annulé n'est pas compté comme reçu, il peut être reproposé au destinataire dès le lendemain.
    public function testLeDefiAnnulePeutEtreReproposeAuDestinataire(): void
    {
        [$lou, $mia] = $this->duoForme();
        $this->choisir($lou, $mia, new DateTimeImmutable('2026-10-20 09:00:00'));

        $this->rattrapage->rattraper($mia, new DateTimeImmutable('2026-10-20 12:30:00'));

        $this->assertSame([], (new MissionDepot($this->pdo))->recusPar($mia->id));
    }

    // Spec #59 récit 5 : le second rattrapage (autre appareil, lendemain) ne retouche pas une mission déjà annulée.
    public function testDeuxRattrapagesNAnnulentQuUneFois(): void
    {
        [$lou, $mia] = $this->duoForme();
        $id = $this->choisir($lou, $mia, new DateTimeImmutable('2026-10-20 09:00:00'));

        $this->rattrapage->rattraper($lou, new DateTimeImmutable('2026-10-20 12:00:00'));
        $this->assertSame([], $this->rattrapage->rattraper($mia, new DateTimeImmutable('2026-10-21 08:00:00')));

        $this->assertSame(StatutMission::Annule, $this->mission($id)->statut);
        $this->assertSame(Joueur::PV_DEPART, (new JoueurDepot($this->pdo))->trouverParId($mia->id)->pv);
    }

    // Doc technique § 3.2 : révélation avant annulation. Deux choix rattrapés après 12 h sont révélés, jamais annulés.
    public function testUneMissionReveleeNEstJamaisAnnulee(): void
    {
        [$lou, $mia] = $this->duoForme();
        $choix = new DateTimeImmutable('2026-10-20 09:00:00');
        $pourMia = $this->choisir($lou, $mia, $choix);
        $pourLou = $this->choisir($mia, $lou, $choix);

        $this->rattrapage->rattraper($mia, new DateTimeImmutable('2026-10-20 15:00:00'));

        $this->assertSame(StatutMission::EnCours, $this->mission($pourMia)->statut);
        $this->assertSame(StatutMission::EnCours, $this->mission($pourLou)->statut);
    }
}
