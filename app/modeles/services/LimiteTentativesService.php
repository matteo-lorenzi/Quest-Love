<?php
// Limitation des tentatives — A3 Connexion et B1 Salle d'attente — lot 1 — A. Règle commune à la connexion et au code d'invitation (cadrage § 6.6).

class LimiteTentativesService
{
    // 5 échecs en 15 minutes, puis 15 minutes d'attente (cadrage § 6.6).
    private const ECHECS_MAX = 5;
    private const FENETRE = '15 minutes';

    public function __construct(private TentativeDepot $tentatives) {}

    // Réserve un essai sur chaque clé (joueur, session…) avant que le service ne le vérifie.
    // Résultat : les numéros des essais réservés, ou l'heure de réessai (la plus tardive des clés) sans rien garder.
    // L'essai est écrit avant d'être compté, et on ne compte que ceux écrits avant lui : des requêtes simultanées
    // voient chacune les précédentes, donc seules les cinq premières passent, sans attendre le verdict des autres.
    // Un échec garde sa réservation ; un essai réussi la rend avec liberer(), il ne compte pas.
    /** @return int[]|DateTimeImmutable */
    public function reserver(array $cles, TypeTentative $type, DateTimeImmutable $maintenant): array|DateTimeImmutable
    {
        $ids = [];
        $attente = null;
        foreach ($cles as $cle) {
            $id = $this->tentatives->ajouter($cle, $type, $maintenant);
            $ids[] = $id;
            // max() classe null avant toute date.
            $attente = max($attente, $this->attente($cle, $type, $maintenant, $id));
        }
        if ($attente === null) {
            return $ids;
        }
        // Un essai refusé n'est pas enregistré, sinon chaque nouvel essai prolongerait l'attente.
        $this->liberer($ids);
        return $attente;
    }

    public function liberer(array $ids): void
    {
        foreach ($ids as $id) {
            $this->tentatives->supprimer($id);
        }
    }

    // Heure de réessai si la clé est en attente, sinon null. Ne compte que les essais écrits avant $avantId.
    // L'attente part du 5ᵉ échec, qui est le plus récent puisque les essais refusés ne sont pas gardés.
    // Une fenêtre comptée depuis maintenant libérerait trop tôt : les 5 échecs en sortent avant la fin de l'attente.
    // On remonte donc de 30 minutes : 15 pour que les 5 échecs aient eu lieu, 15 pour l'attente qui les suit.
    private function attente(string $cle, TypeTentative $type, DateTimeImmutable $maintenant, int $avantId): ?DateTimeImmutable
    {
        $echecs = $this->tentatives->datesDepuis($cle, $type, $maintenant->modify('-30 minutes'), $avantId);
        if (count($echecs) < self::ECHECS_MAX) {
            return null;
        }
        // $echecs est trié du plus récent au plus ancien : [0] est le dernier échec, [4] le 5ᵉ en remontant.
        $dernier = $echecs[0];
        $enRafale = $echecs[self::ECHECS_MAX - 1] > $dernier->modify('-' . self::FENETRE);
        $finAttente = $dernier->modify('+' . self::FENETRE);
        return $enRafale && $maintenant < $finAttente ? $finAttente : null;
    }
}
