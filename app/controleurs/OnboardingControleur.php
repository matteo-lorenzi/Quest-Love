<?php
// Contrôleur de l'onboarding des règles — B2 — parcours 1 (appairage) — lot 1 — A.

class OnboardingControleur
{
    private AppairageService $service;

    // Le routeur passe la connexion et l'heure ; le contrôleur construit lui-même ses dépôts et services (ADR-0001).
    // Un seul service par requête : il ne garde aucun état.
    public function __construct(private PDO $pdo, private DateTimeImmutable $maintenant)
    {
        $this->service = AppairageService::construire($pdo);
    }

    // GET : une règle par écran, choisie par &etape=.
    public function afficher(): void
    {
        $joueur = $this->joueurConnecte();
        $etapeAcces = $this->service->etapeAcces($joueur);
        // Les deux joueurs ont fini : B2 n'est plus un écran de passage.
        if ($etapeAcces === EtapeAcces::AccesAuJeu) {
            Routeur::rediriger('accueil');
        }
        // Il a fini avant son partenaire : variante d'attente, relue à chaque chargement.
        // Écart assumé avec la spec, qui nomme cette variante &etape=attente : elle ne dépend que de l'étape d'accès (relue en base),
        // jamais du paramètre. Elle sert donc quel que soit &etape=, y compris &etape=attente (lien de retour de B3), et le routeur
        // comme « Passer » y envoient le joueur sans paramètre ; exiger le paramètre ferait boucler dès qu'un lien l'oublie.
        // À l'inverse, &etape=attente d'un joueur qui n'a pas fini retombe sur la première règle : la variante n'est jamais montrée à tort.
        if ($etapeAcces === EtapeAcces::Attente) {
            Vue::rendre('b2-onboarding', 'En attente de ton partenaire', [
                'attente' => true,
                'partenaire' => (new JoueurDepot($this->pdo))->trouverPartenaire($joueur)?->pseudo,
            ]);
            return;
        }
        $regles = Regles::toutes();
        // Étape absente ou invalide : première règle ; au-delà de la dernière : accueil.
        $etape = is_string($_GET['etape'] ?? null) && preg_match('/^[0-9]+$/D', $_GET['etape']) ? max(1, (int) $_GET['etape']) : 1;
        if ($etape > count($regles)) {
            Routeur::rediriger('accueil');
        }
        // Pas de barre basse sur cet écran : elle ouvrirait un chemin vers C1 sans terminer l'onboarding (cadrage § 8.5).
        Vue::rendre('b2-onboarding', 'Les règles du jeu', [
            'attente' => false,
            'regle' => $regles[$etape - 1],
            'etape' => $etape,
            'total' => count($regles),
        ]);
    }

    // POST : « Passer » et le bouton du dernier écran ont le même effet. Le jeton CSRF a déjà été vérifié par le routeur.
    public function terminer(): void
    {
        $this->service->terminerOnboarding($this->joueurConnecte(), $this->maintenant);
        // Le joueur est relu : l'écriture vient de changer son état. Accueil si le partenaire avait fini, variante d'attente sinon.
        Routeur::rediriger(Routeur::ecranDeLEtape($this->service->etapeAcces($this->joueurConnecte())));
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
