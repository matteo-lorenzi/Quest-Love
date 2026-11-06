<?php
// Dépôt des duos — B1 Salle d'attente — lot 1 — A.

class DuoDepot
{
    public function __construct(private PDO $pdo) {}

    // La date vient de $maintenant, jamais de l'horloge SQL (§ 3.8). Renvoie le numéro du duo créé.
    public function creer(DateTimeImmutable $maintenant): int
    {
        $this->pdo->prepare('INSERT INTO duo (date_creation) VALUES (?)')->execute([$maintenant->format('Y-m-d H:i:s')]);
        return (int) $this->pdo->lastInsertId();
    }

    public function trouverParId(int $id): ?Duo
    {
        $requete = $this->pdo->prepare('SELECT * FROM duo WHERE id = ?');
        $requete->execute([$id]);
        $ligne = $requete->fetch();
        return $ligne ? new Duo((int) $ligne['id'], DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $ligne['date_creation'])) : null;
    }
}
