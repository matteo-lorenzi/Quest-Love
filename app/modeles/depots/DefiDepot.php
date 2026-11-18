<?php
// Dépôt des défis — C2 Tirage et C3 Défi reçu — Journée de jeu — lot 1 — B. Lecture seule : la bibliothèque se charge par sql/defis.sql (modèle de données § 2).

class DefiDepot
{
    public function __construct(private PDO $pdo) {}

    public function trouverParId(int $id): ?Defi
    {
        $requete = $this->pdo->prepare('SELECT defi.*, categorie.libelle FROM defi JOIN categorie ON categorie.id = defi.categorie_id WHERE defi.id = ?');
        $requete->execute([$id]);
        $ligne = $requete->fetch();
        return $ligne ? $this->hydrater($ligne) : null;
    }

    // Toute la bibliothèque (une cinquantaine de lignes au plus) : le tirage filtre et tire au hasard côté PHP (cadrage § 4.6).
    /** @return Defi[] indexés par numéro */
    public function tous(): array
    {
        $defis = [];
        foreach ($this->pdo->query('SELECT defi.*, categorie.libelle FROM defi JOIN categorie ON categorie.id = defi.categorie_id ORDER BY defi.id') as $ligne) {
            $defis[(int) $ligne['id']] = $this->hydrater($ligne);
        }
        return $defis;
    }

    private function hydrater(array $ligne): Defi
    {
        return new Defi((int) $ligne['id'], $ligne['titre'], $ligne['description'], (int) $ligne['categorie_id'], $ligne['libelle']);
    }
}
