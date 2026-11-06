<?php
// Service des comptes — A2 Inscription et A3 Connexion — lot 1 — A. Écran de référence : annoté étape par étape avec les numéros du doc technique § 2.2 (§ 2.4).

class CompteService
{
    // Longueurs d'un identifiant (cadrage § 6.6), reprises par le maxlength du formulaire et sa règle affichée.
    public const IDENTIFIANT_MIN = 3;
    public const IDENTIFIANT_MAX = 30;
    // Longueur maximale d'un pseudo (cadrage § 6.6) ; le minimum est 1, c'est-à-dire non vide.
    public const PSEUDO_MAX = 30;
    // Longueurs d'un mot de passe (cadrage § 6.6), reprises par la règle affichée sous le champ.
    public const MDP_MIN = 12;
    public const MDP_MAX = 64;
    // Empreinte d'un mot de passe que personne ne connaît, vérifiée quand l'identifiant est inconnu (A3) : même algorithme et mêmes coûts qu'à l'inscription.
    // TODO(simplif): figée avec les coûts actuels d'Argon2id de PHP, à régénérer si password_hash change ses défauts (un test le signale).
    public const EMPREINTE_LEURRE = '$argon2id$v=19$m=65536,t=4,p=1$ZjNyekpOdFFSbHoveU83dA$isKHs82PefoTlbEkJl5QO2Kk1RCeYykThnbU8ZFUv6w';

    private LimiteTentativesService $limite;

    // Le contrôleur construit les dépôts et les passe ici : aucune dépendance cachée (ADR-0001).
    // La règle de limitation est partagée avec AppairageService ; elle n'a pas d'autre état que le dépôt reçu.
    public function __construct(
        private JoueurDepot $joueurs,
        TentativeDepot $tentatives,
    ) {
        $this->limite = new LimiteTentativesService($tentatives);
    }

    // A2 — le contrôleur d'inscription appelle ce service avec les trois champs du formulaire.
    // Résultat : le Joueur créé, la liste des erreurs ['champ' => ['code', ...]] si une règle n'est pas respectée,
    // ou l'heure de réessai si l'adresse IP a dépassé le plafond d'inscriptions (aucun compte n'est alors créé).
    public function inscrire(string $identifiant, string $pseudo, string $mdp, DateTimeImmutable $maintenant, ?string $ip = null): Joueur|array|DateTimeImmutable
    {
        // Seule la clé IP compte, et chaque envoi se garde (réussi ou non) : une rafale d'inscriptions, ou de doublons sondés, est freinée.
        // Sans adresse (hors requête web), rien n'est limité.
        $reservation = $this->limite->reserver([], TypeTentative::Inscription, $maintenant, $ip);
        if ($reservation instanceof DateTimeImmutable) {
            return $reservation;
        }

        // NFC avant tout contrôle : une longueur ou une comparaison ne doit pas dépendre de l'encodage du clavier (doc technique § 3.6).
        // Un texte qui n'est pas de l'UTF-8 valide ne peut venir que d'une requête forgée : on le nomme par champ et on s'arrête,
        // les autres règles (longueur, lettres…) n'ont aucun sens sur des octets invalides.
        $textes = [
            'identifiant' => $this->normaliser($identifiant),
            'pseudo' => $this->normaliser($pseudo),
            'mdp' => $this->normaliser($mdp),
        ];
        $illisibles = array_keys(array_filter($textes, fn (?string $texte) => $texte === null));
        if ($illisibles) {
            return array_fill_keys($illisibles, ['encodage']);
        }
        ['identifiant' => $identifiant, 'pseudo' => $pseudo, 'mdp' => $mdp] = $textes;
        // Espaces de bord retirées pour l'identifiant et le pseudo seulement : le mot de passe, lui, est refusé plus bas, jamais rogné.
        $identifiant = $this->rogner($identifiant);
        $pseudo = $this->rogner($pseudo);

        $erreurs = [];
        $longueur = mb_strlen($identifiant);
        if ($longueur < self::IDENTIFIANT_MIN || $longueur > self::IDENTIFIANT_MAX) {
            $erreurs['identifiant'][] = 'longueur';
        }
        // Le modificateur u est indispensable : sans lui, \p{L} ne reconnaît pas les lettres accentuées.
        // \z et non $ : $ accepte un saut de ligne final, qui ferait un identifiant introuvable à la connexion (le contrôleur d'A3 le rogne).
        // Vide : déjà signalé par la longueur, inutile d'ajouter les caractères.
        if ($identifiant !== '' && !preg_match('/^[\p{L}\p{N}._-]+\z/u', $identifiant)) {
            $erreurs['identifiant'][] = 'caracteres';
        }
        // Doublon : l'interclassement de la colonne ignore majuscules et accents, comme pour connecter().
        // Un identifiant déjà refusé pour sa forme n'est pas cherché en base.
        if (!isset($erreurs['identifiant']) && $this->joueurs->trouverParIdentifiant($identifiant) !== null) {
            $erreurs['identifiant'][] = 'pris';
        }
        if ($pseudo === '') {
            $erreurs['pseudo'][] = 'vide';
        } elseif (mb_strlen($pseudo) > self::PSEUDO_MAX) {
            $erreurs['pseudo'][] = 'longueur';
        }
        // Seulement les caractères de contrôle (Cc) : d'autres caractères invisibles font partie d'émojis valides (ZWJ).
        if (preg_match('/\p{Cc}/u', $pseudo)) {
            $erreurs['pseudo'][] = 'controle';
        }
        $longueurMdp = mb_strlen($mdp);
        if ($longueurMdp < self::MDP_MIN || $longueurMdp > self::MDP_MAX) {
            $erreurs['mdp'][] = 'longueur';
        }
        if (!preg_match('/\p{Lu}/u', $mdp)) {
            $erreurs['mdp'][] = 'majuscule';
        }
        if (!preg_match('/\p{Ll}/u', $mdp)) {
            $erreurs['mdp'][] = 'minuscule';
        }
        if (!preg_match('/\p{N}/u', $mdp)) {
            $erreurs['mdp'][] = 'chiffre';
        }
        // Spécial : ni lettre, ni chiffre, ni marque combinante, ni contrôle ou invisible (cadrage § 6.6). L'espace et les émojis en font partie.
        if (!preg_match('/[^\p{L}\p{N}\p{M}\p{Cc}\p{Cf}]/u', $mdp)) {
            $erreurs['mdp'][] = 'special';
        }
        // Pas de trim silencieux : le joueur ne verrait pas ce qu'on a retiré, et son mot de passe ne serait plus celui qu'il croit (cadrage § 6.6).
        if (preg_match('/^\p{Z}|\p{Z}$/uD', $mdp)) {
            $erreurs['mdp'][] = 'espace_bord';
        }
        if (preg_match('/\p{Cc}/u', $mdp)) {
            $erreurs['mdp'][] = 'controle';
        }
        // Liste des mots de passe les plus courants (cadrage § 6.6), comparée sans majuscules ni accents.
        if (isset($this->motsDePasseCourants()[$this->plier($mdp)])) {
            $erreurs['mdp'][] = 'courant';
        }
        // Mêmes règles que l'unicité de l'identifiant : ni majuscules ni accents. Une valeur de moins de 3 caractères n'est pas comparée.
        if ($this->contient($mdp, $identifiant)) {
            $erreurs['mdp'][] = 'contient_identifiant';
        }
        if ($this->contient($mdp, $pseudo)) {
            $erreurs['mdp'][] = 'contient_pseudo';
        }
        if ($erreurs) {
            return $erreurs;
        }

        $id = $this->joueurs->ajouter($identifiant, $pseudo, password_hash($mdp, PASSWORD_ARGON2ID), $maintenant);
        // Perdant d'une course sur l'identifiant : même refus que le doublon vu plus haut.
        if ($id === null) {
            return ['identifiant' => ['pris']];
        }
        return $this->joueurs->trouverParId($id);
    }

    // Étape 6 — côté modèle : le contrôleur de A3 appelle ce service avec les champs du formulaire.
    // Résultat : le Joueur s'il est connecté, l'heure de réessai s'il doit attendre, null si l'identifiant ou le mot de passe est faux.
    public function connecter(string $identifiant, string $mdp, string $cleSession, DateTimeImmutable $maintenant, ?string $ip = null): Joueur|DateTimeImmutable|null
    {
        // Étape 6.0 — texte illisible ou identifiant trop long : aucun compte ne peut porter ce nom, on refuse sans toucher la base.
        // Le maxlength du formulaire ne protège pas d'une requête forgée, et la colonne tentative.cle est limitée à 64 caractères.
        // La NFC vient d'abord : l'identifiant est compté comme à l'inscription, où il a été normalisé (doc technique § 3.6).
        $identifiant = $this->normaliser($identifiant);
        $mdp = $this->normaliser($mdp);
        if ($identifiant === null || $mdp === null || mb_strlen($identifiant) > self::IDENTIFIANT_MAX) {
            return null;
        }

        // Étape 6.1 — essai réservé, ou attente en cours ?
        // Réservé avant le mot de passe : des requêtes simultanées se comptent donc entre elles, et seules les cinq premières passent.
        // Pendant l'attente, même le bon mot de passe est refusé, et l'essai refusé n'est pas gardé.
        // Trois compteurs : par identifiant, par session et par adresse IP, pour que changer l'un ou l'autre ne remette pas à zéro.
        $reservation = $this->limite->reserver([$identifiant, LimiteTentativesService::cleSession($cleSession)], TypeTentative::Connexion, $maintenant, $ip);
        if ($reservation instanceof DateTimeImmutable) {
            return $reservation;
        }

        // Étape 6.2 — recherche du joueur, puis vérification de l'empreinte.
        $joueur = $this->joueurs->trouverParIdentifiant($identifiant);
        // Mot de passe normalisé comme à l'inscription (étape 6.0), sinon un compte créé sur un appareil ne s'ouvrirait pas sur un autre.
        // Identifiant inconnu : on vérifie quand même contre l'empreinte leurre, pour que le temps de réponse ne révèle pas si le compte existe.
        $verifie = password_verify($mdp, $joueur->mdpHash ?? self::EMPREINTE_LEURRE);
        if ($joueur !== null && $verifie) {
            // Une connexion réussie ne compte pas : l'essai réservé est rendu.
            $this->limite->liberer($reservation);
            return $joueur;
        }

        // Étape 6.3 — échec : l'essai réservé reste, c'est lui qui compte sur les deux compteurs.
        // Un identifiant inconnu est compté comme un autre, et reçoit le même refus.
        return null;
    }

    // Forme canonique NFC (extension intl) : un « é » d'un bloc et un « e » suivi de son accent deviennent le même texte.
    // null si le texte n'est pas de l'UTF-8 valide (Normalizer::normalize renvoie alors false).
    private function normaliser(string $texte): ?string
    {
        $normalise = Normalizer::normalize($texte, Normalizer::FORM_C);
        return $normalise === false ? null : $normalise;
    }

    // Retire les espaces (tout séparateur Unicode, y compris l'espace insécable) au début et à la fin ; trim() ne connaît que l'espace ASCII.
    private function rogner(string $texte): string
    {
        return preg_replace('/^\p{Z}+|\p{Z}+$/uD', '', $texte);
    }

    // Le fichier est déjà plié : chaque ligne devient une clé, pour une recherche directe plutôt qu'un balayage de la liste.
    // Il est relu à chaque inscription (un millier de lignes) : TODO(simplif): pas de cache, à ajouter si l'inscription devient fréquente.
    private function motsDePasseCourants(): array
    {
        $lignes = file(__DIR__ . '/mots-de-passe-courants.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $mots = array_filter($lignes, fn (string $ligne) => !str_starts_with($ligne, '#'));
        return array_flip($mots);
    }

    // Vrai si $texte contient $valeur sans tenir compte des majuscules ni des accents.
    // Une valeur de moins de IDENTIFIANT_MIN caractères n'est pas comparée : un pseudo court bloquerait presque tout mot de passe (cadrage § 6.6).
    private function contient(string $texte, string $valeur): bool
    {
        return mb_strlen($valeur) >= self::IDENTIFIANT_MIN && str_contains($this->plier($texte), $this->plier($valeur));
    }

    // Minuscules et sans accents : la même égalité que l'unicité de l'identifiant (« Léa » et « lea » sont le même mot).
    // NFD sépare chaque lettre de son accent, qu'on retire ensuite (marques \p{Mn}).
    private function plier(string $texte): string
    {
        return mb_strtolower(preg_replace('/\p{Mn}/u', '', Normalizer::normalize($texte, Normalizer::FORM_D)));
    }
}
