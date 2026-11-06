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
        // Le choix de la page (accueil, variante d'attente, règle demandée) est une règle : le service la porte, le contrôleur l'exécute.
        // Écart assumé avec la spec, qui nomme la variante d'attente &etape=attente : elle ne dépend que de l'étape d'accès (relue en base),
        // jamais du paramètre (voir AppairageService::pageOnboarding).
        $page = $this->service->pageOnboarding(Routeur::joueurConnecte($this->pdo), is_string($_GET['etape'] ?? null) ? $_GET['etape'] : null);
        if ($page->redirection !== null) {
            Routeur::rediriger(Routeur::ecranDeLEtape($page->redirection));
        }
        if ($page->attente) {
            Vue::rendre('b2-onboarding', 'En attente de ton partenaire', [
                'attente' => true,
                'partenaire' => $page->partenaire,
            ]);
            return;
        }
        // Pas de barre basse sur cet écran : elle ouvrirait un chemin vers C1 sans terminer l'onboarding (cadrage § 8.5).
        Vue::rendre('b2-onboarding', 'Les règles du jeu', [
            'attente' => false,
            'regle' => $page->regle,
            'etape' => $page->etape,
            'total' => $page->total,
        ]);
    }

    // POST : « Passer » et le bouton du dernier écran ont le même effet. Le jeton CSRF a déjà été vérifié par le routeur.
    public function terminer(): void
    {
        $this->service->terminerOnboarding(Routeur::joueurConnecte($this->pdo), $this->maintenant);
        // Le joueur est relu : l'écriture vient de changer son état. Accueil si le partenaire avait fini, variante d'attente sinon.
        Routeur::rediriger(Routeur::ecranDeLEtape($this->service->etapeAcces(Routeur::joueurConnecte($this->pdo))));
    }
}
