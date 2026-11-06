<?php
// Contrôleur de la salle d'attente — B1 — lot 1 — A.

class SalleAttenteControleur
{
    // Clé de session qui transporte la saisie et le refus du POST vers le GET qui suit la redirection.
    private const RETOUR = 'retour_salle_attente';

    // Le routeur passe la connexion et l'heure ; le contrôleur construit lui-même ses dépôts et services (ADR-0001).
    public function __construct(private PDO $pdo, private DateTimeImmutable $maintenant) {}

    // GET : la variante « déjà en duo » remplace la saisie quand le joueur a déjà un partenaire.
    public function afficher(): void
    {
        $joueur = Routeur::joueurConnecte($this->pdo);
        $retour = $_SESSION[self::RETOUR] ?? ['saisie' => '', 'refus' => null, 'attente' => null];
        unset($_SESSION[self::RETOUR]);
        // Le code d'un lien est prérempli, jamais validé ici : un GET n'écrit pas d'essai, et aucun duo ne se forme sans le clic du joueur.
        // Il vient de l'URL (joueur déjà connecté) ou de la session (lien ouvert avant l'inscription ou la connexion) ; il ne sert qu'une fois.
        $recu = is_string($_GET['code'] ?? null) ? $_GET['code'] : null;
        $conserve = CodeRecu::reprendre();

        // L'aiguillage (redirection, variante « déjà en duo », code à montrer) est une règle : le service la porte, le contrôleur l'exécute.
        $aiguillage = $this->service()->salleAttente($joueur, $recu !== null, $this->maintenant);
        if ($aiguillage->redirection !== null) {
            Routeur::rediriger(Routeur::ecranDeLEtape($aiguillage->redirection));
        }
        if ($aiguillage->dejaEnDuo) {
            Vue::rendre('b1-salle-attente', 'Salle d\'attente', [
                'dejaEnDuo' => true,
                'partenaire' => $aiguillage->partenaire,
            ]);
            return;
        }

        $joueur = $aiguillage->joueur;
        $saisie = $this->service()->saisieAPrefiller($retour['saisie'], $recu, $conserve);
        $titre = ($retour['refus'] !== null || $retour['attente'] !== null) ? 'Erreur : Salle d\'attente' : 'Salle d\'attente';
        Vue::rendre('b1-salle-attente', $titre, [
            'dejaEnDuo' => false,
            // « XXXX-XXXX » se lit et se dicte mieux ; la saisie accepte les deux formes.
            'code' => substr($joueur->codeInvitation, 0, 4) . '-' . substr($joueur->codeInvitation, 4),
            'echeance' => $joueur->dateExpirationCode,
            'saisie' => $saisie,
            'refus' => $retour['refus'],
            'attente' => $retour['attente'],
        ]);
    }

    // POST : le jeton CSRF a déjà été vérifié par le routeur. Deux boutons, un champ caché `action` les distingue.
    public function traiter(): void
    {
        $joueur = Routeur::joueurConnecte($this->pdo);
        $service = $this->service();

        // Lien « nouveau code » : écrase l'ancien code ; le service n'y touche pas si le joueur est déjà en duo.
        if (($_POST['action'] ?? '') === 'nouveau-code') {
            $service->regenerer($joueur, $this->maintenant);
            Routeur::rediriger('salle-attente');
        }

        // Aucune règle ici : le service contrôle tout et renvoie le duo formé, l'heure de réessai ou la raison du refus.
        $saisie = is_string($_POST['code'] ?? null) ? $_POST['code'] : '';
        $resultat = $service->rejoindre($joueur, $saisie, session_id(), $this->maintenant, $_SERVER['REMOTE_ADDR'] ?? null);
        if ($resultat instanceof Duo) {
            Routeur::rediriger('onboarding');
        }
        // Redirection après écriture (§ 2.4) : même un refus écrit un essai, donc on repasse par un GET.
        $_SESSION[self::RETOUR] = [
            'saisie' => $saisie,
            'refus' => $resultat instanceof RefusCode ? $resultat : null,
            'attente' => $resultat instanceof DateTimeImmutable ? $resultat : null,
        ];
        Routeur::rediriger('salle-attente');
    }

    private function service(): AppairageService
    {
        return AppairageService::construire($this->pdo);
    }
}
