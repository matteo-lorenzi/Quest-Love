<?php
// Dépôt des joueurs — A2, A3 et tous les écrans connectés — lot 1 — A.

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

    // La date vient de $maintenant, jamais de l'horloge SQL : le décalage d'horloge de démonstration s'applique aussi à l'inscription (§ 2.4, § 3.8).
    // Renvoie l'identifiant attribué au nouveau joueur, ou null si l'identifiant est déjà pris.
    // Le contrôle préalable du service ne couvre pas deux inscriptions simultanées : la clé unique de la table tranche,
    // et c'est ici, où vit le SQL, qu'on reconnaît son erreur (code MySQL 1062, entrée dupliquée). Toute autre erreur remonte.
    public function ajouter(string $identifiant, string $pseudo, string $mdpHash, DateTimeImmutable $maintenant): ?int
    {
        try {
            $this->pdo->prepare('INSERT INTO joueur (identifiant, pseudo, mdp_hash, date_creation) VALUES (?, ?, ?, ?)')
                ->execute([$identifiant, $pseudo, $mdpHash, $maintenant->format('Y-m-d H:i:s')]);
        } catch (PDOException $erreur) {
            if (($erreur->errorInfo[1] ?? null) === 1062) {
                return null;
            }
            throw $erreur;
        }
        return (int) $this->pdo->lastInsertId();
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
