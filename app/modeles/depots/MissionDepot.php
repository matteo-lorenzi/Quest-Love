<?php
// Dépôt des missions — C1 à C3 et D1 — Journée de jeu — lot 1 — B.

class MissionDepot
{
    public function __construct(private PDO $pdo) {}

    // Null si le tirage a déjà sa mission : la clé unique tirage_id refuse un second choix (modèle de données § 4).
    // Un défi hors des propositions du tirage est refusé par la clé composite (ADR-0003) et remonte : c'est une erreur de programmation.
    public function ajouter(Tirage $tirage, int $defiId, DateTimeImmutable $maintenant, DateTimeImmutable $dateLimite): ?int
    {
        try {
            $this->pdo->prepare('INSERT INTO mission (duo_id, tirage_id, defi_id, emetteur_id, destinataire_id, date_attribution, date_limite, statut) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute([$tirage->duoId, $tirage->id, $defiId, $tirage->emetteurId, $tirage->destinataireId, $maintenant->format('Y-m-d H:i:s'), $dateLimite->format('Y-m-d H:i:s'), StatutMission::AttribueNonRevele->value]);
        } catch (PDOException $erreur) {
            if (($erreur->errorInfo[1] ?? null) === 1062) {
                return null;
            }
            throw $erreur;
        }
        return (int) $this->pdo->lastInsertId();
    }

    public function trouverParId(int $id): ?Mission
    {
        $requete = $this->pdo->prepare('SELECT * FROM mission WHERE id = ?');
        $requete->execute([$id]);
        $ligne = $requete->fetch();
        return $ligne ? $this->hydrater($ligne) : null;
    }

    public function trouverParTirage(int $tirageId): ?Mission
    {
        $requete = $this->pdo->prepare('SELECT * FROM mission WHERE tirage_id = ?');
        $requete->execute([$tirageId]);
        $ligne = $requete->fetch();
        return $ligne ? $this->hydrater($ligne) : null;
    }

    // Les missions d'une journée passent par leur tirage, seul à porter date_jour.
    /** @return Mission[] zéro, une ou deux, une par émetteur du duo */
    public function trouverParDuoEtJour(int $duoId, DateTimeImmutable $jour): array
    {
        $requete = $this->pdo->prepare('SELECT mission.* FROM mission JOIN tirage ON tirage.id = mission.tirage_id WHERE mission.duo_id = ? AND tirage.date_jour = ?');
        $requete->execute([$duoId, $jour->format('Y-m-d')]);
        return array_map(fn (array $ligne) => $this->hydrater($ligne), $requete->fetchAll());
    }

    // Défis des missions révélées d'un destinataire, dans l'ordre de réception. Une mission annulée, jamais révélée, ne compte pas (cadrage § 4.6).
    /** @return int[] */
    public function recusPar(int $destinataireId): array
    {
        $requete = $this->pdo->prepare('SELECT defi_id FROM mission WHERE destinataire_id = ? AND date_revelation IS NOT NULL ORDER BY date_revelation, id');
        $requete->execute([$destinataireId]);
        return array_map('intval', $requete->fetchAll(PDO::FETCH_COLUMN));
    }

    // Missions d'un destinataire encore dans un statut donné : le rattrapage y cherche celles à expirer ou à annuler (index ix_mission_rattrapage).
    /** @return Mission[] */
    public function trouverParDestinataireEtStatut(int $destinataireId, StatutMission $statut): array
    {
        $requete = $this->pdo->prepare('SELECT * FROM mission WHERE destinataire_id = ? AND statut = ? ORDER BY date_limite');
        $requete->execute([$destinataireId, $statut->value]);
        return array_map(fn (array $ligne) => $this->hydrater($ligne), $requete->fetchAll());
    }

    // Écritures simples : le service vérifie la transition (modèle de données § 3) sous transaction avec verrou (#59).
    // La date limite est recalculée à la révélation : c'est elle qui ouvre le délai jusqu'à minuit.
    public function reveler(int $id, DateTimeImmutable $maintenant, DateTimeImmutable $dateLimite): void
    {
        $this->pdo->prepare('UPDATE mission SET statut = ?, date_revelation = ?, date_limite = ? WHERE id = ?')
            ->execute([StatutMission::EnCours->value, $maintenant->format('Y-m-d H:i:s'), $dateLimite->format('Y-m-d H:i:s'), $id]);
    }

    // Seule la première consultation compte : une découverte déjà posée n'est jamais déplacée.
    public function decouvrir(int $id, DateTimeImmutable $maintenant): void
    {
        $this->pdo->prepare('UPDATE mission SET date_decouverte = ? WHERE id = ? AND date_decouverte IS NULL')
            ->execute([$maintenant->format('Y-m-d H:i:s'), $id]);
    }

    public function valider(int $id, DateTimeImmutable $maintenant): void
    {
        $this->pdo->prepare('UPDATE mission SET statut = ?, date_validation = ? WHERE id = ?')
            ->execute([StatutMission::Valide->value, $maintenant->format('Y-m-d H:i:s'), $id]);
    }

    // Expiration et annulation ne posent aucune date : le statut suffit.
    public function changerStatut(int $id, StatutMission $statut): void
    {
        $this->pdo->prepare('UPDATE mission SET statut = ? WHERE id = ?')->execute([$statut->value, $id]);
    }

    private function hydrater(array $ligne): Mission
    {
        $instant = fn (?string $valeur) => $valeur === null ? null : DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $valeur);
        return new Mission(
            (int) $ligne['id'],
            (int) $ligne['duo_id'],
            (int) $ligne['tirage_id'],
            (int) $ligne['defi_id'],
            (int) $ligne['emetteur_id'],
            (int) $ligne['destinataire_id'],
            $instant($ligne['date_attribution']),
            $instant($ligne['date_revelation']),
            $instant($ligne['date_decouverte']),
            $instant($ligne['date_limite']),
            StatutMission::from($ligne['statut']),
            $instant($ligne['date_validation']),
        );
    }
}
