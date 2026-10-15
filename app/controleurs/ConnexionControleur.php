<?php
// Contrôleur de connexion et de déconnexion — A3 — lot 1 — A. Écran de référence : annoté étape par étape avec les numéros du doc technique § 2.2 (§ 2.4).

class ConnexionControleur
{
    // Clé de session qui transporte les erreurs et la saisie du POST vers le GET qui suit la redirection.
    private const RETOUR = 'retour_connexion';

    // Le routeur passe la connexion et l'heure ; le contrôleur construit lui-même ce dont il a besoin (ADR-0001).
    public function __construct(private PDO $pdo, private DateTimeImmutable $maintenant) {}

    // Étape 6 — GET : afficher le formulaire.
    // Après un échec, le POST a redirigé ici : on reprend ses erreurs une seule fois, puis on les efface.
    public function afficher(): void
    {
        $retour = $_SESSION[self::RETOUR] ?? ['identifiant' => '', 'erreurs' => [], 'attente' => null];
        unset($_SESSION[self::RETOUR]);

        // Le titre de la page commence par « Erreur : » pour que le lecteur d'écran l'annonce au rechargement (RGAA 8.6).
        $titre = ($retour['erreurs'] || $retour['attente']) ? 'Erreur : Connexion' : 'Connexion';
        Vue::rendre('a3-connexion', $titre, $retour);
    }

    // Étape 6 — POST : le jeton CSRF a déjà été vérifié par le routeur.
    public function connecter(): void
    {
        // Étape 6.1 — lecture de la requête.
        // Un champ absent ou envoyé sous forme de tableau vaut une chaîne vide.
        $identifiant = is_string($_POST['identifiant'] ?? null) ? trim($_POST['identifiant']) : '';
        $mdp = is_string($_POST['mdp'] ?? null) ? $_POST['mdp'] : '';

        // Étape 6.2 — champs vides : une erreur de saisie, pas un essai de mot de passe.
        // Ni la base ni le compteur d'échecs ne sont touchés.
        $erreurs = [];
        if ($identifiant === '') {
            $erreurs['identifiant'] = 'vide';
        }
        if ($mdp === '') {
            $erreurs['mdp'] = 'vide';
        }
        if ($erreurs) {
            $this->revenir($identifiant, $erreurs);
        }

        // Étape 6.3 — appel du service, qui porte toute la règle (cadrage § 6.6).
        // L'identifiant de session sert de second compteur, lu avant d'être régénéré.
        $resultat = (new CompteService(new JoueurDepot($this->pdo), new TentativeDepot($this->pdo)))
            ->connecter($identifiant, $mdp, session_id(), $this->maintenant);

        // Étape 6.4 — refus : attente en cours, ou identifiants incorrects (un seul message pour les deux cas).
        if ($resultat instanceof DateTimeImmutable) {
            $this->revenir($identifiant, [], $resultat);
        }
        if ($resultat === null) {
            $this->revenir($identifiant, ['general' => 'incorrect']);
        }

        // Étape 6.5 — succès : nouvel identifiant de session, contre la fixation de session (cadrage § 6.6).
        session_regenerate_id(true);
        $_SESSION['joueur_id'] = $resultat->id;

        // Étape 6.6 — aiguillage (cadrage § 8.5) : en duo vers l'accueil, sinon vers la salle d'attente.
        // TODO(simplif): pas de branche vers B2 avec un code d'invitation conservé, à ajouter avec AppairageService.
        Routeur::rediriger($resultat->duoId !== null ? 'accueil' : 'salle-attente');
    }

    // POST seulement (la table du routeur refuse le GET) : un lien ne peut pas déconnecter quelqu'un à son insu.
    public function deconnecter(): void
    {
        // On vide la session et on change son identifiant : l'ancien cookie ne donne plus accès à rien.
        $_SESSION = [];
        session_regenerate_id(true);
        Routeur::rediriger('bienvenue');
    }

    // Redirection après écriture (§ 2.4) : même un échec écrit une tentative, donc on repasse par un GET.
    private function revenir(string $identifiant, array $erreurs, ?DateTimeImmutable $attente = null): never
    {
        $_SESSION[self::RETOUR] = ['identifiant' => $identifiant, 'erreurs' => $erreurs, 'attente' => $attente];
        Routeur::rediriger('connexion');
    }
}
