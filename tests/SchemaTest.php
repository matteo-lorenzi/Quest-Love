<?php
// Tests de cohérence du schéma SQL et des contraintes de la base de jeu — C1 Accueil, C2 Tirage et C3 Défi reçu — parcours 2 (journée de jeu) — lot 1 — commun.

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SchemaTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = Bdd::connexion();
        $this->viderLaBase();
    }

    // Les autres classes de test vident joueur sans passer par duo : aucune ligne de jeu ne doit leur survivre.
    protected function tearDown(): void
    {
        $this->viderLaBase();
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

    // Doc technique § 6.3 et cadrage § 4.6 : sur une base existante, la bibliothèque d'environ cinquante défis remplace les défis, environ douze par catégorie.
    public function testLaReinitialisationChargeLaBibliothequeDeDefis(): void
    {
        $this->reinitialiser();

        $effectifs = $this->pdo->query('SELECT COUNT(defi.id) FROM categorie LEFT JOIN defi ON defi.categorie_id = categorie.id GROUP BY categorie.id')->fetchAll(PDO::FETCH_COLUMN);
        $this->assertCount(4, $effectifs);
        $this->assertGreaterThanOrEqual(45, array_sum($effectifs));
        $this->assertLessThanOrEqual(55, array_sum($effectifs));
        foreach ($effectifs as $effectif) {
            $this->assertGreaterThanOrEqual(10, (int) $effectif);
            $this->assertLessThanOrEqual(14, (int) $effectif);
        }
    }

    // Doc technique § 6.3 : un poste neuf n'a encore aucune table (ticket #66).
    public function testLaReinitialisationReconstruitUneBaseVide(): void
    {
        $tables = $this->pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($tables as $table) {
            $this->pdo->exec("DROP TABLE `$table`");
        }
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

        $this->reinitialiser();

        $this->assertEqualsCanonicalizing($tables, $this->pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN));
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

    public function testUnDefiNeFigurePasDeuxFoisDansLeMemeTirage(): void
    {
        $jeu = $this->creerJourneeDeJeu('lou', 'mia');

        $this->assertDoublonRefuse(fn() => $this->inserer('tirage_proposition', ['tirage_id' => $jeu['tirage'], 'defi_id' => $jeu['defis'][0]]));
    }

    // Le second choix porte sur une autre proposition : seule l'unicité du tirage dans mission peut le refuser.
    public function testUnSecondChoixDansLeMemeTirageEstRefuse(): void
    {
        $jeu = $this->creerJourneeDeJeu('lou', 'mia');

        $this->assertDoublonRefuse(fn() => $this->insererMission($jeu, $jeu['defis'][1]));
    }

    // ADR-0003 : la clé étrangère composite (tirage_id, defi_id) vers tirage_proposition.
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

    // Modèle de données § 6 : suppressions en cascade depuis duo (dissolution, F2).
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

    // Le duo d'abord : sa suppression emporte les données de jeu, qui bloqueraient celle des joueurs.
    private function viderLaBase(): void
    {
        viderLaBase($this->pdo);
        // Ces tests chargent leurs propres défis : les données de référence partent aussi.
        $this->pdo->exec('DELETE FROM defi');
        $this->pdo->exec('DELETE FROM categorie');
    }

    private function reinitialiser(): void
    {
        exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/../sql/reinitialiser.php') . ' 2>&1', $sortie, $code);
        $this->assertSame(0, $code, implode("\n", $sortie));
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
