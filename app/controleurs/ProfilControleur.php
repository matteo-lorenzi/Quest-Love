<?php
// Contrôleur du profil — F1 — parcours Profil, duo et système — lot 1 — A.

class ProfilControleur
{
    // Le routeur passe la connexion et l'heure ; le contrôleur construit lui-même ses dépôts et services (ADR-0001).
    public function __construct(private PDO $pdo, private DateTimeImmutable $maintenant) {}

    // GET : les informations du seul joueur connecté ; du partenaire, seul le pseudo est lu (cadrage § 10).
    public function afficher(): void
    {
        $joueur = Routeur::joueurConnecte($this->pdo);
        $service = AppairageService::construire($this->pdo);
        $etape = $service->etapeAcces($joueur);
        // TODO(simplif): la variante « sans duo » n'est pas écrite ; un joueur sans duo est renvoyé à B1 jusqu'au ticket #106.
        if ($etape === EtapeAcces::SansDuo) {
            Routeur::rediriger(Routeur::ecranDeLEtape($etape));
        }
        $elements = self::elementsAffiches($etape);
        Vue::rendre('f1-profil', 'Mon profil', [
            'pseudo' => $joueur->pseudo,
            'identifiant' => $joueur->identifiant,
            'carteDuo' => $elements['carteDuo'],
            'partenaire' => $elements['carteDuo'] ? $service->pseudoPartenaire($joueur) : null,
            'barre' => $elements['barre'],
            'retour' => $elements['retour'],
        ]);
    }

    // Éléments de F1 selon l'étape d'accès, sans base ni session : carte duo (et « Quitter mon duo »), barre basse, écran de la flèche ← (ou null).
    public static function elementsAffiches(EtapeAcces $etape): array
    {
        return match ($etape) {
            EtapeAcces::AccesAuJeu => ['carteDuo' => true, 'barre' => true, 'retour' => null],
            // Duo formé, pas encore activé : pas de barre, qui ouvrirait l'accueil avant la fin de l'onboarding ; la flèche ← mène à B2.
            EtapeAcces::OnboardingAFaire, EtapeAcces::Attente => ['carteDuo' => true, 'barre' => false, 'retour' => Routeur::ecranDeLEtape($etape)],
        };
    }
}
