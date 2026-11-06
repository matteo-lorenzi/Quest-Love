<?php
// Contrôleur d'inscription — A2 — lot 1 — A.

class InscriptionControleur
{
    // Clé de session qui transporte les erreurs et la saisie du POST vers le GET qui suit la redirection.
    private const RETOUR = 'retour_inscription';

    // Le routeur passe la connexion et l'heure ; le contrôleur construit lui-même ce dont il a besoin (ADR-0001).
    public function __construct(private PDO $pdo, private DateTimeImmutable $maintenant) {}

    // GET : afficher le formulaire. Après un échec, le POST a redirigé ici : on reprend ses erreurs une seule fois.
    public function afficher(): void
    {
        $retour = $_SESSION[self::RETOUR] ?? ['identifiant' => '', 'pseudo' => '', 'erreurs' => []];
        unset($_SESSION[self::RETOUR]);
        // Un lien d'invitation arrive avec ?code= : on le garde en session pour le préremplir en B1 après l'inscription (doc technique § 3.6).
        $retour['codeRecu'] = SalleAttenteControleur::conserverCodeRecu();

        // « Erreur : » en tête du titre pour que le lecteur d'écran l'annonce au rechargement (RGAA 8.6), comme en A3.
        $titre = $retour['erreurs'] ? 'Erreur : Inscription' : 'Inscription';
        Vue::rendre('a2-inscription', $titre, $retour);
    }

    // POST : le jeton CSRF a déjà été vérifié par le routeur.
    public function inscrire(): void
    {
        // Un champ absent ou envoyé sous forme de tableau vaut une chaîne vide.
        // Aucun trim ici : le service retire les espaces de bord de l'identifiant et du pseudo, et refuse celles du mot de passe.
        $identifiant = is_string($_POST['identifiant'] ?? null) ? $_POST['identifiant'] : '';
        $pseudo = is_string($_POST['pseudo'] ?? null) ? $_POST['pseudo'] : '';
        $mdp = is_string($_POST['mdp'] ?? null) ? $_POST['mdp'] : '';

        // Aucune règle ici : le service contrôle tout (§ 2.4) et renvoie le Joueur créé ou les erreurs par champ.
        $resultat = (new CompteService(new JoueurDepot($this->pdo), new TentativeDepot($this->pdo)))
            ->inscrire($identifiant, $pseudo, $mdp, $this->maintenant);

        if (is_array($resultat)) {
            $this->revenir($identifiant, $pseudo, $resultat);
        }

        // Nouvel identifiant de session, contre la fixation de session (cadrage § 6.6) : le joueur est connecté d'office.
        session_regenerate_id(true);
        Securite::renouvelerJeton();
        $_SESSION['joueur_id'] = $resultat->id;

        // Un joueur tout juste inscrit n'a pas de duo : B1, où un code conservé est prérempli, sans être rejoué (un lien ne forme pas un duo à l'insu du joueur).
        // TODO(simplif): aucune limite au nombre d'inscriptions par session ou par adresse, le cadrage ne limite que la connexion et les codes, à reprendre si l'abus se présente.
        Routeur::rediriger('salle-attente');
    }

    // Redirection après écriture (§ 2.4) : on repasse par un GET. On rend l'identifiant et le pseudo saisis, jamais le mot de passe.
    private function revenir(string $identifiant, string $pseudo, array $erreurs): never
    {
        $_SESSION[self::RETOUR] = ['identifiant' => $identifiant, 'pseudo' => $pseudo, 'erreurs' => $erreurs];
        Routeur::rediriger('inscription');
    }
}
