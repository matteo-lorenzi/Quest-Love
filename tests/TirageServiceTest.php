<?php
// Tests de TirageService — C2 Tirage, étapes « éventail » et « carte piochée », et C1 états 3 et 4 — parcours 2 (journée de jeu) — lot 1 — B. Base locale, $maintenant injecté.

use PHPUnit\Framework\TestCase;

class TirageServiceTest extends TestCase
{
    private PDO $pdo;
    private TirageService $service;
    private DateTimeImmutable $matin;

    private static int $categorie;

    // Le test du schéma vide la bibliothèque : trois défis de plus, une fois pour la classe, garantissent de quoi tirer quel que soit l'ordre des tests.
    public static function setUpBeforeClass(): void
    {
        $pdo = Bdd::connexion();
        $pdo->exec("INSERT INTO categorie (libelle) VALUES ('Catégorie du test de tirage')");
        $categorie = self::$categorie = (int) $pdo->lastInsertId();
        foreach ([1, 2, 3] as $n) {
            $pdo->prepare('INSERT INTO defi (titre, description, categorie_id) VALUES (?, ?, ?)')->execute(["Défi de test $n", 'Pour le test.', $categorie]);
        }
    }

    // Le libellé de catégorie est unique : sans ce ménage, relancer la classe sur la même base échouerait à l'insertion.
    public static function tearDownAfterClass(): void
    {
        $pdo = Bdd::connexion();
        // Les propositions du dernier test retiennent ses défis : la base est vidée d'abord.
        viderLaBase($pdo);
        $pdo->prepare('DELETE FROM defi WHERE categorie_id = ?')->execute([self::$categorie]);
        $pdo->prepare('DELETE FROM categorie WHERE id = ?')->execute([self::$categorie]);
    }

    protected function setUp(): void
    {
        // Comme le test de la journée : base locale vidée, l'heure passée en paramètre.
        $this->pdo = Bdd::connexion();
        viderLaBase($this->pdo);

        $this->service = $this->construire(new TirageDepot($this->pdo));
        $this->matin = new DateTimeImmutable('2026-10-15 09:00:00');
    }

    private function construire(TirageDepot $tirages): TirageService
    {
        $journee = new JourneeService(new ParticipationDepot($this->pdo), new DuoDepot($this->pdo), new MissionDepot($this->pdo));
        return new TirageService($this->pdo, $journee, $tirages, new DefiDepot($this->pdo), new JoueurDepot($this->pdo), new MissionDepot($this->pdo));
    }

    // Un duo activé la veille, onboarding fini par les deux ; chaque nom de $ouis a déjà répondu oui ce matin.
    /** @return Joueur[] */
    private function formerDuo(array $ouis = ['Lou', 'Mia']): array
    {
        $duos = new DuoDepot($this->pdo);
        $duo = $duos->creer($this->matin->modify('-1 day'));
        $duos->activer($duo, $this->matin->modify('-1 day'));
        $joueurs = new JoueurDepot($this->pdo);
        $formes = [];
        foreach (['Lou', 'Mia'] as $identifiant) {
            $id = $joueurs->ajouter($identifiant, $identifiant, 'empreinte', $this->matin->modify('-1 day'));
            $joueurs->rattacherAuDuo($id, $duo);
            $joueurs->marquerOnboardingVu($id);
            $formes[] = $joueurs->trouverParId($id);
        }
        $journee = new JourneeService(new ParticipationDepot($this->pdo), $duos, new MissionDepot($this->pdo));
        foreach ($formes as $joueur) {
            if (in_array($joueur->pseudo, $ouis, true)) {
                $journee->repondre($joueur, Reponse::Oui, $this->matin);
            }
        }
        return $formes;
    }

    // C2 éventail : à la première demande, trois défis distincts de la bibliothèque, proposés par Lou à Mia.
    public function testLaPremiereDemandeTireTroisPropositionsDistinctes(): void
    {
        [$lou, $mia] = $this->formerDuo();

        $tirage = $this->service->tirer($lou, $this->matin->modify('+5 minutes'));

        $this->assertCount(3, array_unique($tirage->defiIds));
        $this->assertSame($lou->id, $tirage->emetteurId);
        $this->assertSame($mia->id, $tirage->destinataireId);
        $this->assertSame('2026-10-15', $tirage->dateJour->format('Y-m-d'));
        $bibliotheque = (new DefiDepot($this->pdo))->tous();
        foreach ($tirage->defiIds as $defiId) {
            $this->assertArrayHasKey($defiId, $bibliotheque);
        }
    }

    // Pas de retirage : une seconde demande le même jour (rechargement, second appareil) relit les mêmes trois propositions.
    public function testUneSecondeDemandeLeMemeJourRelitLesMemesPropositions(): void
    {
        [$lou] = $this->formerDuo();
        $premier = $this->service->tirer($lou, $this->matin->modify('+5 minutes'));

        $second = $this->service->tirer($lou, $this->matin->modify('+2 hours'));

        $this->assertSame($premier->id, $second->id);
        $this->assertSame($premier->defiIds, $second->defiIds);
    }

    // C1 état 2 : tant que les deux n'ont pas dit oui, aucun tirage n'est créé, même à la demande directe de C2.
    public function testAucunTirageAvantLesDeuxOui(): void
    {
        [$lou, $mia] = $this->formerDuo(['Lou']);

        $this->assertSame(EtatAccueil::AttentePartenaire, $this->service->tirer($lou, $this->matin->modify('+5 minutes')));
        $this->assertSame(EtatAccueil::Question, $this->service->tirer($mia, $this->matin->modify('+5 minutes')));
        $this->assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM tirage')->fetchColumn());
    }

    // C2 passé 12 h sans choix : plus d'éventail, qu'un tirage existe déjà ou non. La journée blanche rendue permet à C2 de dire que la journée est passée.
    public function testApresMidiSansChoixLEventailNEstPlusMontre(): void
    {
        [$lou, $mia] = $this->formerDuo();
        $this->service->tirer($lou, $this->matin->modify('+5 minutes'));
        $midi = new DateTimeImmutable('2026-10-15 12:00:00');

        $this->assertSame(EtatAccueil::JourneeBlancheSansChoix, $this->service->tirer($lou, $midi));
        $this->assertSame(EtatAccueil::JourneeBlancheSansChoix, $this->service->tirer($mia, $midi));
        $this->assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM tirage')->fetchColumn());
    }

    // C1 état 4 : une fois le choix fait, l'éventail n'est plus montré, même avant 12 h ; C2 renvoie à l'accueil.
    public function testApresLeChoixLEventailNEstPlusMontre(): void
    {
        [$lou] = $this->formerDuo();
        $tirage = $this->service->tirer($lou, $this->matin->modify('+5 minutes'));
        $this->service->choisir($lou, $tirage->defiIds[0], $this->matin->modify('+10 minutes'));

        $this->assertSame(EtatAccueil::DefiEnvoye, $this->service->tirer($lou, $this->matin->modify('+15 minutes')));
    }

    // Course : deux demandes simultanées (double clic, deux appareils) lisent toutes deux « pas de tirage ». La seconde bute sur la clé unique
    // (emetteur_id, date_jour) : elle relit le tirage de la première, sans erreur ni second jeu de propositions.
    public function testDeuxDemandesConcurrentesNeCreentQuUnTirage(): void
    {
        [$lou] = $this->formerDuo();
        $premier = $this->service->tirer($lou, $this->matin->modify('+5 minutes'));
        // La seconde demande a lu la base avant que la première n'écrive : sa première lecture ne trouve rien.
        $lectureAncienne = new class ($this->pdo) extends TirageDepot {
            private bool $dejaLu = false;

            public function trouverParEmetteurEtJour(int $emetteurId, DateTimeImmutable $jour): ?Tirage
            {
                if (!$this->dejaLu) {
                    $this->dejaLu = true;
                    return null;
                }
                return parent::trouverParEmetteurEtJour($emetteurId, $jour);
            }
        };

        $second = $this->construire($lectureAncienne)->tirer($lou, $this->matin->modify('+5 minutes'));

        $this->assertSame($premier->id, $second->id);
        $this->assertSame($premier->defiIds, $second->defiIds);
        $this->assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM tirage')->fetchColumn());
        $this->assertSame(3, (int) $this->pdo->query('SELECT COUNT(*) FROM tirage_proposition')->fetchColumn());
    }

    // Bibliothèque réduite à $nombre défis, pour que les propositions attendues se déduisent sans hasard. Les tests suivants en gardent au moins trois.
    /** @return int[] */
    private function bibliotheque(int $nombre): array
    {
        $this->pdo->exec('DELETE FROM defi');
        $ajout = $this->pdo->prepare("INSERT INTO defi (titre, description, categorie_id) SELECT ?, 'Pour le test.', id FROM categorie WHERE libelle = 'Catégorie du test de tirage'");
        $ids = [];
        foreach (range(1, $nombre) as $n) {
            $ajout->execute(["Défi de réservoir $n"]);
            $ids[] = (int) $this->pdo->lastInsertId();
        }
        return $ids;
    }

    // Une journée passée de $emetteur vers $destinataire : ses propositions et, si $retenu est donné, la mission révélée qui en est issue.
    private function journeePassee(Joueur $emetteur, Joueur $destinataire, string $decalage, array $defiIds, ?int $retenu = null): void
    {
        $jour = $this->matin->modify($decalage);
        $tirages = new TirageDepot($this->pdo);
        $tirages->creer($emetteur->duoId, $emetteur->id, $destinataire->id, $jour, $defiIds);
        if ($retenu !== null) {
            $missions = new MissionDepot($this->pdo);
            $mission = $missions->ajouter($tirages->trouverParEmetteurEtJour($emetteur->id, $jour), $retenu, $jour, $jour->modify('tomorrow'));
            $missions->reveler($mission, $jour, $jour->modify('tomorrow'));
        }
    }

    // C2 éventail, réservoir : un défi déjà reçu par Mia ne lui est plus proposé ; restent exactement les trois autres.
    public function testUnDefiDejaRecuNestPlusPropose(): void
    {
        [$a, $b, $c, $d] = $this->bibliotheque(4);
        [$lou, $mia] = $this->formerDuo();
        $this->journeePassee($lou, $mia, '-10 days', [$a, $b, $c], $a);

        $tirage = $this->service->tirer($lou, $this->matin->modify('+5 minutes'));

        $this->assertEqualsCanonicalizing([$b, $c, $d], $tirage->defiIds);
    }

    // C2 éventail, fenêtre de trois jours : les propositions non retenues d'avant-hier restent écartées, celles d'il y a trois jours reviennent.
    public function testUnePropositionNonRetenueRevientApresTroisJours(): void
    {
        [$a, $b, $c, $d, $e, $f] = $this->bibliotheque(6);
        [$lou, $mia] = $this->formerDuo();
        $this->journeePassee($lou, $mia, '-3 days', [$a, $b, $c]);
        $this->journeePassee($lou, $mia, '-2 days', [$d, $e, $f]);

        $tirage = $this->service->tirer($lou, $this->matin->modify('+5 minutes'));

        $this->assertEqualsCanonicalizing([$a, $b, $c], $tirage->defiIds);
    }

    // C2 éventail, remise à zéro : Mia a reçu deux des quatre défis, il en reste deux ; son cycle repart à zéro et trois propositions distinctes sont tirées.
    public function testSousTroisDefisDisponiblesLeCycleRepartAZero(): void
    {
        [$a, $b, $c, $d] = $this->bibliotheque(4);
        [$lou, $mia] = $this->formerDuo();
        $this->journeePassee($lou, $mia, '-20 days', [$a, $b, $c], $a);
        $this->journeePassee($lou, $mia, '-10 days', [$b, $c, $d], $b);

        $tirage = $this->service->tirer($lou, $this->matin->modify('+5 minutes'));

        $this->assertCount(3, array_unique($tirage->defiIds));
        $this->assertEmpty(array_diff($tirage->defiIds, [$a, $b, $c, $d]));
    }

    // Après la remise à zéro, le nouveau cycle écarte ce qu'il a déjà donné : c, reçu quand il ne restait que c et d, n'est pas reproposé.
    public function testLeCycleRepartiEcarteLesDefisRecusDepuis(): void
    {
        [$a, $b, $c, $d] = $this->bibliotheque(4);
        [$lou, $mia] = $this->formerDuo();
        $this->journeePassee($lou, $mia, '-30 days', [$a, $b, $c], $a);
        $this->journeePassee($lou, $mia, '-20 days', [$b, $c, $d], $b);
        $this->journeePassee($lou, $mia, '-10 days', [$a, $c, $d], $c);

        $tirage = $this->service->tirer($lou, $this->matin->modify('+5 minutes'));

        $this->assertEqualsCanonicalizing([$a, $b, $d], $tirage->defiIds);
    }

    // C2 éventail, mission annulée (cadrage § 4.6, tranché le 18/11) : son défi n'est ni reçu ni soumis aux trois jours, il revient dès le lendemain.
    // Cinq défis : b et c, non retenus hier, restent écartés ; il reste exactement a, d et e, sans que le repli de la petite bibliothèque intervienne.
    public function testLeDefiDUneMissionAnnuleeRevientDesLeLendemain(): void
    {
        [$a, $b, $c, $d, $e] = $this->bibliotheque(5);
        [$lou, $mia] = $this->formerDuo();
        $hier = $this->matin->modify('-1 day');
        $tirages = new TirageDepot($this->pdo);
        $tirages->creer($lou->duoId, $lou->id, $mia->id, $hier, [$a, $b, $c]);
        $missions = new MissionDepot($this->pdo);
        $mission = $missions->ajouter($tirages->trouverParEmetteurEtJour($lou->id, $hier), $a, $hier, $hier->modify('tomorrow'));
        $missions->changerStatut($mission, StatutMission::Annule);

        $tirage = $this->service->tirer($lou, $this->matin->modify('+5 minutes'));

        $this->assertEqualsCanonicalizing([$a, $d, $e], $tirage->defiIds);
    }

    // C2 carte piochée, puis C1 état 4 : le choix crée la mission de Mia, non révélée ; Lou lit « défi envoyé », Mia doit encore piocher.
    public function testLeChoixDUneDesTroisPropositionsCreeLaMissionDuPartenaire(): void
    {
        [$lou, $mia] = $this->formerDuo();
        $tirage = $this->service->tirer($lou, $this->matin->modify('+5 minutes'));

        $this->assertTrue($this->service->choisir($lou, $tirage->defiIds[1], $this->matin->modify('+10 minutes')));

        $mission = (new MissionDepot($this->pdo))->trouverParTirage($tirage->id);
        $this->assertSame(StatutMission::AttribueNonRevele, $mission->statut);
        $this->assertSame($tirage->defiIds[1], $mission->defiId);
        $this->assertSame($lou->id, $mission->emetteurId);
        $this->assertSame($mia->id, $mission->destinataireId);
        $this->assertSame('2026-10-15 09:10:00', $mission->dateAttribution->format('Y-m-d H:i:s'));
        $journee = new JourneeService(new ParticipationDepot($this->pdo), new DuoDepot($this->pdo), new MissionDepot($this->pdo));
        $this->assertSame(EtatAccueil::DefiEnvoye, $journee->etatAccueil($lou, $this->matin->modify('+11 minutes')));
        $this->assertSame(EtatAccueil::ATirer, $journee->etatAccueil($mia, $this->matin->modify('+11 minutes')));
    }

    // Un défi de la bibliothèque absent des trois propositions (formulaire falsifié) est refusé : aucune mission.
    public function testUnDefiHorsDesTroisPropositionsEstRefuse(): void
    {
        [$lou] = $this->formerDuo();
        $tirage = $this->service->tirer($lou, $this->matin->modify('+5 minutes'));
        // Défi ajouté après le tirage : il ne peut pas en faire partie, même quand la bibliothèque n'en compte que trois.
        $this->pdo->exec("INSERT INTO defi (titre, description, categorie_id) SELECT 'Défi hors tirage', 'Pour le test.', MIN(id) FROM categorie");
        $horsTirage = (int) $this->pdo->lastInsertId();

        $this->assertFalse($this->service->choisir($lou, $horsTirage, $this->matin->modify('+10 minutes')));
        $this->assertNull((new MissionDepot($this->pdo))->trouverParTirage($tirage->id));
    }

    // Choix arrivé à 12 h pile (horodatage serveur) : refusé, rien n'est écrit. Le contrôleur affiche le message.
    public function testUnChoixApresMidiEstRefuseSansEcriture(): void
    {
        [$lou] = $this->formerDuo();
        $tirage = $this->service->tirer($lou, $this->matin->modify('+5 minutes'));

        $this->assertFalse($this->service->choisir($lou, $tirage->defiIds[0], new DateTimeImmutable('2026-10-15 12:00:00')));
        $this->assertNull((new MissionDepot($this->pdo))->trouverParTirage($tirage->id));
    }

    // Celui qui avait choisi avant 12 h et renvoie le formulaire après (page restée ouverte) n'est pas refusé : son choix tient.
    public function testUnRenvoiApresMidiDUnJoueurQuiAvaitChoisiNEstPasRefuse(): void
    {
        [$lou] = $this->formerDuo();
        $tirage = $this->service->tirer($lou, $this->matin->modify('+5 minutes'));
        $this->service->choisir($lou, $tirage->defiIds[0], $this->matin->modify('+10 minutes'));

        $this->assertTrue($this->service->choisir($lou, $tirage->defiIds[0], new DateTimeImmutable('2026-10-15 12:05:00')));
    }

    // Second choix (double clic, second appareil, ou deux envois simultanés) : le service écrit sans relire, la clé unique tirage_id tranche.
    // Le premier choix reste, sans erreur ni seconde mission.
    public function testUnSecondChoixNeCreeQuUneMission(): void
    {
        [$lou] = $this->formerDuo();
        $tirage = $this->service->tirer($lou, $this->matin->modify('+5 minutes'));
        $this->service->choisir($lou, $tirage->defiIds[0], $this->matin->modify('+10 minutes'));

        $this->assertTrue($this->service->choisir($lou, $tirage->defiIds[2], $this->matin->modify('+10 minutes')));

        $this->assertSame($tirage->defiIds[0], (new MissionDepot($this->pdo))->trouverParTirage($tirage->id)->defiId);
        $this->assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM mission')->fetchColumn());
    }
}
