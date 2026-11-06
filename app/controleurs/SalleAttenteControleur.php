<?php
// Contrôleur de la salle d'attente — B1 — lot 1 — A.

class SalleAttenteControleur
{
    // Clé de session qui transporte la saisie et le refus du POST (ou du rejeu après connexion) vers le GET qui suit la redirection.
    private const RETOUR = 'retour_salle_attente';
    // Clé de session du code reçu par un lien avant authentification (doc technique § 3.6).
    private const CODE_CONSERVE = 'code_conserve';
    // Un code fait 8 caractères : au-delà, ce n'est pas un code, et la session n'a pas à garder n'importe quoi.
    private const LONGUEUR_MAX_CONSERVEE = 64;

    // Le routeur passe la connexion et l'heure ; le contrôleur construit lui-même ce dont il a besoin (ADR-0001).
    public function __construct(private PDO $pdo, private DateTimeImmutable $maintenant) {}

    // GET : afficher le code du joueur et la saisie, ou la variante « déjà en duo ».
    public function afficher(): void
    {
        $joueur = $this->joueurConnecte();
        $retour = $_SESSION[self::RETOUR] ?? ['saisie' => '', 'refus' => null, 'attente' => null];
        unset($_SESSION[self::RETOUR]);
        // Un lien d'invitation arrive avec ?code= : le champ est prérempli, sans rien valider (un GET n'écrit jamais d'essai).
        $recu = is_string($_GET['code'] ?? null) ? $_GET['code'] : null;

        // B1 vérifie la formation du duo à chaque chargement (cadrage § 8.5). Un duo formé pendant l'attente renvoie à l'accueil ;
        // le lien d'invitation d'un joueur déjà en duo montre au contraire la variante, pour qu'il comprenne pourquoi il ne peut pas rejoindre.
        if ($joueur->duoId !== null) {
            if ($recu === null) {
                Routeur::rediriger('accueil');
            }
            Vue::rendre('b1-salle-attente', 'Salle d\'attente', [
                'dejaEnDuo' => true,
                'partenaire' => (new JoueurDepot($this->pdo))->trouverPartenaire($joueur)?->pseudo,
            ]);
            return;
        }

        $joueur = self::service($this->pdo)->code($joueur, $this->maintenant);
        $saisie = $retour['saisie'] !== '' ? $retour['saisie'] : ($recu ?? '');
        $titre = ($retour['refus'] !== null || $retour['attente'] !== null) ? 'Erreur : Salle d\'attente' : 'Salle d\'attente';
        Vue::rendre('b1-salle-attente', $titre, [
            'dejaEnDuo' => false,
            // « XXXX-XXXX » se lit et se dicte mieux ; la saisie accepte les deux formes.
            'code' => substr($joueur->codeInvitation, 0, 4) . '-' . substr($joueur->codeInvitation, 4),
            'echeance' => $joueur->dateExpirationCode,
            'saisie' => $saisie,
            'refus' => $retour['refus'],
            'attente' => $retour['attente'],
        ]);
    }

    // POST : le jeton CSRF a déjà été vérifié par le routeur. Deux boutons, un champ caché `action` les distingue.
    public function traiter(): void
    {
        $joueur = $this->joueurConnecte();
        $service = self::service($this->pdo);

        // Lien « nouveau code » : écrase l'ancien code. Sans effet pour un joueur déjà en duo, qui n'a plus de code.
        if (($_POST['action'] ?? '') === 'nouveau-code') {
            if ($joueur->duoId === null) {
                $service->regenerer($joueur, $this->maintenant);
            }
            Routeur::rediriger('salle-attente');
        }

        // Aucune règle ici : le service contrôle tout et renvoie le duo formé, l'heure de réessai ou la raison du refus.
        $saisie = is_string($_POST['code'] ?? null) ? $_POST['code'] : '';
        $resultat = $service->rejoindre($joueur, $saisie, session_id(), $this->maintenant);
        if ($resultat instanceof Duo) {
            // TODO(simplif): la route onboarding n'existe pas (G1), à brancher avec B2.
            Routeur::rediriger('onboarding');
        }
        self::revenir($saisie, $resultat);
    }

    // Appelée par A2 et A3 quand elles affichent le formulaire : garde le code d'un lien d'invitation et renvoie celui en session.
    // TODO(simplif): l'entrée du lien est ?code= sur A2 et A3, faute d'A1 · invitation, à déplacer quand A1 existe.
    public static function conserverCodeRecu(): ?string
    {
        if (is_string($_GET['code'] ?? null)) {
            $_SESSION[self::CODE_CONSERVE] = mb_substr($_GET['code'], 0, self::LONGUEUR_MAX_CONSERVEE);
        }
        return $_SESSION[self::CODE_CONSERVE] ?? null;
    }

    // Aiguillage après inscription ou connexion (cadrage § 8.5) : en duo vers l'accueil, code conservé valide vers B2, sinon B1.
    // Le code est rejoué une seule fois, avec la limite des tentatives (cadrage § 6.6), et ne reste jamais en session.
    public static function aiguiller(PDO $pdo, Joueur $joueur, DateTimeImmutable $maintenant): never
    {
        $code = $_SESSION[self::CODE_CONSERVE] ?? null;
        unset($_SESSION[self::CODE_CONSERVE]);
        if ($joueur->duoId !== null) {
            Routeur::rediriger('accueil');
        }
        if ($code === null) {
            Routeur::rediriger('salle-attente');
        }
        $resultat = self::service($pdo)->rejoindre($joueur, $code, session_id(), $maintenant);
        if ($resultat instanceof Duo) {
            // TODO(simplif): la route onboarding n'existe pas (G1), à brancher avec B2.
            Routeur::rediriger('onboarding');
        }
        self::revenir($code, $resultat);
    }

    private static function service(PDO $pdo): AppairageService
    {
        return new AppairageService($pdo, new JoueurDepot($pdo), new DuoDepot($pdo), new TentativeDepot($pdo));
    }

    // Redirection après écriture (§ 2.4) : même un refus écrit un essai, donc on repasse par un GET.
    private static function revenir(string $saisie, DateTimeImmutable|RefusCode $resultat): never
    {
        $_SESSION[self::RETOUR] = [
            'saisie' => $saisie,
            'refus' => $resultat instanceof RefusCode ? $resultat : null,
            'attente' => $resultat instanceof DateTimeImmutable ? $resultat : null,
        ];
        Routeur::rediriger('salle-attente');
    }

    // Le joueur de la session, relu en base. Un compte supprimé entre-temps ramène à la connexion.
    private function joueurConnecte(): Joueur
    {
        $joueur = (new JoueurDepot($this->pdo))->trouverParId($_SESSION['joueur_id']);
        if ($joueur === null) {
            $_SESSION = [];
            Routeur::rediriger('connexion');
        }
        return $joueur;
    }
}
