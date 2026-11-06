<?php
// Contrôleur de la dissolution du duo — F2 — parcours 1 (appairage) — lot 1 — A.

class QuitterDuoControleur
{
    // Le routeur passe la connexion et l'heure ; le contrôleur construit lui-même ses dépôts et services (ADR-0001).
    public function __construct(private PDO $pdo, private DateTimeImmutable $maintenant) {}

    // GET : l'avertissement d'irréversibilité, la confirmation et l'annulation vers l'écran d'origine.
    public function afficher(): void
    {
        $joueur = Routeur::joueurConnecte($this->pdo);
        $service = AppairageService::construire($this->pdo);
        // Origine absente ou inconnue : l'écran de l'étape d'accès du joueur (relue en base).
        // TODO(simplif): pour un joueur en accès au jeu l'origine vaut « accueil », absent de la table du routeur : Annuler et ← mènent à G1, à corriger quand C1 existe.
        $origine = self::origine($_GET['origine'] ?? null);
        $retour = $origine !== null ? self::parametre($origine) : Routeur::ecranDeLEtape($service->etapeAcces($joueur));
        Vue::rendre('f2-quitter-duo', 'Quitter mon duo', [
            'retour' => $retour,
            'partenaire' => $service->pseudoPartenaire($joueur),
        ]);
    }

    // Écrans d'où F2 s'ouvre : l'énumération OrigineQuitterDuo est la liste fermée, l'annulation ne suit jamais un écran choisi librement dans l'adresse.
    // Origine validée contre l'énumération ; toute autre valeur (absente, inconnue, tableau) : null.
    public static function origine(mixed $origine): ?OrigineQuitterDuo
    {
        foreach (OrigineQuitterDuo::cases() as $cas) {
            if ($origine === self::parametre($cas)) {
                return $cas;
            }
        }
        return null;
    }

    // Seul endroit où un cas d'origine devient un nom d'écran : le paramètre « origine » de l'adresse, que les vues et l'annulation reprennent.
    public static function parametre(OrigineQuitterDuo $origine): string
    {
        return match ($origine) {
            OrigineQuitterDuo::SalleAttente => 'salle-attente',
            OrigineQuitterDuo::Onboarding => 'onboarding',
        };
    }

    // POST : le jeton CSRF a déjà été vérifié par le routeur. Le résultat (dissous ou déjà dissous) ne change pas l'écran suivant.
    public function dissoudre(): void
    {
        AppairageService::construire($this->pdo)->dissoudre(Routeur::joueurConnecte($this->pdo));
        Routeur::rediriger('salle-attente');
    }
}
