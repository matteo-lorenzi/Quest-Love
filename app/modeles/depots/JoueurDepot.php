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

    // L'autre joueur du duo, pour la variante « déjà en duo » de B1. Nul si le joueur est sans duo.
    public function trouverPartenaire(Joueur $joueur): ?Joueur
    {
        $requete = $this->pdo->prepare('SELECT * FROM joueur WHERE duo_id = ? AND id <> ?');
        $requete->execute([$joueur->duoId, $joueur->id]);
        $ligne = $requete->fetch();
        return $ligne ? $this->hydrater($ligne) : null;
    }

    // Le code est sensible à la casse (colonne ascii_bin) : le service le normalise avant de chercher.
    public function trouverParCode(string $code): ?Joueur
    {
        $requete = $this->pdo->prepare('SELECT * FROM joueur WHERE code_invitation = ?');
        $requete->execute([$code]);
        $ligne = $requete->fetch();
        return $ligne ? $this->hydrater($ligne) : null;
    }

    // Verrou en écriture jusqu'à la fin de la transaction, pris dans l'ordre des numéros : deux appairages croisés ne se bloquent pas l'un l'autre.
    /** @return Joueur[] indexés par numéro */
    public function verrouiller(array $ids): array
    {
        sort($ids);
        $requete = $this->pdo->prepare('SELECT * FROM joueur WHERE id IN (' . implode(',', array_fill(0, count($ids), '?')) . ') ORDER BY id FOR UPDATE');
        $requete->execute($ids);
        $joueurs = [];
        foreach ($requete->fetchAll() as $ligne) {
            $joueurs[(int) $ligne['id']] = $this->hydrater($ligne);
        }
        return $joueurs;
    }

    // Le code ne sert qu'une fois : il disparaît avec le rattachement, et l'onboarding est à refaire avec ce duo.
    public function rattacherAuDuo(int $id, int $duoId): void
    {
        $this->pdo->prepare('UPDATE joueur SET duo_id = ?, onboarding_vu = 0, code_invitation = NULL, date_expiration_code = NULL WHERE id = ?')
            ->execute([$duoId, $id]);
    }

    // Remise à zéro d'un joueur dont le duo disparaît : $pv vient du service (règle du jeu). Ne touche pas au marqueur de dissolution.
    // Le duo_id, lui, retombe à nul par la suppression du duo (ON DELETE SET NULL).
    public function remettreAZero(int $id, int $pv): void
    {
        $this->pdo->prepare('UPDATE joueur SET pv = ?, onboarding_vu = 0 WHERE id = ?')->execute([$pv, $id]);
    }

    // Pose le marqueur « duo dissous par le partenaire » (D3 le lira). Jamais remis à 0 ici : un marqueur non lu doit survivre.
    public function signalerDissolution(int $id): void
    {
        $this->pdo->prepare('UPDATE joueur SET dissolution_a_signaler = 1 WHERE id = ?')->execute([$id]);
    }

    public function marquerOnboardingVu(int $id): void
    {
        $this->pdo->prepare('UPDATE joueur SET onboarding_vu = 1 WHERE id = ?')->execute([$id]);
    }

    // Écrase le code du joueur et repousse l'échéance : c'est ce qui invalide l'ancien (ADR-0002).
    // Sans effet sur un joueur déjà en duo : une régénération qui croise la formation ne doit pas lui recréer un code.
    // Avec $siExpireAvant, sans effet non plus si un code encore valable existe : un autre appareil vient de le créer, on ne l'écrase pas.
    // Faux si le code est déjà porté par un autre joueur : la clé unique tranche, et c'est ici qu'on reconnaît son erreur (1062), comme pour ajouter().
    public function enregistrerCode(int $id, string $code, DateTimeImmutable $expiration, ?DateTimeImmutable $siExpireAvant = null): bool
    {
        $sql = 'UPDATE joueur SET code_invitation = ?, date_expiration_code = ? WHERE id = ? AND duo_id IS NULL';
        $valeurs = [$code, $expiration->format('Y-m-d H:i:s'), $id];
        if ($siExpireAvant !== null) {
            $sql .= ' AND (code_invitation IS NULL OR date_expiration_code <= ?)';
            $valeurs[] = $siExpireAvant->format('Y-m-d H:i:s');
        }
        try {
            $this->pdo->prepare($sql)->execute($valeurs);
        } catch (PDOException $erreur) {
            if (($erreur->errorInfo[1] ?? null) === 1062) {
                return false;
            }
            throw $erreur;
        }
        return true;
    }

    private function hydrater(array $ligne): Joueur
    {
        return new Joueur(
            (int) $ligne['id'],
            $ligne['identifiant'],
            $ligne['pseudo'],
            $ligne['mdp_hash'],
            $ligne['code_invitation'],
            $ligne['date_expiration_code'] === null ? null : DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $ligne['date_expiration_code']),
            $ligne['duo_id'] === null ? null : (int) $ligne['duo_id'],
            (int) $ligne['pv'],
            (bool) $ligne['onboarding_vu'],
            (bool) $ligne['dissolution_a_signaler'],
            // createFromFormat lit une date stockée ; il ne consulte pas l'horloge (§ 2.4).
            DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $ligne['date_creation']),
        );
    }
}
