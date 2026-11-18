<?php
// Journée de jeu — C1 Accueil — parcours 2 (journée de jeu) — lot 1 — B. Bornes de la journée en Europe/Paris (doc technique § 3.1).

class JourneeService
{
    // Les dépôts sont construits par l'appelant, contrôleur ou test (ADR-0001).
    public function __construct(private ParticipationDepot $participations, private DuoDepot $duos) {}

    // Identifiant de journée AAAA-MM-JJ, clé de participation et de tirage.
    public function journee(DateTimeImmutable $maintenant): string
    {
        return self::enHeureDeParis($maintenant)->format('Y-m-d');
    }

    // Bascule de 12 h du jour : fin des réponses et des choix (cadrage § 4.2).
    public function bascule(DateTimeImmutable $maintenant): DateTimeImmutable
    {
        return self::enHeureDeParis($maintenant)->modify('today 12:00');
    }

    // Expiration des défis du jour (cadrage § 4.1). « tomorrow » suit le calendrier : 23 h ou 25 h après l'ouverture un jour de changement d'heure.
    public function minuitSuivant(DateTimeImmutable $maintenant): DateTimeImmutable
    {
        return self::enHeureDeParis($maintenant)->modify('tomorrow');
    }

    public function avantBascule(DateTimeImmutable $maintenant): bool
    {
        return $maintenant < $this->bascule($maintenant);
    }

    // Réponse à « On joue aujourd'hui ? », écrite seulement quand la question se pose encore (état 1) : ni après le non du partenaire (spec #57, récit 4), ni sans duo.
    // Une seconde réponse (double clic, second appareil) est ignorée sans erreur ; la clé unique tranche une course entre deux envois, la première reste définitive.
    // Faux seulement quand une réponse jamais donnée arrive à 12 h ou après (horodatage serveur) : rien n'est écrit et le contrôleur l'explique.
    public function repondre(Joueur $joueur, Reponse $reponse, DateTimeImmutable $maintenant): bool
    {
        $aParis = self::enHeureDeParis($maintenant);
        if (!$this->avantBascule($maintenant)) {
            // Celui qui avait déjà répondu (page restée ouverte) n'est pas refusé : sa réponse a été prise en compte.
            return $this->participations->trouverParJoueurEtJour($joueur->id, $aParis) !== null;
        }
        if ($this->etatAccueil($joueur, $maintenant) === EtatAccueil::Question) {
            // L'heure de réponse s'écrit en heure de Paris, comme la journée.
            $this->participations->ajouter($joueur->duoId, $joueur->id, $aParis, $reponse, $aParis);
        }
        return true;
    }

    // État de C1 pour ce joueur, déduit de l'activation du duo, des participations du jour et de l'heure ; rien n'est écrit (la journée blanche non plus).
    public function etatAccueil(Joueur $joueur, DateTimeImmutable $maintenant): EtatAccueil
    {
        // Un joueur relu sans duo (dissolution entre-temps) n'a pas de journée : pas de jeu, et la question n'est pas posée.
        if ($joueur->duoId === null) {
            return EtatAccueil::JourneeBlanche;
        }
        // Duo activé à 12 h ou après : la bascule du jour est déjà passée, le premier jour de jeu est le lendemain (cadrage § 4.2).
        // L'activation n'est jamais dans le futur : passé minuit, elle tombe avant la bascule du jour et la question revient.
        $activation = $this->duos->trouverParId($joueur->duoId)?->dateActivation;
        if ($activation !== null && $activation >= $this->bascule($maintenant)) {
            return EtatAccueil::PremierJourDemain;
        }
        $sienne = null;
        $celleDuPartenaire = null;
        foreach ($this->participations->trouverParDuoEtJour($joueur->duoId, self::enHeureDeParis($maintenant)) as $participation) {
            if ($participation->joueurId === $joueur->id) {
                $sienne = $participation->reponse;
            } else {
                $celleDuPartenaire = $participation->reponse;
            }
        }
        // Un seul non suffit : la question n'est plus posée à l'autre (spec #57, récit 4).
        if ($sienne === Reponse::Non || $celleDuPartenaire === Reponse::Non) {
            return EtatAccueil::JourneeBlanche;
        }
        if ($sienne === Reponse::Oui && $celleDuPartenaire === Reponse::Oui) {
            return EtatAccueil::LesDeuxOntDitOui;
        }
        // Passé 12 h sans les deux oui : l'un des deux n'a pas répondu, la journée est blanche.
        if (!$this->avantBascule($maintenant)) {
            return EtatAccueil::JourneeBlanche;
        }
        if ($sienne === null) {
            return EtatAccueil::Question;
        }
        return EtatAccueil::AttentePartenaire;
    }

    // La journée se lit à Paris quel que soit le fuseau de l'instant reçu : le service ne dépend pas du fuseau par défaut posé par l'amorce.
    private static function enHeureDeParis(DateTimeImmutable $maintenant): DateTimeImmutable
    {
        return $maintenant->setTimezone(new DateTimeZone('Europe/Paris'));
    }
}
