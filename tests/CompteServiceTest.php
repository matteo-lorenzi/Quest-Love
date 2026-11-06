<?php
// Tests de CompteService::connecter et ::inscrire — A3 Connexion et A2 Inscription — parcours 1 (arrivée et appairage) — lot 1 — A.

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CompteServiceTest extends TestCase
{
    private const MDP = 'Lou-test-2026!';
    // Mot de passe qui respecte toutes les règles pour Nina / Nini : espaces intérieures, chiffre, majuscule, spécial.
    private const MDP_VALIDE = 'Mon chat dort 7 fois!';

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

    // A2 — l'inscription valide crée un joueur sans duo, daté de l'heure passée au service (jamais de l'horloge SQL).
    public function testInscriptionValideCreeUnJoueurSansDuo(): void
    {
        $resultat = $this->service->inscrire('Nina', 'Nini', self::MDP_VALIDE, $this->midi);

        $this->assertInstanceOf(Joueur::class, $resultat);
        $this->assertSame('Nina', $resultat->identifiant);
        $this->assertSame('Nini', $resultat->pseudo);
        $this->assertNull($resultat->duoId);
        $this->assertSame(5, $resultat->pv);
        $this->assertEquals($this->midi, $resultat->dateCreation);
    }

    // A2 — Argon2id imposé (cadrage 2.13) : l'empreinte porte son algorithme, et A3 s'ouvre avec le même mot de passe.
    public function testInscriptionStockeUneEmpreinteArgon2id(): void
    {
        $joueur = $this->service->inscrire('Nina', 'Nini', self::MDP_VALIDE, $this->midi);

        $this->assertStringStartsWith('$argon2id$', $joueur->mdpHash);
        $this->assertTrue(password_verify(self::MDP_VALIDE, $joueur->mdpHash));
    }

    // A2 — identifiant de 3 à 30 caractères (cadrage § 6.6) : on teste chaque borne, de part et d'autre.
    #[DataProvider('longueursDIdentifiant')]
    public function testIdentifiantDeTroisATrenteCaracteres(string $identifiant, bool $accepte): void
    {
        $resultat = $this->service->inscrire($identifiant, 'Nini', self::MDP_VALIDE, $this->midi);

        $accepte
            ? $this->assertInstanceOf(Joueur::class, $resultat)
            : $this->assertSame(['identifiant' => ['longueur']], $resultat);
    }

    public static function longueursDIdentifiant(): array
    {
        return [
            '2 refusé' => ['ab', false],
            '3 accepté' => ['abc', true],
            '30 accepté' => [str_repeat('a', 30), true],
            '31 refusé' => [str_repeat('a', 31), false],
        ];
    }

    // A2 — lettres accentuées, chiffres, tiret, tiret bas et point ; ni espace ni autre signe.
    #[DataProvider('caracteresDIdentifiant')]
    public function testIdentifiantAdmetSeulementLettresChiffresEtTroisSignes(string $identifiant, bool $accepte): void
    {
        $resultat = $this->service->inscrire($identifiant, 'Nini', self::MDP_VALIDE, $this->midi);

        $accepte
            ? $this->assertInstanceOf(Joueur::class, $resultat)
            : $this->assertSame(['identifiant' => ['caracteres']], $resultat);
    }

    public static function caracteresDIdentifiant(): array
    {
        return [
            'lettre accentuée' => ['Léa', true],
            'tiret, tiret bas, point, chiffre' => ['a-b_c.d9', true],
            'espace intérieure' => ['lou lou', false],
            'arobase' => ['lou@lou', false],
            'émoji' => ["lou\u{1F600}", false],
        ];
    }

    // A2 — pseudo de 1 à 30 caractères imprimables : émojis acceptés, caractères de contrôle refusés.
    #[DataProvider('pseudos')]
    public function testPseudoDeUnATrenteCaracteresImprimables(string $pseudo, ?string $codeErreur): void
    {
        $resultat = $this->service->inscrire('Nina', $pseudo, self::MDP_VALIDE, $this->midi);

        $codeErreur === null
            ? $this->assertInstanceOf(Joueur::class, $resultat)
            : $this->assertSame(['pseudo' => [$codeErreur]], $resultat);
    }

    public static function pseudos(): array
    {
        return [
            '1 accepté' => ['N', null],
            '30 acceptés' => [str_repeat('é', 30), null],
            '31 refusés' => [str_repeat('é', 31), 'longueur'],
            'vide refusé' => ['', 'vide'],
            'émoji accepté' => ["Nini \u{1F431}", null],
            'caractère de contrôle refusé' => ["Ni\u{0007}ni", 'controle'],
        ];
    }

    // A2 — mot de passe de 12 à 64 caractères (points de code) ; le reste du mot de passe respecte les autres règles.
    #[DataProvider('longueursDeMotDePasse')]
    public function testMotDePasseDeDouzeASoixanteQuatreCaracteres(int $longueur, bool $accepte): void
    {
        $mdp = 'Aa1!' . str_repeat('é', $longueur - 4);

        $resultat = $this->service->inscrire('Nina', 'Nini', $mdp, $this->midi);

        $accepte
            ? $this->assertInstanceOf(Joueur::class, $resultat)
            : $this->assertSame(['mdp' => ['longueur']], $resultat);
    }

    public static function longueursDeMotDePasse(): array
    {
        return [
            '11 refusé' => [11, false],
            '12 accepté' => [12, true],
            '64 accepté' => [64, true],
            '65 refusé' => [65, false],
        ];
    }

    // A2 — une majuscule, une minuscule, un chiffre, un caractère spécial ; l'espace intérieure est un caractère spécial.
    #[DataProvider('compositionsDeMotDePasse')]
    public function testMotDePasseExigeQuatreFamillesDeCaracteres(string $mdp, ?string $codeErreur): void
    {
        $resultat = $this->service->inscrire('Nina', 'Nini', $mdp, $this->midi);

        $codeErreur === null
            ? $this->assertInstanceOf(Joueur::class, $resultat)
            : $this->assertSame(['mdp' => [$codeErreur]], $resultat);
    }

    public static function compositionsDeMotDePasse(): array
    {
        return [
            'sans majuscule' => ['mon chat dort 7 fois!', 'majuscule'],
            'sans minuscule' => ['MON CHAT DORT 7 FOIS!', 'minuscule'],
            'sans chiffre' => ['Mon chat dort six fois!', 'chiffre'],
            'sans caractère spécial' => ['MonChatDortSeptFois7', 'special'],
            'espace intérieure comme caractère spécial' => ['Mon chat dort 7', null],
            'émoji comme caractère spécial' => ["MonChatDort7\u{1F431}ABC", null],
        ];
    }

    // A2 — une espace (tout séparateur Unicode) en début ou en fin serait invisible dans un champ masqué : refusée, jamais rognée.
    // Un caractère de contrôle est refusé : seule une requête forgée peut l'envoyer.
    #[DataProvider('bordsEtControlesDeMotDePasse')]
    public function testMotDePasseRefuseEspaceAuxBordsEtCaracteresDeControle(string $mdp, string $codeErreur): void
    {
        $resultat = $this->service->inscrire('Nina', 'Nini', $mdp, $this->midi);

        $this->assertSame(['mdp' => [$codeErreur]], $resultat);
    }

    public static function bordsEtControlesDeMotDePasse(): array
    {
        return [
            'espace au début' => [' Mon chat dort 7 fois!', 'espace_bord'],
            'espace à la fin' => ['Mon chat dort 7 fois! ', 'espace_bord'],
            'espace insécable au début' => ["\u{00A0}Mon chat dort 7 fois!", 'espace_bord'],
            'tabulation intérieure' => ["Mon chat\tdort 7 fois!", 'controle'],
        ];
    }

    // A2 — le mot de passe ne contient ni l'identifiant ni le pseudo, sans tenir compte des majuscules ni des accents ;
    // une valeur de moins de 3 caractères n'est pas comparée, sinon un pseudo comme « A » bloquerait presque tout mot de passe.
    #[DataProvider('motsDePasseContenantUneIdentite')]
    public function testMotDePasseNeContientNiIdentifiantNiPseudo(string $identifiant, string $pseudo, string $mdp, ?string $codeErreur): void
    {
        $resultat = $this->service->inscrire($identifiant, $pseudo, $mdp, $this->midi);

        $codeErreur === null
            ? $this->assertInstanceOf(Joueur::class, $resultat)
            : $this->assertSame(['mdp' => [$codeErreur]], $resultat);
    }

    public static function motsDePasseContenantUneIdentite(): array
    {
        return [
            'identifiant, autre casse' => ['Lola', 'Zoé', 'Mon LOLA dort 7 fois!', 'contient_identifiant'],
            'identifiant, accent retiré' => ['Léo', 'Zoé', 'Chat LEO dort 7 fois!', 'contient_identifiant'],
            'pseudo, accent et casse' => ['Nina', 'Léa', 'Chat LEA dort 7 fois!', 'contient_pseudo'],
            'pseudo de 2 caractères non comparé' => ['Nina', 'Jo', 'Jo dort 7 fois sur 7!', null],
            'pseudo d\'un caractère non comparé' => ['Nina', 'A', 'Mon chat dort 7 fois!', null],
            'ni l\'un ni l\'autre' => ['Nina', 'Nini', 'Mon chat dort 7 fois!', null],
        ];
    }

    // A2 — Lou existe (setUp) : l'unicité ignore majuscules et accents (cadrage § 6.6), le pseudo, lui, peut se répéter.
    #[DataProvider('doublonsDeLou')]
    public function testIdentifiantDejaPrisEstRefuseSansTenirCompteDeLaCasseNiDesAccents(string $identifiant): void
    {
        $resultat = $this->service->inscrire($identifiant, 'Nini', self::MDP_VALIDE, $this->midi);

        $this->assertSame(['identifiant' => ['pris']], $resultat);
    }

    public static function doublonsDeLou(): array
    {
        return [['Lou'], ['lou'], ['LOU'], ['Lóu']];
    }

    public function testDeuxJoueursPeuventPorterLeMemePseudo(): void
    {
        $premier = $this->service->inscrire('Nina', 'Nini', self::MDP_VALIDE, $this->midi);
        $second = $this->service->inscrire('Mael', 'Nini', self::MDP_VALIDE, $this->midi);

        $this->assertInstanceOf(Joueur::class, $premier);
        $this->assertInstanceOf(Joueur::class, $second);
    }

    // A2 — course : deux inscriptions simultanées voient « identifiant libre », la clé unique départage.
    // Le perdant reçoit le même refus que le doublon ordinaire, pas une erreur serveur.
    // Le seul faux dépôt de la suite : une course ne se reproduit pas autrement de façon fiable.
    public function testCourseSurLIdentifiantDonneLeMemeRefusQueLeDoublon(): void
    {
        $depotAveugle = new class ($this->pdo) extends JoueurDepot {
            public function trouverParIdentifiant(string $identifiant): ?Joueur
            {
                return null;
            }
        };
        $service = new CompteService($depotAveugle, new TentativeDepot($this->pdo));

        $resultat = $service->inscrire('Lou', 'Nini', self::MDP_VALIDE, $this->midi);

        $this->assertSame(['identifiant' => ['pris']], $resultat);
    }

    // A2 puis A3 — un « é » d'un bloc ou en deux temps (e + accent) est le même mot de passe : le compte s'ouvre des deux façons.
    public function testMotDePasseComposeEtDecomposeOuvrentLeMemeCompte(): void
    {
        $this->service->inscrire('Nina', 'Nini', "Caf\u{00E9} au lait 7 fois!", $this->midi);

        $resultat = $this->service->connecter('Nina', "Cafe\u{0301} au lait 7 fois!", 'session-a', $this->midi);

        $this->assertInstanceOf(Joueur::class, $resultat);
        $this->assertSame('Nina', $resultat->identifiant);
    }

    // A2 — l'identifiant et le pseudo sont aussi normalisés : « Léa » en deux temps est valide (l'accent seul n'est pas une lettre),
    // se range comme « Léa » d'un bloc, et ne compte pas deux caractères de plus.
    public function testIdentifiantEtPseudoSontEnregistresEnNfc(): void
    {
        $premier = $this->service->inscrire("Le\u{0301}a", str_repeat("e\u{0301}", 30), self::MDP_VALIDE, $this->midi);
        $doublon = $this->service->inscrire("L\u{00E9}a", 'Nini', self::MDP_VALIDE, $this->midi);

        $this->assertInstanceOf(Joueur::class, $premier);
        $this->assertSame("L\u{00E9}a", $premier->identifiant);
        $this->assertSame(str_repeat("\u{00E9}", 30), $premier->pseudo);
        $this->assertSame(['identifiant' => ['pris']], $doublon);
    }

    // A2 — un texte qui n'est pas de l'UTF-8 valide (requête forgée) est refusé, nommé par son champ, sans erreur PHP.
    public function testTexteNonUtf8EstRefuseParChampSansErreurPhp(): void
    {
        $resultat = $this->service->inscrire("Ni\xFFna", "Ni\xC3", "Mon chat dort 7 fois!\xFE", $this->midi);

        $this->assertSame(
            ['identifiant' => ['encodage'], 'pseudo' => ['encodage'], 'mdp' => ['encodage']],
            $resultat
        );
    }

    // A3 — même refus ordinaire qu'un mauvais mot de passe, sans toucher aux compteurs : aucun compte ne peut porter ce texte.
    public function testConnexionAvecTexteNonUtf8EstRefuseSansCompterDEchec(): void
    {
        $this->assertNull($this->service->connecter('Lou', "Lou-test-2026!\xFF", 'session-a', $this->midi));
        $this->assertNull($this->service->connecter("Lo\xFFu", self::MDP, 'session-a', $this->midi));

        $this->assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM tentative')->fetchColumn());
    }

    // A2 — l'identifiant et le pseudo sont rognés des espaces de bord (tout séparateur Unicode), contrairement au mot de passe.
    public function testIdentifiantEtPseudoSontRognesDesEspacesDeBord(): void
    {
        $joueur = $this->service->inscrire("  Nina\u{00A0}", "\u{3000}Nini ", self::MDP_VALIDE, $this->midi);

        $this->assertInstanceOf(Joueur::class, $joueur);
        $this->assertSame('Nina', $joueur->identifiant);
        $this->assertSame('Nini', $joueur->pseudo);
    }

    public function testPseudoFaitSeulementDEspacesEstVide(): void
    {
        $resultat = $this->service->inscrire('Nina', "   \u{00A0} ", self::MDP_VALIDE, $this->midi);

        $this->assertSame(['pseudo' => ['vide']], $resultat);
    }

    // A2 — un mot de passe de la liste des plus courants (NCSC) est refusé même s'il respecte toutes les autres règles ;
    // la comparaison ignore majuscules et accents. « john!20130605at1753 » figure dans la liste.
    #[DataProvider('motsDePasseCourants')]
    public function testMotDePasseCourantEstRefuse(string $mdp): void
    {
        $resultat = $this->service->inscrire('Nina', 'Nini', $mdp, $this->midi);

        $this->assertSame(['mdp' => ['courant']], $resultat);
    }

    public static function motsDePasseCourants(): array
    {
        return [
            'tel que dans la liste, avec majuscule' => ['John!20130605at1753'],
            'autre casse' => ['jOHN!20130605AT1753'],
            'avec un accent' => ["J\u{00F3}hn!20130605at1753"],
        ];
    }

    // A2 — chaque critère manquant est nommé, tous ensemble : le joueur corrige tout en une fois.
    public function testToutesLesErreursSontRenvoyeesEnsemble(): void
    {
        $resultat = $this->service->inscrire('ab', '', 'abc', $this->midi);

        $this->assertSame(
            [
                'identifiant' => ['longueur'],
                'pseudo' => ['vide'],
                'mdp' => ['longueur', 'majuscule', 'chiffre', 'special'],
            ],
            $resultat
        );
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

    public function testUnIdentifiantTropLongEstRefuseSansToucherLaBase(): void
    {
        // Plus long que la colonne tentative.cle : en mode strict (MariaDB d'Azrael), l'écrire lèverait une erreur.
        $tropLong = str_repeat('a', 70);
        for ($i = 0; $i < 5; $i++) {
            $this->assertNull($this->service->connecter($tropLong, 'mauvais', 'session-a', $this->midi->modify("+$i minutes")));
        }

        // Rien n'a été compté sur la session : Lou se connecte.
        $resultat = $this->service->connecter('Lou', self::MDP, 'session-a', new DateTimeImmutable('2026-10-15 12:05:00'));

        $this->assertInstanceOf(Joueur::class, $resultat);
    }

    // Un échec par minute à partir de midi : 12 h 00, 12 h 01…
    private function echouer(int $nombre, string $identifiant, string $cleSession): void
    {
        for ($i = 0; $i < $nombre; $i++) {
            $this->service->connecter($identifiant, 'mauvais', $cleSession, $this->midi->modify("+$i minutes"));
        }
    }
}
