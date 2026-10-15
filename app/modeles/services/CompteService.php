<?php
// Service des comptes — A3 Connexion — lot 1 — A. Écran de référence : annoté étape par étape avec les numéros du doc technique § 2.2 (§ 2.4).

class CompteService
{
    // Limitation des tentatives : 5 échecs en 15 minutes, puis 15 minutes d'attente (cadrage § 6.6).
    private const ECHECS_MAX = 5;
    private const FENETRE = '15 minutes';
    // Longueur maximale d'un identifiant (cadrage § 6.6), reprise par le maxlength du formulaire.
    public const IDENTIFIANT_MAX = 30;

    // Le contrôleur construit les dépôts et les passe ici : aucune dépendance cachée (ADR-0001).
    public function __construct(
        private JoueurDepot $joueurs,
        private TentativeDepot $tentatives,
    ) {}

    // Étape 6 — côté modèle : le contrôleur de A3 appelle ce service avec les champs du formulaire.
    // Résultat : le Joueur s'il est connecté, l'heure de réessai s'il doit attendre, null si l'identifiant ou le mot de passe est faux.
    public function connecter(string $identifiant, string $mdp, string $cleSession, DateTimeImmutable $maintenant): Joueur|DateTimeImmutable|null
    {
        // Étape 6.0 — identifiant trop long : aucun compte ne peut porter ce nom, on refuse sans toucher la base.
        // Le maxlength du formulaire ne protège pas d'une requête forgée, et la colonne tentative.cle est limitée à 64 caractères.
        if (mb_strlen($identifiant) > self::IDENTIFIANT_MAX) {
            return null;
        }

        // Étape 6.1 — attente en cours ?
        // Vérifiée avant le mot de passe : pendant l'attente, même le bon mot de passe est refusé.
        // Un essai refusé ici n'est pas enregistré, sinon chaque nouvel essai prolongerait l'attente.
        // Deux compteurs : par identifiant et par session, pour que changer l'un ou l'autre ne remette pas à zéro.
        // Quand les deux bloquent, on annonce l'heure la plus tardive ; max() classe null avant toute date.
        $attente = max($this->attente($identifiant, $maintenant), $this->attente($cleSession, $maintenant));
        if ($attente !== null) {
            return $attente;
        }

        // Étape 6.2 — recherche du joueur, puis vérification de l'empreinte.
        $joueur = $this->joueurs->trouverParIdentifiant($identifiant);
        if ($joueur !== null && password_verify($mdp, $joueur->mdpHash)) {
            return $joueur;
        }

        // Étape 6.3 — échec : on le date sur les deux compteurs.
        // Un identifiant inconnu est compté comme un autre, et reçoit le même refus.
        $this->tentatives->ajouter($identifiant, TypeTentative::Connexion, $maintenant);
        $this->tentatives->ajouter($cleSession, TypeTentative::Connexion, $maintenant);
        return null;
    }

    // Heure de réessai si la clé est en attente, sinon null.
    // L'attente part du 5ᵉ échec, qui est le plus récent puisque les essais refusés ne sont pas enregistrés.
    // Une fenêtre comptée depuis maintenant libérerait trop tôt : les 5 échecs en sortent avant la fin de l'attente.
    // On remonte donc de 30 minutes : 15 pour que les 5 échecs aient eu lieu, 15 pour l'attente qui les suit.
    private function attente(string $cle, DateTimeImmutable $maintenant): ?DateTimeImmutable
    {
        $echecs = $this->tentatives->datesDepuis($cle, TypeTentative::Connexion, $maintenant->modify('-30 minutes'));
        if (count($echecs) < self::ECHECS_MAX) {
            return null;
        }
        // $echecs est trié du plus récent au plus ancien : [0] est le dernier échec, [4] le 5ᵉ en remontant.
        $dernier = $echecs[0];
        $enRafale = $echecs[self::ECHECS_MAX - 1] > $dernier->modify('-' . self::FENETRE);
        $finAttente = $dernier->modify('+' . self::FENETRE);
        return $enRafale && $maintenant < $finAttente ? $finAttente : null;
    }
}
