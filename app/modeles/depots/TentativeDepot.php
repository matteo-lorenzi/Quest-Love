<?php
// Dépôt des tentatives échouées — A3 (et B1 pour les codes) — lot 1 — A.

class TentativeDepot
{
    public function __construct(private PDO $pdo) {}

    // La date vient de $maintenant, jamais de l'horloge SQL : le décalage d'horloge s'applique aussi ici (§ 3.8).
    public function ajouter(string $cle, TypeTentative $type, DateTimeImmutable $date): void
    {
        $this->pdo->prepare('INSERT INTO tentative (cle, type, date) VALUES (?, ?, ?)')
            ->execute([$cle, $type->value, $date->format('Y-m-d H:i:s')]);
    }

    /** @return DateTimeImmutable[] du plus récent au plus ancien */
    public function datesDepuis(string $cle, TypeTentative $type, DateTimeImmutable $depuis): array
    {
        $requete = $this->pdo->prepare('SELECT date FROM tentative WHERE cle = ? AND type = ? AND date > ? ORDER BY date DESC');
        $requete->execute([$cle, $type->value, $depuis->format('Y-m-d H:i:s')]);
        return array_map(
            fn (string $date) => DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $date),
            $requete->fetchAll(PDO::FETCH_COLUMN)
        );
    }
}
