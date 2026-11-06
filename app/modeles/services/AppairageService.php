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

    // B1 — saisie du code d'un partenaire. Résultat : le Duo formé, l'heure de réessai si le joueur doit attendre, ou la raison du refus.
    public function rejoindre(Joueur $invite, string $saisie, string $cleSession, DateTimeImmutable $maintenant): Duo|DateTimeImmutable|RefusCode
    {
        // Texte sans forme de code : refusé sans toucher la base ni compter d'échec, comme connecter() (étape 6.0).
        $code = $this->normaliserCode($saisie);
        if ($code === null) {
            return RefusCode::Format;
        }
        // Un joueur déjà en duo n'a pas de saisie en B1 : seule une requête forgée arrive ici, elle ne compte pas.
        if ($invite->duoId !== null) {
            return RefusCode::DejaEnDuo;
        }
        // Attente en cours : vérifiée avant le code, et un essai refusé ici n'est pas enregistré, sinon chaque essai la prolongerait.
        // Deux compteurs, comme la connexion : changer de session ne remet pas celui du joueur à zéro, et inversement.
        $attente = max(
            $this->limite->attente((string) $invite->id, TypeTentative::Code, $maintenant),
            $this->limite->attente($cleSession, TypeTentative::Code, $maintenant),
        );
        if ($attente !== null) {
            return $attente;
        }
        $proprietaire = $this->joueurs->trouverParCode($code);
        // TODO(simplif): faux, régénéré et propriétaire déjà en duo ne sont pas distingués (le code est écrasé ou remis à nul), à garder l'historique des codes si le support en a besoin.
        if ($proprietaire === null) {
            return $this->echec(RefusCode::PasValable, $invite, $cleSession, $maintenant);
        }
        if ($proprietaire->id === $invite->id) {
            return $this->echec(RefusCode::PropreCode, $invite, $cleSession, $maintenant);
        }
        if ($proprietaire->dateExpirationCode <= $maintenant) {
            return $this->echec(RefusCode::Expire, $invite, $cleSession, $maintenant);
        }
        // Formation : une transaction, sous verrou des deux joueurs. Ce qui a été lu plus haut a pu changer entre-temps
        // (le propriétaire a régénéré son code, un autre invité l'a pris), donc tout est revérifié sur les lignes verrouillées.
        $duoId = null;
        $this->pdo->beginTransaction();
        try {
            $verrouilles = $this->joueurs->verrouiller([$invite->id, $proprietaire->id]);
            $inviteFrais = $verrouilles[$invite->id] ?? null;
            $proprietaireFrais = $verrouilles[$proprietaire->id] ?? null;
            if ($inviteFrais !== null && $inviteFrais->duoId === null && $proprietaireFrais !== null && $proprietaireFrais->duoId === null
                && $proprietaireFrais->codeInvitation === $code && $proprietaireFrais->dateExpirationCode > $maintenant) {
                $duoId = $this->duos->creer($maintenant);
                $this->joueurs->rattacherAuDuo($invite->id, $duoId);
                $this->joueurs->rattacherAuDuo($proprietaire->id, $duoId);
            }
            $this->pdo->commit();
        } catch (Throwable $erreur) {
            $this->pdo->rollBack();
            throw $erreur;
        }
        // L'échec est écrit une fois la transaction terminée : dedans, une erreur ultérieure l'annulerait avec le reste.
        if ($duoId === null) {
            return $this->echec(RefusCode::PasValable, $invite, $cleSession, $maintenant);
        }
        return $this->duos->trouverParId($duoId);
    }

    // Le code tel qu'on le range : NFC, sans espaces ni tirets (copier-coller de « XXXX-XXXX »), en majuscules.
    // null si ce ne peut pas être un code : UTF-8 invalide, ou autre chose que 8 caractères de l'alphabet.
    private function normaliserCode(string $saisie): ?string
    {
        $texte = Normalizer::normalize($saisie, Normalizer::FORM_C);
        if ($texte === false) {
            return null;
        }
        $code = mb_strtoupper(preg_replace('/[\p{Z}\p{Pd}]+/u', '', $texte));
        return preg_match('/^[A-HJ-NP-Z2-9]{8}$/D', $code) ? $code : null;
    }

    // Un refus qui compte est daté sur les deux compteurs : celui du joueur et celui de la session (cadrage § 6.6).
    private function echec(RefusCode $refus, Joueur $invite, string $cleSession, DateTimeImmutable $maintenant): RefusCode
    {
        $this->limite->echouer((string) $invite->id, TypeTentative::Code, $maintenant);
        $this->limite->echouer($cleSession, TypeTentative::Code, $maintenant);
        return $refus;
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
