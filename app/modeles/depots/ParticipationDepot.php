<?php
// Dépôt des participations — C1 Accueil — Journée de jeu — lot 1 — B.

class ParticipationDepot
{
    public function __construct(private PDO $pdo) {}

    // Faux si le joueur a déjà répondu ce jour-là : la clé unique (joueur_id, date_jour) tranche, double clic compris (modèle de données § 4).
    public function ajouter(int $duoId, int $joueurId, DateTimeImmutable $jour, Reponse $reponse, DateTimeImmutable $maintenant): bool
    {
        try {
            $this->pdo->prepare('INSERT INTO participation (duo_id, joueur_id, date_jour, reponse, date_reponse) VALUES (?, ?, ?, ?, ?)')
                ->execute([$duoId, $joueurId, $jour->format('Y-m-d'), $reponse->value, $maintenant->format('Y-m-d H:i:s')]);
        } catch (PDOException $erreur) {
            if (($erreur->errorInfo[1] ?? null) === 1062) {
                return false;
            }
            throw $erreur;
        }
        return true;
    }

    public function trouverParJoueurEtJour(int $joueurId, DateTimeImmutable $jour): ?Participation
    {
        $requete = $this->pdo->prepare('SELECT * FROM participation WHERE joueur_id = ? AND date_jour = ?');
        $requete->execute([$joueurId, $jour->format('Y-m-d')]);
        $ligne = $requete->fetch();
        return $ligne ? $this->hydrater($ligne) : null;
    }

    /** @return Participation[] zéro, une ou deux, une par joueur du duo */
    public function trouverParDuoEtJour(int $duoId, DateTimeImmutable $jour): array
    {
        $requete = $this->pdo->prepare('SELECT * FROM participation WHERE duo_id = ? AND date_jour = ?');
        $requete->execute([$duoId, $jour->format('Y-m-d')]);
        return array_map(fn (array $ligne) => $this->hydrater($ligne), $requete->fetchAll());
    }

    private function hydrater(array $ligne): Participation
    {
        return new Participation(
            (int) $ligne['id'],
            (int) $ligne['duo_id'],
            (int) $ligne['joueur_id'],
            // « ! » met l'heure à minuit au lieu de reprendre celle de l'horloge (§ 2.4).
            DateTimeImmutable::createFromFormat('!Y-m-d', $ligne['date_jour']),
            Reponse::from($ligne['reponse']),
            DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $ligne['date_reponse']),
        );
    }
}
