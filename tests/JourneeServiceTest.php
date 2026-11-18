<?php
// Tests de JourneeService — C1 Accueil — parcours 2 (journée de jeu) — lot 1 — B. Bornes de la journée en Europe/Paris, $maintenant injecté.

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class JourneeServiceTest extends TestCase
{
    private PDO $pdo;
    private JourneeService $service;
    private DateTimeImmutable $matin;

    protected function setUp(): void
    {
        // Comme le test d'appairage : base locale vidée, l'heure passée en paramètre. Les duos sont formés par chaque test.
        $this->pdo = Bdd::connexion();
        // Le duo d'abord : ses participations partent avec lui (ON DELETE CASCADE), puis plus rien ne retient les joueurs.
        $this->pdo->exec('DELETE FROM duo');
        $this->pdo->exec('DELETE FROM joueur');

        $this->service = new JourneeService(new ParticipationDepot($this->pdo), new DuoDepot($this->pdo));
        $this->matin = new DateTimeImmutable('2026-10-15 09:00:00');
    }

    // Un duo dont les deux joueurs ont fini l'onboarding : l'accès au jeu, condition d'arrivée sur C1. Activé la veille, sauf instant donné.
    /** @return Joueur[] */
    private function formerDuo(?DateTimeImmutable $activation = null): array
    {
        $duos = new DuoDepot($this->pdo);
        $duo = $duos->creer($this->matin->modify('-1 day'));
        $duos->activer($duo, $activation ?? $this->matin->modify('-1 day'));
        $joueurs = new JoueurDepot($this->pdo);
        $formes = [];
        foreach (['Lou', 'Mia'] as $identifiant) {
            $id = $joueurs->ajouter($identifiant, $identifiant, 'empreinte', $this->matin->modify('-1 day'));
            $joueurs->rattacherAuDuo($id, $duo);
            $joueurs->marquerOnboardingVu($id);
            $formes[] = $joueurs->trouverParId($id);
        }
        return $formes;
    }

    // L'instant est ramené en Europe/Paris par le service : il ne dépend pas du fuseau par défaut posé par l'amorce.
    public function testUnInstantDansUnAutreFuseauEstLuEnHeureDeParis(): void
    {
        // 22 h 30 UTC le 15 octobre (heure d'été) : 0 h 30 le 16 à Paris.
        $this->assertSame('2026-10-16', $this->service->journee(new DateTimeImmutable('2026-10-15T22:30:00Z')));
        // 10 h 30 UTC : 12 h 30 à Paris, la bascule est passée.
        $this->assertFalse($this->service->avantBascule(new DateTimeImmutable('2026-10-15T10:30:00Z')));
        $this->assertSame('2026-10-16T12:00:00+02:00', $this->service->bascule(new DateTimeImmutable('2026-10-15T22:30:00Z'))->format(DATE_ATOM));

        // L'heure de réponse s'écrit en heure de Paris, comme la journée : 7 h 30 UTC, 9 h 30 à Paris.
        [$lou] = $this->formerDuo();
        $this->service->repondre($lou, Reponse::Oui, new DateTimeImmutable('2026-10-15T07:30:00Z'));
        $participation = (new ParticipationDepot($this->pdo))->trouverParJoueurEtJour($lou->id, new DateTimeImmutable('2026-10-15'));
        $this->assertSame('2026-10-15 09:30:00', $participation->dateReponse->format('Y-m-d H:i:s'));
    }

    // Une seconde avant minuit, on est encore dans la journée ; à minuit pile, la suivante commence.
    public function testUnInstantAutourDeMinuitAppartientALaBonneJournee(): void
    {
        $this->assertSame('2026-10-15', $this->service->journee(new DateTimeImmutable('2026-10-15 23:59:59')));
        $this->assertSame('2026-10-16', $this->service->journee(new DateTimeImmutable('2026-10-16 00:00:00')));
    }

    // La bascule est à 12 h pile : à 11 h 59 min 59 s on peut encore répondre, à 12 h non (cadrage § 4.2).
    public function testUnInstantAutourDeMidiEstClasseDuBonCoteDeLaBascule(): void
    {
        $this->assertEquals(new DateTimeImmutable('2026-10-15 12:00:00'), $this->service->bascule(new DateTimeImmutable('2026-10-15 08:30:00')));
        $this->assertTrue($this->service->avantBascule(new DateTimeImmutable('2026-10-15 11:59:59')));
        $this->assertFalse($this->service->avantBascule(new DateTimeImmutable('2026-10-15 12:00:00')));
    }

    // Les heures d'été et d'hiver se lisent dans le décalage UTC : 1 h 30 avant midi le matin, l'autre décalage à midi.
    public static function joursDeChangementDHeure(): array
    {
        return [
            // Passage à l'heure d'été : 2 h devient 3 h, la journée dure 23 h.
            'journée de 23 h' => ['2027-03-28T01:30:00+01:00', '2027-03-28', '2027-03-28T12:00:00+02:00', '2027-03-29T00:00:00+02:00', 23],
            // Passage à l'heure d'hiver : 3 h redevient 2 h, la journée dure 25 h.
            'journée de 25 h' => ['2026-10-25T01:30:00+02:00', '2026-10-25', '2026-10-25T12:00:00+01:00', '2026-10-26T00:00:00+01:00', 25],
        ];
    }

    // Doc technique § 2.4 : bornes par le calendrier, jamais 86 400 s ; minuit suivant est le vrai minuit, pas 1 h ni 23 h.
    #[DataProvider('joursDeChangementDHeure')]
    public function testUnJourDeChangementDHeureALesBonnesBornes(string $instant, string $journee, string $bascule, string $minuit, int $heures): void
    {
        $maintenant = (new DateTimeImmutable($instant))->setTimezone(new DateTimeZone('Europe/Paris'));

        $this->assertSame($journee, $this->service->journee($maintenant));
        $this->assertEquals(new DateTimeImmutable($bascule), $this->service->bascule($maintenant));
        $this->assertSame((new DateTimeImmutable($minuit))->format(DATE_ATOM), $this->service->minuitSuivant($maintenant)->format(DATE_ATOM));
        $this->assertSame($heures * 3600, $this->service->minuitSuivant($maintenant)->getTimestamp() - (new DateTimeImmutable("$journee 00:00:00"))->getTimestamp());
    }

    // C1 états 1 et 2 : sans réponse, la question ; après son oui, l'attente du partenaire, qui voit toujours la question.
    public function testUnOuiAvantMidiFaitPasserDeLaQuestionALAttenteDuPartenaire(): void
    {
        [$lou, $mia] = $this->formerDuo();
        $this->assertSame(EtatAccueil::Question, $this->service->etatAccueil($lou, $this->matin));

        $this->service->repondre($lou, Reponse::Oui, $this->matin);

        $this->assertSame(EtatAccueil::AttentePartenaire, $this->service->etatAccueil($lou, $this->matin->modify('+1 minute')));
        $this->assertSame(EtatAccueil::Question, $this->service->etatAccueil($mia, $this->matin->modify('+1 minute')));
    }

    // Réponse définitive : un second envoi (double clic, second appareil) ne change rien et ne lève aucune erreur.
    public function testUneSecondeReponseEstSansEffetNiErreur(): void
    {
        [$lou] = $this->formerDuo();
        $this->service->repondre($lou, Reponse::Oui, $this->matin);

        $this->service->repondre($lou, Reponse::Non, $this->matin->modify('+1 second'));

        $this->assertSame(EtatAccueil::AttentePartenaire, $this->service->etatAccueil($lou, $this->matin->modify('+1 minute')));
    }

    // Spec #57, récit 4 : la question n'est pas posée à un joueur dont le partenaire a déjà dit non.
    public function testUnPartenaireQuiADitNonNeLaissePasLaQuestion(): void
    {
        [$lou, $mia] = $this->formerDuo();

        $this->service->repondre($mia, Reponse::Non, $this->matin);

        $this->assertNotSame(EtatAccueil::Question, $this->service->etatAccueil($lou, $this->matin->modify('+1 minute')));
    }

    // Spec #57, récit 4 : une réponse envoyée quand même (page ouverte avant le non du partenaire) n'est pas écrite.
    public function testUneReponseApresLeNonDuPartenaireNEstPasEcrite(): void
    {
        [$lou, $mia] = $this->formerDuo();
        $this->service->repondre($mia, Reponse::Non, $this->matin);

        $this->service->repondre($lou, Reponse::Oui, $this->matin->modify('+1 minute'));

        $this->assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM participation')->fetchColumn());
    }

    // Un joueur relu sans duo (dissolution entre-temps) n'a pas de journée : pas de jeu, et aucune erreur.
    public function testUnJoueurSansDuoNAPasDeJeu(): void
    {
        $joueurs = new JoueurDepot($this->pdo);
        $seul = $joueurs->trouverParId($joueurs->ajouter('Noe', 'Noe', 'empreinte', $this->matin->modify('-1 day')));

        $this->assertSame(EtatAccueil::JourneeBlanche, $this->service->etatAccueil($seul, $this->matin));
    }

    // Les deux ont dit oui : ni question ni attente, la journée est jouée.
    public function testLesDeuxOuiSortentDeLAttente(): void
    {
        [$lou, $mia] = $this->formerDuo();

        $this->service->repondre($lou, Reponse::Oui, $this->matin);
        $this->service->repondre($mia, Reponse::Oui, $this->matin);

        $this->assertSame(EtatAccueil::LesDeuxOntDitOui, $this->service->etatAccueil($lou, $this->matin->modify('+1 minute')));
    }

    // Ticket #69 : un seul non donne la journée blanche aux deux, à celui qui a dit non comme à l'autre.
    public function testUnNonDonneLaJourneeBlancheAuxDeux(): void
    {
        [$lou, $mia] = $this->formerDuo();
        $this->service->repondre($lou, Reponse::Oui, $this->matin);

        $this->service->repondre($mia, Reponse::Non, $this->matin);

        $this->assertSame(EtatAccueil::JourneeBlanche, $this->service->etatAccueil($lou, $this->matin->modify('+1 minute')));
        $this->assertSame(EtatAccueil::JourneeBlanche, $this->service->etatAccueil($mia, $this->matin->modify('+1 minute')));
    }

    // Ticket #69 : à 12 h, un joueur qui n'a pas répondu donne la journée blanche, même si l'autre a dit oui ; rien n'est écrit pour elle.
    public function testAucuneReponseAMidiDonneLaJourneeBlanche(): void
    {
        [$lou, $mia] = $this->formerDuo();
        $this->service->repondre($lou, Reponse::Oui, $this->matin);
        $midi = new DateTimeImmutable('2026-10-15 12:00:00');

        $this->assertSame(EtatAccueil::JourneeBlanche, $this->service->etatAccueil($lou, $midi));
        $this->assertSame(EtatAccueil::JourneeBlanche, $this->service->etatAccueil($mia, $midi));
        $this->assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM participation')->fetchColumn());
    }

    // Ticket #69 : les deux oui avant 12 h, la journée reste jouée après la bascule.
    public function testLesDeuxOuiRestentJouesApresMidi(): void
    {
        [$lou, $mia] = $this->formerDuo();
        $this->service->repondre($lou, Reponse::Oui, $this->matin);
        $this->service->repondre($mia, Reponse::Oui, $this->matin);

        $this->assertSame(EtatAccueil::LesDeuxOntDitOui, $this->service->etatAccueil($lou, new DateTimeImmutable('2026-10-15 15:00:00')));
    }

    // Ticket #69 : un duo activé avant 12 h joue le jour même, état 1.
    public function testUnDuoActiveAvantMidiVoitLaQuestion(): void
    {
        [$lou] = $this->formerDuo(new DateTimeImmutable('2026-10-15 08:00:00'));

        $this->assertSame(EtatAccueil::Question, $this->service->etatAccueil($lou, $this->matin));
    }

    // Ticket #69 : un duo activé après 12 h lit « premier jour demain » jusqu'à minuit, puis la question le lendemain.
    public function testUnDuoActiveApresMidiAttendLeLendemainJusquAMinuit(): void
    {
        [$lou] = $this->formerDuo(new DateTimeImmutable('2026-10-15 14:00:00'));

        $this->assertSame(EtatAccueil::PremierJourDemain, $this->service->etatAccueil($lou, new DateTimeImmutable('2026-10-15 14:00:00')));
        $this->assertSame(EtatAccueil::PremierJourDemain, $this->service->etatAccueil($lou, new DateTimeImmutable('2026-10-15 23:59:59')));
        $this->assertSame(EtatAccueil::Question, $this->service->etatAccueil($lou, new DateTimeImmutable('2026-10-16 09:00:00')));
    }

    // Ticket #69 : une réponse envoyée à 12 h ou après est refusée par l'horodatage serveur, et rien n'est écrit.
    public function testUneReponseApresMidiEstRefuseeSansEcriture(): void
    {
        [$lou] = $this->formerDuo();

        $this->assertFalse($this->service->repondre($lou, Reponse::Oui, new DateTimeImmutable('2026-10-15 12:00:00')));

        $this->assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM participation')->fetchColumn());
        $this->assertSame(EtatAccueil::JourneeBlanche, $this->service->etatAccueil($lou, new DateTimeImmutable('2026-10-15 12:01:00')));
    }

    // Un joueur qui avait déjà répondu et renvoie après 12 h (page restée ouverte) : sa réponse a bien été prise en compte, pas de refus.
    public function testUnRenvoiApresMidiDUnJoueurQuiAvaitReponduNEstPasRefuse(): void
    {
        [$lou] = $this->formerDuo();
        $this->service->repondre($lou, Reponse::Oui, $this->matin);

        $this->assertTrue($this->service->repondre($lou, Reponse::Oui, new DateTimeImmutable('2026-10-15 12:05:00')));
    }
}
