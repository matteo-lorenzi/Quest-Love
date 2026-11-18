<?php
// Tests de cohérence du schéma SQL et des contraintes de la base de jeu — aucun écran — Journée de jeu — lot 1 — commun.

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SchemaTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        // Le duo d'abord : sa suppression emporte les données de jeu, qui bloqueraient celle des joueurs.
        $this->pdo = Bdd::connexion();
        $this->pdo->exec('DELETE FROM duo');
        $this->pdo->exec('DELETE FROM joueur');
        $this->pdo->exec('DELETE FROM defi');
        $this->pdo->exec('DELETE FROM categorie');
    }

    // Les autres classes de test vident joueur sans passer par duo : aucune ligne de jeu ne doit leur survivre.
    protected function tearDown(): void
    {
        $this->setUp();
    }

    public static function enumerations(): array
    {
        return [
            'tentative.type' => ['tentative', 'type', TypeTentative::class],
            'participation.reponse' => ['participation', 'reponse', Reponse::class],
            'mission.statut' => ['mission', 'statut', StatutMission::class],
        ];
    }

    // Les ENUM sont écrits à la main dans sql/schema.sql : chacun doit lister exactement les cas de son énumération PHP.
    #[DataProvider('enumerations')]
    public function testLEnumDuSchemaCorrespondALEnumerationPhp(string $table, string $colonne, string $enumeration): void
    {
        $schema = file_get_contents(__DIR__ . '/../sql/schema.sql');
        $this->assertSame(1, preg_match("/CREATE TABLE $table \\(.*?\\n  $colonne ENUM\\(([^)]*)\\)/s", $schema, $m));

        $dansLeSchema = array_map(fn(string $v) => trim($v, " '"), explode(',', $m[1]));
        $dansLeCode = array_map(fn(BackedEnum $c) => $c->value, $enumeration::cases());

        sort($dansLeSchema);
        sort($dansLeCode);
        $this->assertSame($dansLeCode, $dansLeSchema);
    }

    // Lot 1 seulement : contestation et acquisition arrivent avec le lot 2 (spec #56).
    public function testLeStatutDeMissionNeConnaitPasEncoreLeLot2(): void
    {
        $valeurs = array_map(fn(StatutMission $s) => $s->value, StatutMission::cases());

        $this->assertSame(['attribue_non_revele', 'en_cours', 'valide', 'expire', 'annule'], $valeurs);
    }

    // Modèle de données § 4 : une réponse par joueur et par journée, même sur un double clic.
    public function testUneSecondeParticipationDuMemeJourEstRefusee(): void
    {
        $jeu = $this->creerJourneeDeJeu('lou', 'mia');

        $this->assertDoublonRefuse(fn() => $this->inserer('participation', ['duo_id' => $jeu['duo'], 'joueur_id' => $jeu['emetteur'], 'date_jour' => '2026-11-18', 'reponse' => 'non', 'date_reponse' => '2026-11-18 09:01:00']));
    }

    // Modèle de données § 4 : un rechargement ne donne pas un second jeu de propositions.
    public function testUnSecondTirageDuMemeEmetteurLeMemeJourEstRefuse(): void
    {
        $jeu = $this->creerJourneeDeJeu('lou', 'mia');

        $this->assertDoublonRefuse(fn() => $this->inserer('tirage', ['duo_id' => $jeu['duo'], 'emetteur_id' => $jeu['emetteur'], 'destinataire_id' => $jeu['destinataire'], 'date_jour' => '2026-11-18']));
    }

    // Trois défis distincts par tirage.
    public function testUnDefiNeFigurePasDeuxFoisDansLeMemeTirage(): void
    {
        $jeu = $this->creerJourneeDeJeu('lou', 'mia');

        $this->assertDoublonRefuse(fn() => $this->inserer('tirage_proposition', ['tirage_id' => $jeu['tirage'], 'defi_id' => $jeu['defis'][0]]));
    }

    // Un seul choix par tirage : le second est refusé, même portant sur une autre proposition.
    public function testUnSecondChoixDansLeMemeTirageEstRefuse(): void
    {
        $jeu = $this->creerJourneeDeJeu('lou', 'mia');

        $this->assertDoublonRefuse(fn() => $this->insererMission($jeu, $jeu['defis'][1]));
    }

    // Clé composite (ADR-0003) : le défi d'une mission est forcément l'une des propositions de son tirage.
    public function testUneMissionDontLeDefiNEstPasProposeEstRefusee(): void
    {
        $jeu = $this->creerJourneeDeJeu('lou', 'mia');
        $this->pdo->exec('DELETE FROM mission');
        $horsTirage = $this->inserer('defi', ['titre' => 'Hors tirage', 'description' => 'Non proposé.', 'categorie_id' => $jeu['categorie']]);

        try {
            $this->insererMission($jeu, $horsTirage);
            $this->fail('La mission hors propositions a été acceptée.');
        } catch (PDOException $e) {
            // 1452 : clé étrangère introuvable.
            $this->assertSame(1452, $e->errorInfo[1]);
        }
    }

    // Modèle de données § 6 : la dissolution supprime toutes les données de jeu du duo, et seulement les siennes.
    public function testSupprimerUnDuoSupprimeSesDonneesDeJeuEtRienDAutre(): void
    {
        $dissous = $this->creerJourneeDeJeu('lou', 'mia');
        $garde = $this->creerJourneeDeJeu('zoe', 'tom');

        (new DuoDepot($this->pdo))->supprimer($dissous['duo']);

        foreach (['participation', 'tirage', 'mission'] as $table) {
            $this->assertSame([0, 1], [$this->compter($table, $dissous['duo']), $this->compter($table, $garde['duo'])], $table);
        }
        $propositions = $this->pdo->prepare('SELECT COUNT(*) FROM tirage_proposition WHERE tirage_id = ?');
        foreach ([[$dissous, 0], [$garde, 3]] as [$jeu, $attendu]) {
            $propositions->execute([$jeu['tirage']]);
            $this->assertSame($attendu, (int) $propositions->fetchColumn());
        }
    }

    // Un duo complet un jour donné : participation de l'émetteur, tirage de trois défis, mission sur le premier.
    private function creerJourneeDeJeu(string $emetteur, string $destinataire): array
    {
        $jeu = ['duo' => $this->inserer('duo', ['date_creation' => '2026-11-01 10:00:00'])];
        foreach (['emetteur' => $emetteur, 'destinataire' => $destinataire] as $role => $identifiant) {
            $jeu[$role] = $this->inserer('joueur', ['identifiant' => $identifiant, 'pseudo' => $identifiant, 'mdp_hash' => 'empreinte', 'duo_id' => $jeu['duo'], 'date_creation' => '2026-11-01 10:00:00']);
        }
        $jeu['categorie'] = $this->inserer('categorie', ['libelle' => "Catégorie de $emetteur"]);
        foreach ([1, 2, 3] as $n) {
            $jeu['defis'][] = $this->inserer('defi', ['titre' => "Défi $n", 'description' => 'Pour le test.', 'categorie_id' => $jeu['categorie']]);
        }
        $this->inserer('participation', ['duo_id' => $jeu['duo'], 'joueur_id' => $jeu['emetteur'], 'date_jour' => '2026-11-18', 'reponse' => 'oui', 'date_reponse' => '2026-11-18 09:00:00']);
        $jeu['tirage'] = $this->inserer('tirage', ['duo_id' => $jeu['duo'], 'emetteur_id' => $jeu['emetteur'], 'destinataire_id' => $jeu['destinataire'], 'date_jour' => '2026-11-18']);
        foreach ($jeu['defis'] as $defi) {
            $this->inserer('tirage_proposition', ['tirage_id' => $jeu['tirage'], 'defi_id' => $defi]);
        }
        $this->insererMission($jeu, $jeu['defis'][0]);
        return $jeu;
    }

    private function insererMission(array $jeu, int $defi): int
    {
        return $this->inserer('mission', ['duo_id' => $jeu['duo'], 'tirage_id' => $jeu['tirage'], 'defi_id' => $defi, 'emetteur_id' => $jeu['emetteur'], 'destinataire_id' => $jeu['destinataire'], 'date_attribution' => '2026-11-18 09:05:00', 'date_limite' => '2026-11-19 00:00:00', 'statut' => 'attribue_non_revele']);
    }

    // Noms de tables et de colonnes écrits en dur dans ce fichier ; les valeurs passent en paramètres.
    private function inserer(string $table, array $valeurs): int
    {
        $colonnes = implode(', ', array_keys($valeurs));
        $marques = implode(', ', array_fill(0, count($valeurs), '?'));
        $this->pdo->prepare("INSERT INTO $table ($colonnes) VALUES ($marques)")->execute(array_values($valeurs));
        return (int) $this->pdo->lastInsertId();
    }

    private function compter(string $table, int $duo): int
    {
        $requete = $this->pdo->prepare("SELECT COUNT(*) FROM $table WHERE duo_id = ?");
        $requete->execute([$duo]);
        return (int) $requete->fetchColumn();
    }

    private function assertDoublonRefuse(callable $insertion): void
    {
        try {
            $insertion();
            $this->fail('Le doublon a été accepté.');
        } catch (PDOException $e) {
            // 1062 : entrée en double sur une clé unique.
            $this->assertSame(1062, $e->errorInfo[1]);
        }
    }
}
