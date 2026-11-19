<?php
// Tests de MissionService — C3 Défi reçu, découverte et validation — parcours 2 (journée de jeu) — lot 1 — B.

use PHPUnit\Framework\TestCase;

class MissionServiceTest extends TestCase
{
    private PDO $pdo;
    private MissionDepot $missions;
    private MissionService $service;
    private Joueur $lou;
    private Joueur $mia;
    private DateTimeImmutable $matin;

    private static int $categorie;
    private static int $defi;

    // Le test du schéma vide la bibliothèque : un défi pour la classe (comme RattrapageServiceTest).
    public static function setUpBeforeClass(): void
    {
        $pdo = Bdd::connexion();
        $pdo->exec("INSERT INTO categorie (libelle) VALUES ('Catégorie du test de mission')");
        self::$categorie = (int) $pdo->lastInsertId();
        $pdo->prepare('INSERT INTO defi (titre, description, categorie_id) VALUES (?, ?, ?)')->execute(['Défi de mission', 'Pour le test.', self::$categorie]);
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

    // Lou et Mia en duo ; ce matin, Lou a choisi le défi de Mia.
    protected function setUp(): void
    {
        $this->pdo = Bdd::connexion();
        viderLaBase($this->pdo);
        $this->missions = new MissionDepot($this->pdo);
        $this->service = MissionService::construire($this->pdo);
        $this->matin = new DateTimeImmutable('2026-10-20 09:00:00');
        $appairage = AppairageService::construire($this->pdo);
        $depot = new JoueurDepot($this->pdo);
        $lou = $appairage->code($depot->trouverParId($depot->ajouter('Lou', 'Lou', 'empreinte', $this->matin)), $this->matin);
        $mia = $depot->trouverParId($depot->ajouter('Mia', 'Mia', 'empreinte', $this->matin));
        $appairage->rejoindre($mia, $lou->codeInvitation, 'session-mia', $this->matin);
        $this->lou = $depot->trouverParId($lou->id);
        $this->mia = $depot->trouverParId($mia->id);
    }

    // Mission de l'émetteur vers le destinataire, choisie ce matin ; révélée à 10 h si demandé (comme le ferait le rattrapage).
    private function missionPour(Joueur $emetteur, Joueur $destinataire, bool $revelee = true): int
    {
        $tirages = new TirageDepot($this->pdo);
        $tirages->creer($emetteur->duoId, $emetteur->id, $destinataire->id, $this->matin, [self::$defi]);
        $minuit = new DateTimeImmutable('2026-10-21 00:00:00');
        $id = $this->missions->ajouter($tirages->trouverParEmetteurEtJour($emetteur->id, $this->matin), self::$defi, $this->matin, $minuit);
        if ($revelee) {
            $this->missions->reveler($id, $this->matin->modify('+1 hour'), $minuit);
        }
        return $id;
    }

    // Spec #60 récits 8 et 9 : la première ouverture de C3 par le destinataire pose la découverte, les suivantes ne la déplacent pas.
    public function testLaDecouverteEstPoseeUneSeuleFoisAuPremierAffichage(): void
    {
        $id = $this->missionPour($this->lou, $this->mia);
        $premier = new DateTimeImmutable('2026-10-20 10:30:00');

        $this->assertSame($id, $this->service->decouvrir($this->mia, $premier)->id);
        $this->service->decouvrir($this->mia, $premier->modify('+1 hour'));

        $this->assertEquals($premier, $this->missions->trouverParId($id)->dateDecouverte);
    }

    // Le duo de la session borne la lecture : l'émetteur n'ouvre pas le défi qu'il a envoyé comme s'il l'avait reçu, et ne le découvre pas.
    public function testLEmetteurNeDecouvrePasLeDefiQuIlAEnvoye(): void
    {
        $id = $this->missionPour($this->lou, $this->mia);

        $this->assertNull($this->service->decouvrir($this->lou, new DateTimeImmutable('2026-10-20 10:30:00')));
        $this->assertNull($this->missions->trouverParId($id)->dateDecouverte);
    }

    // Une mission non révélée n'est pas lisible sur C3 (doc technique § 3.4).
    public function testUneMissionNonReveleeNEstPasDecouverte(): void
    {
        $id = $this->missionPour($this->lou, $this->mia, false);

        $this->assertNull($this->service->decouvrir($this->mia, new DateTimeImmutable('2026-10-20 10:30:00')));
        $this->assertNull($this->missions->trouverParId($id)->dateDecouverte);
    }

    // Spec #60 récits 2 et 3 : la déclaration passe la mission à « valide », datée par l'horloge reçue.
    public function testLaValidationPasseLaMissionAValideAvecSaDate(): void
    {
        $id = $this->missionPour($this->lou, $this->mia);
        $instant = new DateTimeImmutable('2026-10-20 18:00:00');

        $this->service->valider($this->mia, $id, $instant);

        $mission = $this->missions->trouverParId($id);
        $this->assertSame(StatutMission::Valide, $mission->statut);
        $this->assertEquals($instant, $mission->dateValidation);
    }

    // Spec #60 récit 4 : depuis deux appareils, seule la première validation est enregistrée.
    public function testUneDoubleValidationNEnregistreQuUneValidation(): void
    {
        $id = $this->missionPour($this->lou, $this->mia);
        $premiere = new DateTimeImmutable('2026-10-20 18:00:00');

        $this->service->valider($this->mia, $id, $premiere);
        $this->service->valider($this->mia, $id, $premiere->modify('+5 minutes'));

        $this->assertEquals($premiere, $this->missions->trouverParId($id)->dateValidation);
    }

    // Doc technique § 3.4 : seule une mission en cours se valide ; expirée ou annulée, elle garde son statut.
    public function testLaValidationDUneMissionExpireeOuAnnuleeEstRefusee(): void
    {
        $expiree = $this->missionPour($this->lou, $this->mia);
        $this->missions->changerStatut($expiree, StatutMission::Expire);
        $annulee = $this->missionPour($this->mia, $this->lou, false);
        $this->missions->changerStatut($annulee, StatutMission::Annule);
        $instant = new DateTimeImmutable('2026-10-20 18:00:00');

        $this->service->valider($this->mia, $expiree, $instant);
        $this->service->valider($this->lou, $annulee, $instant);

        foreach ([$expiree => StatutMission::Expire, $annulee => StatutMission::Annule] as $id => $statut) {
            $mission = $this->missions->trouverParId($id);
            $this->assertSame($statut, $mission->statut);
            $this->assertNull($mission->dateValidation);
        }
    }

    // Seul le destinataire déclare son défi réalisé : l'émetteur ne valide pas le défi qu'il a envoyé.
    public function testLaValidationParUnAutreJoueurQueLeDestinataireEstRefusee(): void
    {
        $id = $this->missionPour($this->lou, $this->mia);

        $this->service->valider($this->lou, $id, new DateTimeImmutable('2026-10-20 18:00:00'));

        $this->assertSame(StatutMission::EnCours, $this->missions->trouverParId($id)->statut);
    }

    // Cadrage § 4.1 : le défi se relève avant minuit ; une page restée ouverte ne le valide pas après, même avant le passage de l'expiration.
    public function testLaValidationApresMinuitEstRefusee(): void
    {
        $id = $this->missionPour($this->lou, $this->mia);

        $this->service->valider($this->mia, $id, new DateTimeImmutable('2026-10-21 00:00:00'));

        $this->assertSame(StatutMission::EnCours, $this->missions->trouverParId($id)->statut);
    }
}
