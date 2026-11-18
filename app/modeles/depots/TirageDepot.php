<?php
// Dépôt des tirages et de leurs propositions — C2 Tirage — Journée de jeu — lot 1 — B.

class TirageDepot
{
    public function __construct(private PDO $pdo) {}

    // Tirage et propositions en plusieurs INSERT : le service appelle sous transaction, pour qu'un tirage n'existe jamais sans ses trois défis.
    // Null si l'émetteur a déjà un tirage ce jour-là : la clé unique (emetteur_id, date_jour) tranche (modèle de données § 4).
    public function creer(int $duoId, int $emetteurId, int $destinataireId, DateTimeImmutable $jour, array $defiIds): ?int
    {
        try {
            $this->pdo->prepare('INSERT INTO tirage (duo_id, emetteur_id, destinataire_id, date_jour) VALUES (?, ?, ?, ?)')
                ->execute([$duoId, $emetteurId, $destinataireId, $jour->format('Y-m-d')]);
        } catch (PDOException $erreur) {
            if (($erreur->errorInfo[1] ?? null) === 1062) {
                return null;
            }
            throw $erreur;
        }
        $id = (int) $this->pdo->lastInsertId();
        $proposition = $this->pdo->prepare('INSERT INTO tirage_proposition (tirage_id, defi_id) VALUES (?, ?)');
        foreach ($defiIds as $defiId) {
            $proposition->execute([$id, $defiId]);
        }
        return $id;
    }

    public function trouverParEmetteurEtJour(int $emetteurId, DateTimeImmutable $jour): ?Tirage
    {
        $requete = $this->pdo->prepare('SELECT * FROM tirage WHERE emetteur_id = ? AND date_jour = ?');
        $requete->execute([$emetteurId, $jour->format('Y-m-d')]);
        $ligne = $requete->fetch();
        return $ligne ? $this->hydrater($ligne) : null;
    }

    /** @return Tirage[] zéro, un ou deux, un par émetteur du duo */
    public function trouverParDuoEtJour(int $duoId, DateTimeImmutable $jour): array
    {
        $requete = $this->pdo->prepare('SELECT * FROM tirage WHERE duo_id = ? AND date_jour = ?');
        $requete->execute([$duoId, $jour->format('Y-m-d')]);
        return array_map(fn (array $ligne) => $this->hydrater($ligne), $requete->fetchAll());
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

    private function hydrater(array $ligne): Tirage
    {
        $propositions = $this->pdo->prepare('SELECT defi_id FROM tirage_proposition WHERE tirage_id = ? ORDER BY id');
        $propositions->execute([$ligne['id']]);
        return new Tirage(
            (int) $ligne['id'],
            (int) $ligne['duo_id'],
            (int) $ligne['emetteur_id'],
            (int) $ligne['destinataire_id'],
            // « ! » met l'heure à minuit au lieu de reprendre celle de l'horloge (§ 2.4).
            DateTimeImmutable::createFromFormat('!Y-m-d', $ligne['date_jour']),
            array_map('intval', $propositions->fetchAll(PDO::FETCH_COLUMN)),
        );
    }
}
