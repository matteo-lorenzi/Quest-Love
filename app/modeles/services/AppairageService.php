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

    // B1 — donne au joueur un nouveau code valable 60 minutes et renvoie le joueur à jour.
    public function code(Joueur $joueur, DateTimeImmutable $maintenant): Joueur
    {
        for ($essai = 0; $essai < self::ESSAIS_CODE; $essai++) {
            if ($this->joueurs->enregistrerCode($joueur->id, $this->tirerCode(), $maintenant->modify('+' . self::VALIDITE))) {
                return $this->joueurs->trouverParId($joueur->id);
            }
        }
        throw new RuntimeException('Aucun code libre après ' . self::ESSAIS_CODE . ' tirages.');
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
