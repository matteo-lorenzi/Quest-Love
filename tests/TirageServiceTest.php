<?php
// Tests de TirageService — C2 Tirage, étapes « éventail » et « carte piochée », et C1 états 3 et 4 — parcours 2 (journée de jeu) — lot 1 — B. Base locale, $maintenant injecté.

use PHPUnit\Framework\TestCase;

class TirageServiceTest extends TestCase
{
    private PDO $pdo;
    private TirageService $service;
    private DateTimeImmutable $matin;

    // Le test du schéma vide la bibliothèque : trois défis de plus, une fois pour la classe, garantissent de quoi tirer quel que soit l'ordre des tests.
    public static function setUpBeforeClass(): void
    {
        $pdo = Bdd::connexion();
        $pdo->exec("INSERT INTO categorie (libelle) VALUES ('Catégorie du test de tirage')");
        $categorie = (int) $pdo->lastInsertId();
        foreach ([1, 2, 3] as $n) {
            $pdo->prepare('INSERT INTO defi (titre, description, categorie_id) VALUES (?, ?, ?)')->execute(["Défi de test $n", 'Pour le test.', $categorie]);
        }
    }

    protected function setUp(): void
    {
        // Comme le test de la journée : base locale vidée (tirages et participations partent avec le duo), l'heure passée en paramètre.
        $this->pdo = Bdd::connexion();
        $this->pdo->exec('DELETE FROM duo');
        $this->pdo->exec('DELETE FROM joueur');

        $this->service = $this->construire(new TirageDepot($this->pdo));
        $this->matin = new DateTimeImmutable('2026-10-15 09:00:00');
    }

    // Les tirages retiennent leurs joueurs : sans ce ménage, les tests qui vident joueur sans vider duo échoueraient après celui-ci.
    protected function tearDown(): void
    {
        $this->pdo->exec('DELETE FROM duo');
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

        $this->assertNull($this->service->tirer($lou, $this->matin->modify('+5 minutes')));
        $this->assertNull($this->service->tirer($mia, $this->matin->modify('+5 minutes')));
        $this->assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM tirage')->fetchColumn());
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
        $this->assertSame(EtatAccueil::APiocher, $journee->etatAccueil($mia, $this->matin->modify('+11 minutes')));
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
