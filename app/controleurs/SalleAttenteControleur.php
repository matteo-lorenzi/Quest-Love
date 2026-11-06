<?php
// Contrôleur de la salle d'attente — B1 — lot 1 — A.

class SalleAttenteControleur
{
    // Clé de session qui transporte la saisie et le refus du POST vers le GET qui suit la redirection.
    private const RETOUR = 'retour_salle_attente';
    // Clé de session du code reçu par un lien avant authentification (doc technique § 3.6).
    private const CODE_CONSERVE = 'code_conserve';
    // Un code fait 8 caractères : au-delà, ce n'est pas un code, et la session n'a pas à garder n'importe quoi.
    private const LONGUEUR_MAX_CONSERVEE = 64;

    // Le routeur passe la connexion et l'heure ; le contrôleur construit lui-même ses dépôts et services (ADR-0001).
    public function __construct(private PDO $pdo, private DateTimeImmutable $maintenant) {}

    // GET : afficher le code du joueur et la saisie, ou la variante « déjà en duo ».
    public function afficher(): void
    {
        $joueur = $this->joueurConnecte();
        $retour = $_SESSION[self::RETOUR] ?? ['saisie' => '', 'refus' => null, 'attente' => null];
        unset($_SESSION[self::RETOUR]);
        // Le code d'un lien est prérempli, jamais validé ici : un GET n'écrit pas d'essai, et aucun duo ne se forme sans le clic du joueur.
        // Il vient de l'URL (joueur déjà connecté) ou de la session (lien ouvert avant l'inscription ou la connexion) ; il ne sert qu'une fois.
        $recu = is_string($_GET['code'] ?? null) ? $_GET['code'] : null;
        $conserve = $_SESSION[self::CODE_CONSERVE] ?? null;
        unset($_SESSION[self::CODE_CONSERVE]);

        // B1 vérifie la formation du duo à chaque chargement (cadrage § 8.5). Un duo formé pendant l'attente renvoie à B2, ou à l'accueil
        // quand les deux ont fini ; le lien d'invitation d'un joueur déjà en duo montre au contraire la variante, pour qu'il comprenne pourquoi
        // il ne peut pas rejoindre. Elle n'arrive qu'après l'onboarding : tant qu'il n'est pas fini par les deux, B2 passe avant.
        if ($joueur->duoId !== null) {
            $etape = $this->service()->etapeAcces($joueur);
            if ($etape !== EtapeAcces::AccesAuJeu || $recu === null) {
                Routeur::rediriger(Routeur::ecranDeLEtape($etape));
            }
            Vue::rendre('b1-salle-attente', 'Salle d\'attente', [
                'dejaEnDuo' => true,
                'partenaire' => (new JoueurDepot($this->pdo))->trouverPartenaire($joueur)?->pseudo,
            ]);
            return;
        }

        // Le premier affichage crée le code : une initialisation idempotente, pas une action du joueur (doc technique § 3.6).
        $joueur = $this->service()->code($joueur, $this->maintenant);
        // Duo formé entre la lecture et la génération : plus de code à montrer, le rechargement renverra à l'accueil.
        if ($joueur->codeInvitation === null) {
            Routeur::rediriger('salle-attente');
        }
        $saisie = $retour['saisie'] !== '' ? $retour['saisie'] : ($recu ?? $conserve ?? '');
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
        $service = $this->service();

        // Lien « nouveau code » : écrase l'ancien code ; le service n'y touche pas si le joueur est déjà en duo.
        if (($_POST['action'] ?? '') === 'nouveau-code') {
            $service->regenerer($joueur, $this->maintenant);
            Routeur::rediriger('salle-attente');
        }

        // Aucune règle ici : le service contrôle tout et renvoie le duo formé, l'heure de réessai ou la raison du refus.
        $saisie = is_string($_POST['code'] ?? null) ? $_POST['code'] : '';
        $resultat = $service->rejoindre($joueur, $saisie, session_id(), $this->maintenant, $_SERVER['REMOTE_ADDR'] ?? null);
        if ($resultat instanceof Duo) {
            Routeur::rediriger('onboarding');
        }
        // Redirection après écriture (§ 2.4) : même un refus écrit un essai, donc on repasse par un GET.
        $_SESSION[self::RETOUR] = [
            'saisie' => $saisie,
            'refus' => $resultat instanceof RefusCode ? $resultat : null,
            'attente' => $resultat instanceof DateTimeImmutable ? $resultat : null,
        ];
        Routeur::rediriger('salle-attente');
    }

    // Appelée par A2 et A3 quand elles affichent le formulaire : garde le code d'un lien d'invitation et renvoie celui en session.
    // TODO(simplif): l'entrée du lien est ?code= sur A2 et A3, faute d'A1 · invitation, à déplacer quand A1 existe ; un lien vers B1 ouvert sans session perd son code (le routeur renvoie à la connexion).
    public static function conserverCodeRecu(): ?string
    {
        // Un ?code= vide n'est pas un code reçu : on ne garde rien.
        $recu = is_string($_GET['code'] ?? null) ? trim($_GET['code']) : '';
        if ($recu !== '') {
            $_SESSION[self::CODE_CONSERVE] = mb_substr($recu, 0, self::LONGUEUR_MAX_CONSERVEE);
        }
        return $_SESSION[self::CODE_CONSERVE] ?? null;
    }

    private function service(): AppairageService
    {
        return AppairageService::construire($this->pdo);
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
