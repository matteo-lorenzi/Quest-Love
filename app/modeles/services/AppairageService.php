<?php
// Service d'appairage — B1 Salle d'attente — lot 1 — A. Code d'invitation et formation du duo (cadrage § 6.6, § 7 ; doc technique § 3.6).

class AppairageService
{
    // 8 caractères parmi 32 : A-Z sans I ni O, 2 à 9 (cadrage § 7). 32 = 2⁵, donc un octet masqué sur 5 bits tire sans biais.
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    private const LONGUEUR_CODE = 8;
    private const VALIDITE = '60 minutes';
    // Tirages avant d'abandonner sur une collision de la clé unique : 32⁸ codes possibles, une collision est déjà exceptionnelle.
    private const ESSAIS_CODE = 5;

    private LimiteTentatives $limite;

    // Le contrôleur construit les dépôts et les passe ici : aucune dépendance cachée (ADR-0001).
    // Le service reçoit aussi la connexion : la formation du duo est une transaction, et les transactions vivent dans les services.
    public function __construct(
        private PDO $pdo,
        private JoueurDepot $joueurs,
        private DuoDepot $duos,
        TentativeDepot $tentatives,
    ) {
        $this->limite = new LimiteTentatives($tentatives);
    }

    // B1 — le code du joueur s'il est encore valable (c'est celui qu'il a pu donner), sinon un nouveau.
    public function code(Joueur $joueur, DateTimeImmutable $maintenant): Joueur
    {
        if ($joueur->codeInvitation !== null && $joueur->dateExpirationCode > $maintenant) {
            return $joueur;
        }
        return $this->regenerer($joueur, $maintenant);
    }

    // B1 — lien « nouveau code » : écrase le code, ce qui invalide l'ancien. Renvoie le joueur à jour.
    public function regenerer(Joueur $joueur, DateTimeImmutable $maintenant): Joueur
    {
        for ($essai = 0; $essai < self::ESSAIS_CODE; $essai++) {
            if ($this->joueurs->enregistrerCode($joueur->id, $this->tirerCode(), $maintenant->modify('+' . self::VALIDITE))) {
                return $this->joueurs->trouverParId($joueur->id);
            }
        }
        throw new RuntimeException('Aucun code libre après ' . self::ESSAIS_CODE . ' tirages.');
    }

    // B1 — saisie du code d'un partenaire : forme le duo des deux joueurs.
    public function rejoindre(Joueur $invite, string $saisie, string $cleSession, DateTimeImmutable $maintenant): Duo
    {
        $proprietaire = $this->joueurs->trouverParCode($saisie);
        $this->pdo->beginTransaction();
        try {
            $this->joueurs->verrouiller([$invite->id, $proprietaire->id]);
            $duoId = $this->duos->creer($maintenant);
            $this->joueurs->rattacherAuDuo($invite->id, $duoId);
            $this->joueurs->rattacherAuDuo($proprietaire->id, $duoId);
            $this->pdo->commit();
        } catch (Throwable $erreur) {
            $this->pdo->rollBack();
            throw $erreur;
        }
        return $this->duos->trouverParId($duoId);
    }

    private function tirerCode(): string
    {
        $code = '';
        foreach (str_split(random_bytes(self::LONGUEUR_CODE)) as $octet) {
            $code .= self::ALPHABET[ord($octet) & 31];
        }
        return $code;
    }
}
