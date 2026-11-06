<?php
// Service d'appairage — B1 Salle d'attente — lot 1 — A. Code d'invitation et formation du duo (cadrage § 6.6, § 7 ; doc technique § 3.6).

class AppairageService
{
    // 8 caractères parmi 32 : A-Z sans I ni O, 2 à 9 (cadrage § 7). 32 = 2⁵, donc un octet masqué sur 5 bits tire sans biais.
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    private const LONGUEUR_CODE = 8;
    private const VALIDITE = '60 minutes';
    // Tirages avant d'abandonner sur une collision de la clé unique : 32⁸ codes possibles, une collision est déjà exceptionnelle.
    private const ESSAIS_CODE = 5;

    private LimiteTentativesService $limite;

    // Les dépôts sont construits par l'appelant, contrôleur ou routeur (ADR-0001). La règle de limitation, elle, est construite ici depuis le dépôt reçu : elle n'a pas d'autre état.
    // Le service reçoit aussi la connexion : la formation du duo est une transaction, et les transactions vivent dans les services.
    public function __construct(
        private PDO $pdo,
        private JoueurDepot $joueurs,
        private DuoDepot $duos,
        TentativeDepot $tentatives,
    ) {
        $this->limite = new LimiteTentativesService($tentatives);
    }

    // Le joueur de la session lu en base, ou null si son compte n'existe plus : l'unique lecture du joueur connecté (Routeur::joueurConnecte).
    public function joueur(int $id): ?Joueur
    {
        return $this->joueurs->trouverParId($id);
    }

    // Pseudo du partenaire du joueur, ou null s'il n'a pas de duo : B1 (variante), B2 (attente) et F2 l'affichent.
    public function pseudoPartenaire(Joueur $joueur): ?string
    {
        return $this->joueurs->trouverPartenaire($joueur)?->pseudo;
    }

    // B1 — aiguillage de l'affichage. B1 vérifie la formation du duo à chaque chargement (cadrage § 8.5). Un duo formé pendant l'attente renvoie à B2,
    // ou à l'accueil quand les deux ont fini ; le lien d'invitation ($lienRecu) d'un joueur déjà en duo montre au contraire la variante, pour qu'il comprenne
    // pourquoi il ne peut pas rejoindre. Elle n'arrive qu'après l'onboarding : tant qu'il n'est pas fini par les deux, B2 passe avant.
    public function salleAttente(Joueur $joueur, bool $lienRecu, DateTimeImmutable $maintenant): AiguillageSalleAttente
    {
        if ($joueur->duoId !== null) {
            $etape = $this->etapeAcces($joueur);
            if ($etape !== EtapeAcces::AccesAuJeu || !$lienRecu) {
                return new AiguillageSalleAttente($etape, false, false, null, null);
            }
            return new AiguillageSalleAttente(null, false, true, $this->pseudoPartenaire($joueur), null);
        }
        // Le premier affichage crée le code : une initialisation idempotente, pas une action du joueur (doc technique § 3.6).
        $joueur = $this->code($joueur, $maintenant);
        // Duo formé entre la lecture et la génération : plus de code à montrer, B1 se recharge (indicateur « recharger ») et prendra la branche ci-dessus.
        if ($joueur->codeInvitation === null) {
            return new AiguillageSalleAttente(null, true, false, null, null);
        }
        return new AiguillageSalleAttente(null, false, false, null, $joueur);
    }

    // B1 — le code à préremplir : la saisie refusée au POST précédent, sinon le code de l'URL, sinon celui conservé en session (lien ouvert avant la connexion).
    public function saisieAPrefiller(string $saisieRetour, ?string $recu, ?string $conserve): string
    {
        return $saisieRetour !== '' ? $saisieRetour : ($recu ?? $conserve ?? '');
    }

    // B2 — la page à montrer. $etapeDemandee : le &etape= brut, nul s'il est absent.
    // Les deux ont fini : B2 n'est plus un écran de passage. Il a fini avant son partenaire : variante d'attente, qui ne dépend que de l'étape d'accès
    // (relue en base), jamais du paramètre : exiger &etape=attente ferait boucler dès qu'un lien l'oublie.
    public function pageOnboarding(Joueur $joueur, ?string $etapeDemandee): PageOnboarding
    {
        $etapeAcces = $this->etapeAcces($joueur);
        if ($etapeAcces === EtapeAcces::AccesAuJeu) {
            return new PageOnboarding(EtapeAcces::AccesAuJeu, false, null, null, null, null);
        }
        if ($etapeAcces === EtapeAcces::Attente) {
            return new PageOnboarding(null, true, $this->pseudoPartenaire($joueur), null, null, null);
        }
        $regles = Regles::toutes();
        // Étape absente ou invalide : première règle ; au-delà de la dernière : accueil.
        $etape = $etapeDemandee !== null && preg_match('/^[0-9]+$/D', $etapeDemandee) ? max(1, (int) $etapeDemandee) : 1;
        if ($etape > count($regles)) {
            return new PageOnboarding(EtapeAcces::AccesAuJeu, false, null, null, null, null);
        }
        return new PageOnboarding(null, false, null, $regles[$etape - 1], $etape, count($regles));
    }

    // B1 — le code du joueur s'il est encore valable (c'est celui qu'il a pu donner), sinon un nouveau.
    public function code(Joueur $joueur, DateTimeImmutable $maintenant): Joueur
    {
        if ($joueur->codeInvitation !== null && $joueur->dateExpirationCode > $maintenant) {
            return $joueur;
        }
        return $this->ecrireCode($joueur, $maintenant, $maintenant);
    }

    // B1 — lien « nouveau code » : écrase le code, ce qui invalide l'ancien. Renvoie le joueur à jour.
    public function regenerer(Joueur $joueur, DateTimeImmutable $maintenant): Joueur
    {
        return $this->ecrireCode($joueur, $maintenant, null);
    }

    // $siExpireAvant : le premier affichage n'écrit que si aucun code valable n'existe, pour ne pas écraser celui qu'un autre appareil vient de créer.
    // Le joueur relu porte ce que la base contient, que cet appel l'ait écrit ou non.
    private function ecrireCode(Joueur $joueur, DateTimeImmutable $maintenant, ?DateTimeImmutable $siExpireAvant): Joueur
    {
        for ($essai = 0; $essai < self::ESSAIS_CODE; $essai++) {
            if ($this->joueurs->enregistrerCode($joueur->id, $this->tirerCode(), $maintenant->modify('+' . self::VALIDITE), $siExpireAvant)) {
                return $this->joueurs->trouverParId($joueur->id);
            }
        }
        throw new RuntimeException('Aucun code libre après ' . self::ESSAIS_CODE . ' tirages.');
    }

    // B1 — saisie du code d'un partenaire. Résultat : le Duo formé, l'heure de réessai si le joueur doit attendre, ou la raison du refus.
    public function rejoindre(Joueur $invite, string $saisie, string $cleSession, DateTimeImmutable $maintenant, ?string $ip = null): Duo|DateTimeImmutable|RefusCode
    {
        // Texte sans forme de code : refusé sans toucher la base ni compter d'échec, comme connecter() (étape 6.0).
        $code = $this->normaliserCode($saisie);
        if ($code === null) {
            return RefusCode::Format;
        }
        // Un joueur déjà en duo n'a pas de saisie en B1 : seule une requête forgée arrive ici, elle ne compte pas.
        if ($invite->duoId !== null) {
            return RefusCode::DejaEnDuo;
        }
        // Essai réservé avant la vérification, comme la connexion : des requêtes simultanées se comptent entre elles.
        // Trois compteurs (joueur, session, IP) : changer de session ne remet pas celui du joueur à zéro, et inversement. Pendant l'attente, l'essai n'est pas gardé.
        // Un refus qui compte garde sa réservation ; la formation du duo et le refus « déjà en duo » la rendent.
        $reservation = $this->limite->reserver([(string) $invite->id, LimiteTentativesService::cleSession($cleSession)], TypeTentative::Code, $maintenant, $ip);
        if ($reservation instanceof DateTimeImmutable) {
            return $reservation;
        }
        $proprietaire = $this->joueurs->trouverParCode($code);
        // TODO(simplif): faux, régénéré et propriétaire déjà en duo ne sont pas distingués (le code est écrasé ou remis à nul), à garder l'historique des codes si le support en a besoin.
        if ($proprietaire === null) {
            return RefusCode::PasValable;
        }
        if ($proprietaire->id === $invite->id) {
            return RefusCode::PropreCode;
        }
        if ($proprietaire->dateExpirationCode <= $maintenant) {
            return RefusCode::Expire;
        }
        // Formation : une transaction, sous verrou des deux joueurs. Ce qui a été lu plus haut a pu changer entre-temps
        // (le propriétaire a régénéré son code, un autre invité l'a pris), donc tout est revérifié sur les lignes verrouillées.
        $duoId = null;
        $inviteFrais = null;
        $this->pdo->beginTransaction();
        try {
            $verrouilles = $this->joueurs->verrouiller([$invite->id, $proprietaire->id]);
            $inviteFrais = $verrouilles[$invite->id] ?? null;
            $proprietaireFrais = $verrouilles[$proprietaire->id] ?? null;
            if ($inviteFrais !== null && $inviteFrais->duoId === null && $proprietaireFrais !== null && $proprietaireFrais->duoId === null
                && $proprietaireFrais->codeInvitation === $code && $proprietaireFrais->dateExpirationCode > $maintenant) {
                $duoId = $this->duos->creer($maintenant);
                $this->joueurs->rattacherAuDuo($invite->id, $duoId);
                $this->joueurs->rattacherAuDuo($proprietaire->id, $duoId);
            }
            $this->pdo->commit();
        } catch (Throwable $erreur) {
            $this->pdo->rollBack();
            throw $erreur;
        }
        if ($duoId === null) {
            // L'invité est entré en duo entre la lecture et le verrou (double clic, deux appareils) : ce n'est pas une erreur de saisie, elle ne compte pas.
            if ($inviteFrais?->duoId !== null) {
                $this->limite->liberer($reservation);
                return RefusCode::DejaEnDuo;
            }
            return RefusCode::PasValable;
        }
        $this->limite->liberer($reservation);
        return $this->duos->trouverParId($duoId);
    }

    // B2 — étape d'accès du joueur (doc technique § 3.6). Le partenaire est relu en base : son état change sans que le joueur agisse.
    public function etapeAcces(Joueur $joueur): EtapeAcces
    {
        if ($joueur->duoId === null) {
            return EtapeAcces::SansDuo;
        }
        if (!$joueur->onboardingVu) {
            return EtapeAcces::OnboardingAFaire;
        }
        // Un duo sans second joueur est une base incohérente (le schéma ne la produit pas) : attendre indéfiniment cacherait la panne,
        // et SansDuo ferait boucler B1 sur elle-même. On échoue donc franchement (500), le joueur n'accède jamais au jeu sur un état faux.
        $partenaire = $this->joueurs->trouverPartenaire($joueur);
        if ($partenaire === null) {
            throw new LogicException("Le duo {$joueur->duoId} n'a pas de second joueur (joueur {$joueur->id}).");
        }
        return $partenaire->onboardingVu ? EtapeAcces::AccesAuJeu : EtapeAcces::Attente;
    }

    // B2 — « Passer » et le bouton du dernier écran. Idempotent (deux appareils) : le second appel ne change rien, donc ne déplace pas l'activation.
    // Transaction sous verrou des deux joueurs, comme la formation : deux joueurs qui finissent en même temps ne s'ignorent pas.
    // Le partenaire est lu APRÈS le verrou du joueur, sur sa ligne fraîche : un duo dissous ou refait entre-temps ne trompe pas l'activation.
    public function terminerOnboarding(Joueur $joueur, DateTimeImmutable $maintenant): void
    {
        $this->pdo->beginTransaction();
        try {
            $frais = $this->joueurs->verrouiller([$joueur->id])[$joueur->id] ?? null;
            if ($frais?->duoId !== null && !$frais->onboardingVu) {
                $partenaire = $this->joueurs->trouverPartenaire($frais);
                // Les deux lignes, dans l'ordre des numéros. Le joueur est déjà verrouillé : le second verrou n'attend que le partenaire.
                // Un interblocage reste possible si les deux finissent à la même milliseconde : InnoDB en annule un, le joueur recommence.
                $verrouilles = $this->joueurs->verrouiller(array_filter([$joueur->id, $partenaire?->id]));
                $this->joueurs->marquerOnboardingVu($joueur->id);
                $partenaireFrais = $partenaire === null ? null : ($verrouilles[$partenaire->id] ?? null);
                // N'active que si le partenaire est toujours du même duo et a fini.
                if ($partenaireFrais?->duoId === $frais->duoId && $partenaireFrais->onboardingVu) {
                    $this->duos->activer($frais->duoId, $maintenant);
                }
            }
            $this->pdo->commit();
        } catch (Throwable $erreur) {
            $this->pdo->rollBack();
            throw $erreur;
        }
    }

    // F2 — dissolution du duo. Vrai si un duo a été dissous, faux si le joueur n'en a plus à la relecture (objet périmé, seconde demande, deux appareils).
    // Même transaction que terminerOnboarding : décision prise sur les lignes fraîches. Le joueur est verrouillé seul, puis les deux (dans l'ordre des numéros).
    // Un interblocage reste possible si les deux dissolvent à la même milliseconde : InnoDB en annule un, le joueur recommence.
    public function dissoudre(Joueur $joueur): bool
    {
        $this->pdo->beginTransaction();
        try {
            $frais = $this->joueurs->verrouiller([$joueur->id])[$joueur->id] ?? null;
            $dissous = $frais?->duoId !== null;
            if ($dissous) {
                $partenaire = $this->joueurs->trouverPartenaire($frais);
                $this->joueurs->verrouiller(array_filter([$joueur->id, $partenaire?->id]));
                // TODO(simplif): 5 écrit en dur, à remplacer par la constante de départ de PointsDeVieService quand elle existe.
                $this->joueurs->remettreAZero($joueur->id, 5);
                if ($partenaire !== null) {
                    $this->joueurs->remettreAZero($partenaire->id, 5);
                    $this->joueurs->signalerDissolution($partenaire->id);
                }
                $this->duos->supprimer($frais->duoId);
            }
            $this->pdo->commit();
        } catch (Throwable $erreur) {
            $this->pdo->rollBack();
            throw $erreur;
        }
        return $dissous;
    }

    // Raccourci pour qui construit le service avec ses dépôts : le routeur et les contrôleurs, sans conteneur d'injection (ADR-0001).
    public static function construire(PDO $pdo): self
    {
        return new self($pdo, new JoueurDepot($pdo), new DuoDepot($pdo), new TentativeDepot($pdo));
    }

    // Le code tel qu'on le range : NFC, sans espaces ni tirets (copier-coller de « XXXX-XXXX »), en majuscules.
    // null si ce ne peut pas être un code : UTF-8 invalide, ou autre chose que 8 caractères de l'alphabet.
    private function normaliserCode(string $saisie): ?string
    {
        $texte = Normalizer::normalize($saisie, Normalizer::FORM_C);
        if ($texte === false) {
            return null;
        }
        // strtoupper et non mb_strtoupper : « ß » deviendrait « SS » et passerait pour deux caractères de l'alphabet.
        $code = strtoupper(preg_replace('/[\p{Z}\p{Pd}]+/u', '', $texte));
        return preg_match('/^[A-HJ-NP-Z2-9]{8}$/D', $code) ? $code : null;
    }

    private function tirerCode(): string
    {
        $code = '';
        foreach (str_split(random_bytes(self::LONGUEUR_CODE)) as $octet) {
            $code .= self::ALPHABET[ord($octet) & 31];
        }
        return $code;
    }
}
