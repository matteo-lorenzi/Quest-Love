<?php
// Dépôt des défis — C2 Tirage et C3 Défi reçu — Journée de jeu — lot 1 — B. Lecture seule : la bibliothèque se charge par sql/defis.sql (modèle de données § 2).

class DefiDepot
{
    private const SELECTION = 'SELECT defi.*, categorie.libelle FROM defi JOIN categorie ON categorie.id = defi.categorie_id';

    public function __construct(private PDO $pdo) {}

    public function trouverParId(int $id): ?Defi
    {
        $requete = $this->pdo->prepare(self::SELECTION . ' WHERE defi.id = ?');
        $requete->execute([$id]);
        $ligne = $requete->fetch();
        return $ligne ? $this->hydrater($ligne) : null;
    }

    // Toute la bibliothèque (une cinquantaine de lignes au plus) : le tirage filtre et tire au hasard côté PHP (cadrage § 4.6).
    /** @return Defi[] indexés par numéro */
    public function tous(): array
    {
        $defis = [];
        foreach ($this->pdo->query(self::SELECTION . ' ORDER BY defi.id') as $ligne) {
            $defis[(int) $ligne['id']] = $this->hydrater($ligne);
        }
        return $defis;
    }

    // Défis des missions révélées d'un destinataire, dans l'ordre de réception. Une mission annulée, jamais révélée, ne compte pas (cadrage § 4.6).
    /** @return int[] */
    public function recusPar(int $destinataireId): array
    {
        $requete = $this->pdo->prepare('SELECT defi_id FROM mission WHERE destinataire_id = ? AND date_revelation IS NOT NULL ORDER BY date_revelation, id');
        $requete->execute([$destinataireId]);
        return array_map('intval', $requete->fetchAll(PDO::FETCH_COLUMN));
    }

    // Défis proposés au destinataire après $jour sans être retenus : ceux de son tirage qui ne sont pas le défi de la mission (modèle de données § 2).
    // Une mission annulée reste le défi retenu de son tirage : il échappe à la fenêtre et peut revenir dès le lendemain (cadrage § 4.6).
    /** @return int[] */
    public function nonRetenusApres(int $destinataireId, DateTimeImmutable $jour): array
    {
        $requete = $this->pdo->prepare('SELECT tirage_proposition.defi_id FROM tirage_proposition JOIN tirage ON tirage.id = tirage_proposition.tirage_id
            WHERE tirage.destinataire_id = ? AND tirage.date_jour > ?
            AND NOT EXISTS (SELECT 1 FROM mission WHERE mission.tirage_id = tirage.id AND mission.defi_id = tirage_proposition.defi_id)');
        $requete->execute([$destinataireId, $jour->format('Y-m-d')]);
        return array_map('intval', $requete->fetchAll(PDO::FETCH_COLUMN));
    }

    private function hydrater(array $ligne): Defi
    {
        return new Defi((int) $ligne['id'], $ligne['titre'], $ligne['description'], (int) $ligne['categorie_id'], $ligne['libelle']);
    }
}
