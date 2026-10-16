<?php
// Dépôt des joueurs — A3 et tous les écrans connectés — lot 1 — A.

class JoueurDepot
{
    public function __construct(private PDO $pdo) {}

    // L'interclassement unicode_ci de la colonne rend la recherche insensible aux majuscules et aux accents (cadrage § 6.6).
    public function trouverParIdentifiant(string $identifiant): ?Joueur
    {
        $requete = $this->pdo->prepare('SELECT * FROM joueur WHERE identifiant = ?');
        $requete->execute([$identifiant]);
        $ligne = $requete->fetch();
        return $ligne ? $this->hydrater($ligne) : null;
    }

    // Sert au routeur pour le niveau d'accès duo (§ 3.6).
    public function trouverParId(int $id): ?Joueur
    {
        $requete = $this->pdo->prepare('SELECT * FROM joueur WHERE id = ?');
        $requete->execute([$id]);
        $ligne = $requete->fetch();
        return $ligne ? $this->hydrater($ligne) : null;
    }

    private function hydrater(array $ligne): Joueur
    {
        return new Joueur(
            (int) $ligne['id'],
            $ligne['identifiant'],
            $ligne['pseudo'],
            $ligne['mdp_hash'],
            $ligne['duo_id'] === null ? null : (int) $ligne['duo_id'],
            (int) $ligne['pv'],
            // createFromFormat lit une date stockée ; il ne consulte pas l'horloge (§ 2.4).
            DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $ligne['date_creation']),
        );
    }
}
