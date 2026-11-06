<?php
// Dépôt des tentatives échouées — A3 (et B1 pour les codes) — lot 1 — A.

class TentativeDepot
{
    public function __construct(private PDO $pdo) {}

    // La date vient de $maintenant, jamais de l'horloge SQL : le décalage d'horloge s'applique aussi ici (§ 3.8).
    // Renvoie le numéro de la ligne : la limitation s'en sert pour ne compter que les essais écrits avant le sien.
    public function ajouter(string $cle, TypeTentative $type, DateTimeImmutable $date): int
    {
        $this->pdo->prepare('INSERT INTO tentative (cle, type, date) VALUES (?, ?, ?)')
            ->execute([$cle, $type->value, $date->format('Y-m-d H:i:s')]);
        return (int) $this->pdo->lastInsertId();
    }

    public function supprimer(int $id): void
    {
        $this->pdo->prepare('DELETE FROM tentative WHERE id = ?')->execute([$id]);
    }

    // Sans cela la table des tentatives grossirait sans fin : les essais trop vieux ne comptent plus pour aucune limite.
    public function purger(DateTimeImmutable $avant): void
    {
        $this->pdo->prepare('DELETE FROM tentative WHERE date < ?')->execute([$avant->format('Y-m-d H:i:s')]);
    }

    /** @return DateTimeImmutable[] du plus récent au plus ancien */
    public function datesDepuis(string $cle, TypeTentative $type, DateTimeImmutable $depuis, int $avantId): array
    {
        $requete = $this->pdo->prepare('SELECT date FROM tentative WHERE cle = ? AND type = ? AND date > ? AND id < ? ORDER BY date DESC');
        $requete->execute([$cle, $type->value, $depuis->format('Y-m-d H:i:s'), $avantId]);
        return array_map(
            fn (string $date) => DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $date),
            $requete->fetchAll(PDO::FETCH_COLUMN)
        );
    }
}
