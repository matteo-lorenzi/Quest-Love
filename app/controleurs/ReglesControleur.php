<?php
// Contrôleur des règles complètes — B3 — parcours 1 (appairage) — lot 1 — A.

class ReglesControleur
{
    // Le routeur passe la connexion et l'heure ; B3 n'a besoin ni de l'un ni de l'autre (ADR-0001).
    public function __construct(private PDO $pdo, private DateTimeImmutable $maintenant) {}

    // GET : toutes les règles en liste, lues à la même source que B2. L'origine et l'étape ne servent qu'au lien de retour.
    public function afficher(): void
    {
        Vue::rendre('b3-regles', 'Les règles du jeu', [
            'regles' => Regles::toutes(),
            'retour' => self::requeteDeRetour($_GET['origine'] ?? null, $_GET['etape'] ?? null),
        ]);
    }

    // Écrans d'où B3 s'ouvre : l'énumération OrigineRegles est la liste fermée, le retour ne suit jamais un écran choisi librement dans l'adresse.
    // TODO(simplif): le profil (F1) n'existe pas, son lien « Règles complètes » (origine=profil) reste à poser avec lui.
    // Origine de B3 validée contre l'énumération ; toute autre valeur (absente, inconnue, tableau) : accueil.
    public static function origine(mixed $origine): OrigineRegles
    {
        return is_string($origine) ? (OrigineRegles::tryFrom($origine) ?? OrigineRegles::Accueil) : OrigineRegles::Accueil;
    }

    // Requête (sans « index.php? ») du retour de B3 vers son origine ; l'origine est validée par origine() (liste fermée).
    // Avec B2 pour origine, l'étape (chiffres ou « attente ») est reprise ; étape invalide : B2 sans étape, donc première règle.
    public static function requeteDeRetour(mixed $origine, mixed $etape): string
    {
        $origine = self::origine($origine);
        if ($origine === OrigineRegles::Onboarding && is_string($etape) && preg_match('/^([0-9]+|attente)$/D', $etape)) {
            return "ecran=onboarding&etape=$etape";
        }
        return 'ecran=' . $origine->value;
    }
}
