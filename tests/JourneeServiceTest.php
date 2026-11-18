<?php
// Tests de JourneeService — C1 Accueil — parcours 2 (journée de jeu) — lot 1 — B. Bornes de la journée en Europe/Paris, $maintenant injecté.

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class JourneeServiceTest extends TestCase
{
    private JourneeService $service;

    protected function setUp(): void
    {
        $this->service = new JourneeService();
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
}
