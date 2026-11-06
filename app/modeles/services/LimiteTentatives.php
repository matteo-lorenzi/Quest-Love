<?php
// Limitation des tentatives — A3 Connexion et B1 Salle d'attente — lot 1 — A. Règle commune à la connexion et au code d'invitation (cadrage § 6.6).

class LimiteTentatives
{
    // 5 échecs en 15 minutes, puis 15 minutes d'attente (cadrage § 6.6).
    private const ECHECS_MAX = 5;
    private const FENETRE = '15 minutes';

    public function __construct(private TentativeDepot $tentatives) {}

    // Heure de réessai si la clé est en attente, sinon null.
    // L'attente part du 5ᵉ échec, qui est le plus récent puisque les essais refusés ne sont pas enregistrés.
    // Une fenêtre comptée depuis maintenant libérerait trop tôt : les 5 échecs en sortent avant la fin de l'attente.
    // On remonte donc de 30 minutes : 15 pour que les 5 échecs aient eu lieu, 15 pour l'attente qui les suit.
    public function attente(string $cle, TypeTentative $type, DateTimeImmutable $maintenant): ?DateTimeImmutable
    {
        $echecs = $this->tentatives->datesDepuis($cle, $type, $maintenant->modify('-30 minutes'));
        if (count($echecs) < self::ECHECS_MAX) {
            return null;
        }
        // $echecs est trié du plus récent au plus ancien : [0] est le dernier échec, [4] le 5ᵉ en remontant.
        $dernier = $echecs[0];
        $enRafale = $echecs[self::ECHECS_MAX - 1] > $dernier->modify('-' . self::FENETRE);
        $finAttente = $dernier->modify('+' . self::FENETRE);
        return $enRafale && $maintenant < $finAttente ? $finAttente : null;
    }

    public function echouer(string $cle, TypeTentative $type, DateTimeImmutable $maintenant): void
    {
        $this->tentatives->ajouter($cle, $type, $maintenant);
    }
}
