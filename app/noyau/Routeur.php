<?php
// Routeur — tous écrans — lot 1 — commun. Annoté étape par étape avec les numéros du doc technique § 2.2 (§ 2.4).

class Routeur
{
    // Table de routage : écran => [contrôleur, méthode en GET, méthode en POST, accès].
    // null : l'écran n'accepte pas cette méthode HTTP. Accès : public, connecte ou duo.
    // Elle grandit ticket par ticket, chaque écran arrivant avec son contrôleur ; liste cible au § 2.3.
    private const ECRANS = [
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
        // Un écran réservé aux joueurs connectés renvoie les visiteurs vers la connexion (A3).
        if ($acces !== 'public' && !$connecte) {
            self::rediriger('connexion');
        }
        // TODO(simplif): niveau duo non vérifié, à lire via JoueurDepot (duo_id) avec #3 ; aucun écran ne l'utilise encore.

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

    // never, comme rediriger() : après G1, plus rien ne doit s'exécuter.
    private static function erreur(int $code): never
    {
        (new ErreurControleur())->afficher($code);
        exit;
    }
}
