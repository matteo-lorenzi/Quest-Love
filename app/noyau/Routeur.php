<?php
// Routeur — tous écrans — lot 1 — commun. Annoté étape par étape avec les numéros du doc technique § 2.2 (§ 2.4).

class Routeur
{
    // Table de routage : écran => [contrôleur, méthode en GET, méthode en POST, accès].
    // null : l'écran n'accepte pas cette méthode HTTP. Accès : public, connecte, duo, ou jeu (en duo, onboarding terminé par les deux).
    // Elle grandit ticket par ticket, chaque écran arrivant avec son contrôleur ; liste cible au § 2.3.
    // Accueil, tirage et défi reçu y entreront avec l'accès 'jeu' (§ 3.6) ; le verrou est déjà en place à l'étape 5.
    private const ECRANS = [
        'inscription' => ['InscriptionControleur', 'afficher', 'inscrire', 'public'],
        'connexion' => ['ConnexionControleur', 'afficher', 'connecter', 'public'],
        'deconnexion' => ['ConnexionControleur', null, 'deconnecter', 'connecte'],
        'salle-attente' => ['SalleAttenteControleur', 'afficher', 'traiter', 'connecte'],
        'onboarding' => ['OnboardingControleur', 'afficher', 'terminer', 'duo'],
        // Accès 'duo', donc sans le verrou du jeu : un joueur dont le partenaire n'a pas fini l'onboarding doit pouvoir sortir (cadrage § 8.5).
        'quitter-duo' => ['QuitterDuoControleur', 'afficher', 'dissoudre', 'duo'],
        'regles' => ['ReglesControleur', 'afficher', null, 'connecte'],
        'erreur' => ['ErreurControleur', 'afficher', null, 'public'],
    ];

    // Écran affiché quand l'URL n'en précise aucun.
    // TODO(simplif): bienvenue absent de la table, donc G1 (404) sans paramètre, résolu quand A1 entre dans la table.
    private const ECRAN_PAR_DEFAUT = 'bienvenue';

    public static function traiter(PDO $pdo, DateTimeImmutable $maintenant): void
    {
        // Étape 2 — résolution de l'écran.
        // On lit ?ecran= ; un écran absent de la table affiche G1 (404), sans rien exécuter d'autre.
        $ecran = $_GET['ecran'] ?? self::ECRAN_PAR_DEFAUT;
        if (!is_string($ecran) || !isset(self::ECRANS[$ecran])) {
            self::erreur(404);
        }
        [$classe, $methodeGet, $methodePost, $acces] = self::ECRANS[$ecran];
        $connecte = isset($_SESSION['joueur_id']);

        // Étape 3 — rattrapage.
        // Pour un joueur connecté, RattrapageService rejoue les échéances manquées depuis sa dernière visite.
        // Il passe avant toute sortie, pour qu'une redirection reste possible.
        // TODO(simplif): rattrapage vide, à brancher quand RattrapageService existe (lot 1, périmètre B).

        // Étape 4 — file d'événements.
        // Tant que le rattrapage a laissé des événements à montrer, tout écran cède la place à l'écran evenement.
        if ($connecte && !empty($_SESSION['evenements']) && $ecran !== 'evenement') {
            self::rediriger('evenement');
        }

        // Étape 5 — préconditions d'accès (§ 3.6).
        // La décision est dans redirectionAcces(), sans base ni session. Le duo est relu en base à chaque requête :
        // une dissolution par le partenaire prend effet aussitôt.
        $etape = null;
        if ($connecte && ($acces === 'duo' || $acces === 'jeu')) {
            $joueur = (new JoueurDepot($pdo))->trouverParId($_SESSION['joueur_id']);
            $etape = $joueur === null ? EtapeAcces::SansDuo : AppairageService::construire($pdo)->etapeAcces($joueur);
        }
        $redirection = self::redirectionAcces($acces, $connecte, $etape);
        if ($redirection !== null) {
            self::rediriger($redirection);
        }

        // Étape 6 — CSRF, puis appel du contrôleur.
        // Toute écriture passe par POST, donc tout POST doit porter le jeton de la session ; sinon G1 (403).
        $methodeHttp = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($methodeHttp === 'POST' && !Securite::verifier($_POST['jeton'] ?? null)) {
            self::erreur(403);
        }
        // Un écran qui n'accepte pas la méthode reçue (GET sur deconnexion, par exemple) affiche G1 (405).
        $methode = $methodeHttp === 'POST' ? $methodePost : $methodeGet;
        if ($methode === null) {
            self::erreur(405);
        }
        // Le contrôleur reçoit la connexion et l'heure, et construit lui-même ses dépôts et services (ADR-0001).
        (new $classe($pdo, $maintenant))->$methode();
    }

    public static function rediriger(string $ecran): never
    {
        header('Location: index.php?ecran=' . urlencode($ecran));
        exit;
    }

    // Écran qui correspond à l'étape d'accès : la règle unique appliquée par le routeur, A3 et B1 (§ 3.6).
    public static function ecranDeLEtape(EtapeAcces $etape): string
    {
        return match ($etape) {
            EtapeAcces::SansDuo => 'salle-attente',
            EtapeAcces::OnboardingAFaire, EtapeAcces::Attente => 'onboarding',
            EtapeAcces::AccesAuJeu => 'accueil',
        };
    }

    // Décision de l'étape 5, sans base ni session : l'écran où renvoyer, ou null si l'accès est permis.
    // $etape : étape d'accès du joueur, nulle tant qu'elle n'a pas été lue (accès public, ou visiteur non connecté).
    public static function redirectionAcces(string $acces, bool $connecte, ?EtapeAcces $etape): ?string
    {
        if ($acces === 'public') {
            return null;
        }
        // Un écran réservé aux joueurs connectés renvoie les visiteurs vers la connexion (A3).
        if (!$connecte) {
            return 'connexion';
        }
        // Un écran réservé aux duos renvoie le joueur sans duo vers la salle d'attente (B1).
        if (($acces === 'duo' || $acces === 'jeu') && ($etape === null || $etape === EtapeAcces::SansDuo)) {
            return 'salle-attente';
        }
        // Un écran de jeu exige en plus que les deux joueurs aient fini l'onboarding, quel que soit le chemin d'arrivée (§ 3.6).
        if ($acces === 'jeu' && $etape !== EtapeAcces::AccesAuJeu) {
            return 'onboarding';
        }
        return null;
    }

    // never, comme rediriger() : après G1, plus rien ne doit s'exécuter.
    private static function erreur(int $code): never
    {
        (new ErreurControleur())->afficher($code);
        exit;
    }
}
