<?php
// Limitation des tentatives — A3 Connexion et B1 Salle d'attente — lot 1 — A. Règle commune à la connexion et au code d'invitation (cadrage § 6.6).

class LimiteTentativesService
{
    // 5 échecs en 15 minutes, puis 15 minutes d'attente (cadrage § 6.6).
    private const ECHECS_MAX = 5;
    public const FENETRE = '15 minutes';
    // Plafond de la clé IP : plus large, car une adresse peut être partagée (box, école) ; il freine un script qui change de session à chaque requête.
    public const ECHECS_MAX_IP = 30;
    // Au-delà de 30 minutes (15 de fenêtre + 15 d'attente, voir attente()), un essai ne compte plus pour aucune clé : on le supprime.
    private const CONSERVATION = '-30 minutes';

    public function __construct(private TentativeDepot $tentatives) {}

    // Clé de limitation d'une session : l'identifiant de session ne s'écrit jamais en clair dans la table des tentatives.
    // SHA-256 en hexadécimal fait déjà 64 caractères, la taille de la colonne tentative.cle ; elle ne peut pas croiser un identifiant (30 au plus) ni un numéro de joueur.
    public static function cleSession(string $idSession): string
    {
        return substr(hash('sha256', $idSession), 0, 64);
    }

    // Clé de limitation d'une adresse IP, hachée comme celle de session ; le préfixe l'empêche de croiser une clé de session.
    public static function cleIp(string $ip): string
    {
        return hash('sha256', 'ip:' . $ip);
    }

    // Réserve un essai sur chaque clé (joueur, session…) avant que le service ne le vérifie.
    // Résultat : les numéros des essais réservés, ou l'heure de réessai (la plus tardive des clés) sans rien garder.
    // L'essai est écrit avant d'être compté, et on ne compte que ceux écrits avant lui : des requêtes simultanées
    // voient chacune les précédentes, donc seules les cinq premières passent, sans attendre le verdict des autres.
    // Un échec garde sa réservation ; un essai réussi la rend avec liberer(), il ne compte pas.
    // $ip : adresse du client, comptée en plus avec un plafond de 30 ; null (hors requête web) la laisse de côté.
    /** @return int[]|DateTimeImmutable */
    public function reserver(array $cles, TypeTentative $type, DateTimeImmutable $maintenant, ?string $ip = null): array|DateTimeImmutable
    {
        $this->tentatives->purger($maintenant->modify(self::CONSERVATION));
        $plafonds = array_fill_keys($cles, self::ECHECS_MAX);
        if ($ip !== null) {
            $plafonds[self::cleIp($ip)] = self::ECHECS_MAX_IP;
        }
        $ids = [];
        $attente = null;
        foreach ($plafonds as $cle => $plafond) {
            $id = $this->tentatives->ajouter((string) $cle, $type, $maintenant);
            $ids[] = $id;
            // max() classe null avant toute date.
            $attente = max($attente, $this->attente((string) $cle, $type, $maintenant, $id, $plafond));
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
    // L'attente part de l'échec qui atteint le plafond, qui est le plus récent puisque les essais refusés ne sont pas gardés.
    // Une fenêtre comptée depuis maintenant libérerait trop tôt : les 5 échecs en sortent avant la fin de l'attente.
    // On remonte donc de CONSERVATION (30 minutes) : 15 pour que les 5 échecs aient eu lieu, 15 pour l'attente qui les suit.
    private function attente(string $cle, TypeTentative $type, DateTimeImmutable $maintenant, int $avantId, int $plafond): ?DateTimeImmutable
    {
        $echecs = $this->tentatives->datesDepuis($cle, $type, $maintenant->modify(self::CONSERVATION), $avantId);
        if (count($echecs) < $plafond) {
            return null;
        }
        // $echecs est trié du plus récent au plus ancien : [0] est le dernier échec, [$plafond - 1] le dernier du plafond en remontant.
        $dernier = $echecs[0];
        $enRafale = $echecs[$plafond - 1] > $dernier->modify('-' . self::FENETRE);
        $finAttente = $dernier->modify('+' . self::FENETRE);
        return $enRafale && $maintenant < $finAttente ? $finAttente : null;
    }
}
